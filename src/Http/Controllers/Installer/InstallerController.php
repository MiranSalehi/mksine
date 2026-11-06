<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers\Installer;

use Faker\Factory;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallPipeline;
use Miran\Mksine\Core\Install\InstallStepStatus;
use Miran\Mksine\Core\Install\Steps\ConfigureRuntimeStep;
use Miran\Mksine\Core\Install\Web\InstallerHandoff;
use Miran\Mksine\Core\Install\Web\InstallerOwnership;
use Miran\Mksine\Core\Install\Web\SetupToken;
use Miran\Mksine\Http\Middleware\Installer\PrepareInstallerRequest;
use Throwable;

/**
 * Browser installer for hosts without a terminal: language, requirements, database,
 * site and admin, then the shared install pipeline one step per request.
 */
class InstallerController extends Controller
{
    /** @var list<string> */
    public const DATABASE_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlite'];

    private const DATABASE_CONFIGURED = 'mksine_installer.database_configured';

    private const SITE = 'mksine_installer.site';

    public function welcome(): View
    {
        return view('mksine::installer.welcome', ['locales' => PrepareInstallerRequest::LOCALES]);
    }

    /**
     * Store the language. The first browser to get here owns the installation; the setup
     * code is asked for only when another browser already started it.
     */
    public function storeLanguage(Request $request, InstallerOwnership $ownership): RedirectResponse
    {
        $validated = $request->validate(['locale' => ['required', Rule::in(PrepareInstallerRequest::LOCALES)]]);

        if (! $ownership->isOwner($request->session()) && ! $ownership->hasOwner()) {
            $request->session()->regenerate();
            $ownership->claim($request->session());
        }

        $request->session()->put(PrepareInstallerRequest::LOCALE_SESSION_KEY, $validated['locale']);

        return $ownership->isOwner($request->session())
            ? redirect()->route('mksine.installer.requirements')
            : redirect()->route('mksine.installer.verify');
    }

    public function verify(Request $request, SetupToken $token, InstallerOwnership $ownership): View | RedirectResponse
    {
        if ($ownership->isOwner($request->session())) {
            return redirect()->route('mksine.installer.requirements');
        }

        try {
            $token->ensureExists();
            $error = null;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }

        return view('mksine::installer.verify', ['tokenPath' => $token->displayPath(), 'tokenError' => $error]);
    }

    public function storeVerify(Request $request, SetupToken $token, InstallerOwnership $ownership): RedirectResponse
    {
        $validated = $request->validate(['token' => ['required', 'string', 'max:64']]);

        if (! $token->verify($validated['token'])) {
            return back()->withErrors(['token' => __('mksine::install.web.token_invalid')]);
        }

        $request->session()->regenerate();
        $ownership->claim($request->session());

        return redirect()->route('mksine.installer.requirements');
    }

    /**
     * Continue from the quick installer, which already checked the setup token.
     */
    public function handoff(Request $request, InstallerHandoff $handoff, InstallerOwnership $ownership): RedirectResponse
    {
        $code = $request->input('handoff');

        if (! is_string($code) || ! $handoff->consume($code)) {
            return redirect()->route('mksine.installer.welcome');
        }

        $request->session()->regenerate();
        $ownership->claim($request->session());

        $locale = $request->input('locale');

        if (in_array($locale, PrepareInstallerRequest::LOCALES, true)) {
            $request->session()->put(PrepareInstallerRequest::LOCALE_SESSION_KEY, $locale);
        }

        return redirect()->route('mksine.installer.requirements');
    }

    public function requirements(Request $request, EnvironmentProbe $probe): View
    {
        $checker = new RequirementsChecker(RequirementsChecker::writablePathsFor(base_path(), storage_path(), public_path()));
        $report = $probe->probe(public_path(), $this->documentRoot($request));

        return view('mksine::installer.requirements', [
            'checks' => $checker->check(),
            'passes' => $checker->passes(),
            'report' => $report,
            'projectInsideWebRoot' => EnvironmentProbe::projectIsInsideWebRoot(base_path(), public_path(), $this->documentRoot($request)),
        ]);
    }

    public function database(Request $request): View
    {
        $connection = (string) config('database.default');
        $settings = (array) config("database.connections.{$connection}", []);

        return view('mksine::installer.database', [
            'drivers' => self::DATABASE_DRIVERS,
            'defaults' => [
                'connection' => in_array($connection, self::DATABASE_DRIVERS, true) ? $connection : 'mysql',
                'host' => $settings['host'] ?? '127.0.0.1',
                'port' => $settings['port'] ?? '3306',
                'database' => $connection === 'sqlite' ? '' : ($settings['database'] ?? ''),
                'username' => $settings['username'] ?? '',
            ],
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'connection' => ['required', Rule::in(self::DATABASE_DRIVERS)],
            'host' => ['exclude_if:connection,sqlite', 'required', 'string', 'max:255'],
            'port' => ['exclude_if:connection,sqlite', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'username' => ['exclude_if:connection,sqlite', 'required', 'string', 'max:255'],
            'password' => ['exclude_if:connection,sqlite', 'nullable', 'string', 'max:255'],
        ]);

        $connection = $validated['connection'];
        $values = ['DB_CONNECTION' => $connection];

        if ($connection === 'sqlite') {
            $values['DB_DATABASE'] = filled($validated['database'] ?? null) ? (string) $validated['database'] : database_path('database.sqlite');
        } else {
            $values += [
                'DB_HOST' => (string) $validated['host'],
                'DB_PORT' => (string) ($validated['port'] ?? ''),
                'DB_DATABASE' => (string) $validated['database'],
                'DB_USERNAME' => (string) $validated['username'],
                'DB_PASSWORD' => (string) ($validated['password'] ?? ''),
            ];
        }

        $error = $this->testDatabaseConnection($connection, $values);

        if ($error !== null) {
            return back()->withInput($request->except('password'))->withErrors(['database' => __('mksine::install.web.database_failed', ['error' => $error])]);
        }

        try {
            $this->environment()->set($values);
        } catch (Throwable $exception) {
            return back()->withInput($request->except('password'))->withErrors(['database' => $exception->getMessage()]);
        }

        $request->session()->put(self::DATABASE_CONFIGURED, true);

        return redirect()->route('mksine.installer.site');
    }

    /**
     * Site and admin account. The default theme is activated and no plugin is: plugins are
     * turned on later in the admin panel.
     */
    public function site(Request $request): View | RedirectResponse
    {
        if (! $request->session()->get(self::DATABASE_CONFIGURED)) {
            return redirect()->route('mksine.installer.database');
        }

        $site = (array) $request->session()->get(self::SITE, []);

        return view('mksine::installer.site', [
            // Sample content is generated with Faker, which production (--no-dev) builds leave out.
            'sampleContentAvailable' => class_exists(Factory::class),
            'defaults' => [
                'site_name' => $site['site_name'] ?? (config('app.name') === 'Laravel' ? '' : config('app.name')),
                'site_url' => $site['site_url'] ?? $request->root(),
                'locale' => $site['locale'] ?? app()->getLocale(),
                'admin_name' => $site['admin_name'] ?? '',
                'admin_email' => $site['admin_email'] ?? '',
                'sample_content' => $site['sample_content'] ?? false,
            ],
        ]);
    }

    public function storeSite(Request $request): RedirectResponse
    {
        if (! $request->session()->get(self::DATABASE_CONFIGURED)) {
            return redirect()->route('mksine.installer.database');
        }

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_url' => ['required', 'url', 'max:255'],
            'locale' => ['required', Rule::in(PrepareInstallerRequest::LOCALES)],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'sample_content' => ['boolean'],
        ]);

        $environment = [
            'APP_NAME' => $validated['site_name'],
            'APP_URL' => rtrim($validated['site_url'], '/'),
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
        ];

        $publicPath = $this->configuredPublicPath();
        if ($publicPath !== null) {
            $environment['MKSINE_PUBLIC_PATH'] = $publicPath;
        }

        try {
            $this->environment()->set($environment);
        } catch (Throwable $exception) {
            return back()->withInput($request->except('admin_password', 'admin_password_confirmation'))->withErrors(['site' => $exception->getMessage()]);
        }

        $request->session()->put(self::SITE, [
            'site_name' => $validated['site_name'],
            'site_url' => $validated['site_url'],
            'locale' => $validated['locale'],
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => Crypt::encryptString($validated['admin_password']),
            'sample_content' => (bool) ($validated['sample_content'] ?? false),
        ]);

        return redirect()->route('mksine.installer.run');
    }

    public function run(Request $request, InstallPipeline $pipeline): View | RedirectResponse
    {
        if (! $request->session()->has(self::SITE)) {
            return redirect()->route('mksine.installer.site');
        }

        $completed = $pipeline->progress()->completedKeys();

        return view('mksine::installer.run', [
            'steps' => array_map(fn (InstallStep $step): array => [
                'key' => $step->key(),
                'label' => $step->label(),
                'completed' => in_array($step->key(), $completed, true),
            ], $pipeline->steps()),
        ]);
    }

    /**
     * Run the next pending step. The page calls this repeatedly until `done` is true.
     */
    public function runNext(
        Request $request,
        InstallPipeline $pipeline,
        EnvironmentProbe $probe,
        InstallationState $installation,
        InstallerOwnership $ownership,
        SetupToken $token,
    ): JsonResponse {
        if (! $request->session()->has(self::SITE)) {
            return response()->json(['message' => __('mksine::install.web.session_expired')], 409);
        }

        @set_time_limit(300);
        ignore_user_abort(true);

        $step = $pipeline->nextPendingStep();

        if ($step === null) {
            return response()->json($this->donePayload());
        }

        $result = $pipeline->runStep($step, $this->context($request, $probe));
        $steps = $pipeline->steps();
        $done = $result->status !== InstallStepStatus::Failed && $installation->hasLockFile();

        if ($done) {
            $ownership->release();
            $token->delete();
            $request->session()->forget(['mksine_installer.site', self::DATABASE_CONFIGURED]);
        }

        return response()->json([
            'step' => $step->key(),
            'label' => $step->label(),
            'result' => $result->toArray(),
            'completed' => $done ? count($steps) : count($pipeline->progress()->completedKeys()),
            'total' => count($steps),
            'done' => $done,
            ...($done ? $this->donePayload() : []),
        ]);
    }

    private function context(Request $request, EnvironmentProbe $probe): InstallContext
    {
        $site = (array) $request->session()->get(self::SITE, []);

        return new InstallContext(
            profile: $probe->probe(public_path(), $this->documentRoot($request))->recommendedProfile(),
            migrate: true,
            adminName: $site['admin_name'] ?? null,
            adminEmail: $site['admin_email'] ?? null,
            adminPassword: isset($site['admin_password']) ? Crypt::decryptString((string) $site['admin_password']) : null,
            locale: $site['locale'] ?? null,
            seedSampleContent: (bool) ($site['sample_content'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function donePayload(): array
    {
        try {
            $adminUrl = Filament::getPanel('admin')->getUrl();
        } catch (Throwable) {
            $adminUrl = null;
        }

        return [
            'done' => true,
            'admin_url' => $adminUrl ?? url('/admin'),
            'site_url' => url('/'),
            'cron_line' => ConfigureRuntimeStep::cronLine(),
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    private function testDatabaseConnection(string $connection, array $values): ?string
    {
        $config = (array) config("database.connections.{$connection}", ['driver' => $connection]);

        if ($connection === 'sqlite') {
            $path = $values['DB_DATABASE'];

            if (! is_file($path)) {
                if (! is_dir(dirname($path)) || @touch($path) === false) {
                    return __('mksine::install.web.sqlite_not_writable', ['path' => $path]);
                }
            }

            $config['database'] = $path;
        } else {
            $config = [
                ...$config,
                'host' => $values['DB_HOST'],
                'port' => $values['DB_PORT'] !== '' ? $values['DB_PORT'] : ($config['port'] ?? null),
                'database' => $values['DB_DATABASE'],
                'username' => $values['DB_USERNAME'],
                'password' => $values['DB_PASSWORD'],
            ];
        }

        try {
            DB::build($config)->getPdo();
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    private function environment(): EnvironmentFileEditor
    {
        $path = app()->environmentFilePath();

        return EnvironmentFileEditor::openOrCreate($path, dirname($path) . '/.env.example');
    }

    /**
     * MKSINE_PUBLIC_PATH for artisan, when the web root is not the project's public/ folder.
     */
    private function configuredPublicPath(): ?string
    {
        $public = realpath(public_path());
        $base = realpath(base_path());

        if ($public === false || $base === false || $public === realpath(base_path('public'))) {
            return null;
        }

        return dirname($public) === dirname($base) ? '../' . basename($public) : $public;
    }

    private function documentRoot(Request $request): ?string
    {
        $root = $request->server('DOCUMENT_ROOT');

        return is_string($root) && $root !== '' ? $root : null;
    }
}
