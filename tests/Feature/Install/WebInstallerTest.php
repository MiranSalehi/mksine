<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallPipeline;
use Miran\Mksine\Core\Install\InstallProgress;
use Miran\Mksine\Core\Install\PreInstallBootstrap;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Core\Install\Web\InstallerHandoff;
use Miran\Mksine\Core\Install\Web\InstallerOwnership;
use Miran\Mksine\Core\Install\Web\SetupToken;
use Miran\Mksine\Http\Controllers\Installer\InstallerRoutes;
use Miran\Mksine\Http\Middleware\Installer\RedirectToInstaller;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->originalStoragePath = $this->app->storagePath();
    $this->originalEnvironmentPath = $this->app->environmentPath();
    $this->tmp = sys_get_temp_dir() . '/mksine-web-installer-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/storage/mksine');
    file_put_contents($this->tmp . '/.env.example', "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\nAPP_KEY=\nDB_CONNECTION=sqlite\n");
    copy($this->tmp . '/.env.example', $this->tmp . '/.env');

    $this->app->useStoragePath($this->tmp . '/storage');
    $this->app->useEnvironmentPath($this->tmp);

    // An installation in progress counts as "not installed" even though the test database has the core tables.
    file_put_contents($this->tmp . '/storage/' . InstallationState::PROGRESS_FILE, '{}');
    $this->app->instance(InstallationState::class, new InstallationState(new Filesystem));
    $this->app->instance(InstallProgress::class, new InstallProgress(new Filesystem, app(InstallationState::class)));
    $this->app->instance(SetupToken::class, new SetupToken(new Filesystem, $this->tmp));

    InstallerRoutes::register();
    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    $this->app->useEnvironmentPath($this->originalEnvironmentPath);
    (new Filesystem)->deleteDirectory($this->tmp);
});

/**
 * @param  list<InstallStep>  $steps
 */
function useFakeInstallSteps(array $steps): void
{
    app()->instance(InstallPipeline::class, new InstallPipeline($steps, app(InstallProgress::class)));
}

function fakeWebStep(string $key, Closure $run): InstallStep
{
    return new class($key, $run) implements InstallStep
    {
        public function __construct(private string $stepKey, private Closure $callback) {}

        public function key(): string
        {
            return $this->stepKey;
        }

        public function label(): string
        {
            return 'Step ' . $this->stepKey;
        }

        public function run(InstallContext $context): StepResult
        {
            return ($this->callback)($context);
        }
    };
}

function becomeInstallerOwner(): void
{
    $token = str_repeat('a', 64);
    (new Filesystem)->ensureDirectoryExists(dirname(storage_path(InstallerOwnership::FILE)));
    file_put_contents(storage_path(InstallerOwnership::FILE), json_encode(['token_hash' => hash('sha256', $token), 'claimed_at' => time()]));
    test()->withSession([InstallerOwnership::SESSION_KEY => $token]);
}

function setupTokenFromFile(string $directory): string
{
    return trim((string) strtok((string) file_get_contents($directory . '/' . SetupToken::FILE_NAME), "\n"));
}

function completeInstallerForms(): void
{
    becomeInstallerOwner();
    test()->withSession([
        'mksine_installer.database_configured' => true,
        'mksine_installer.site' => [
            'site_name' => 'Shop',
            'site_url' => 'https://shop.test',
            'locale' => 'fa',
            'admin_name' => 'Owner',
            'admin_email' => 'owner@shop.test',
            'admin_password' => Crypt::encryptString('password123'),
            'theme' => 'mksine',
            'plugins' => [],
            'sample_content' => false,
        ],
    ]);
}

it('sends web requests to the installer until MKSine is installed', function (): void {
    $kernel = app(Kernel::class);
    $kernel->prependMiddleware(RedirectToInstaller::class);

    $this->get('/')->assertRedirect('/install');
    $this->getJson('/api/anything')->assertStatus(503);
    $this->get('/install')->assertOk();
});

it('shows the language choice and stores the chosen language', function (): void {
    $this->get('/install')->assertOk()->assertSee('فارسی')->assertSee('English');

    $this->post('/install/language', ['locale' => 'fa'])->assertRedirect(route('mksine.installer.requirements'));

    $this->get('/install/requirements')
        ->assertOk()
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee(__('mksine::install.web.requirements_heading', [], 'fa'));
});

it('opens the installer without a code for the first browser', function (): void {
    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.welcome'));
    $this->postJson('/install/run')->assertForbidden();

    $this->post('/install/language', ['locale' => 'en'])->assertRedirect(route('mksine.installer.requirements'));
    $this->get('/install/requirements')->assertOk();

    expect(is_file($this->tmp . '/' . SetupToken::FILE_NAME))->toBeFalse();
});

it('asks a second browser for the setup code', function (): void {
    $this->post('/install/language', ['locale' => 'en']);
    $this->flushSession();

    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.verify'));
    $this->post('/install/language', ['locale' => 'en'])->assertRedirect(route('mksine.installer.verify'));
    $this->postJson('/install/run')->assertForbidden();

    $this->get('/install/verify')->assertOk()->assertSee(SetupToken::FILE_NAME);

    expect(is_file($this->tmp . '/' . SetupToken::FILE_NAME))->toBeTrue()
        ->and(setupTokenFromFile($this->tmp))->toMatch('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/');
});

it('rejects a wrong setup token', function (): void {
    $this->get('/install/verify');

    $this->from('/install/verify')->post('/install/verify', ['token' => 'WRONG-CODE-0000'])
        ->assertRedirect('/install/verify')
        ->assertSessionHasErrors('token');

    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.welcome'));
});

it('opens the installer after the right setup token, typed loosely', function (): void {
    $this->get('/install/verify');
    $code = strtolower(str_replace('-', ' ', setupTokenFromFile($this->tmp)));

    $this->post('/install/verify', ['token' => $code])->assertRedirect(route('mksine.installer.requirements'));

    $this->get('/install/requirements')->assertOk();
    $this->get('/install/verify')->assertRedirect(route('mksine.installer.requirements'));
});

it('lets another browser with the setup token take over', function (): void {
    $this->get('/install/verify');
    $code = setupTokenFromFile($this->tmp);
    $this->post('/install/verify', ['token' => $code]);

    $this->flushSession();
    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.verify'));

    $this->post('/install/verify', ['token' => $code])->assertRedirect(route('mksine.installer.requirements'));
    $this->get('/install/requirements')->assertOk();
});

it('opens the installer once with the quick installer\'s handoff code', function (): void {
    file_put_contents(storage_path(InstallerHandoff::FILE), json_encode(['token_hash' => hash('sha256', 'handoff-code'), 'expires_at' => time() + 600]));

    $this->post('/install/handoff', ['handoff' => 'handoff-code', 'locale' => 'fa'])
        ->assertRedirect(route('mksine.installer.requirements'))
        ->assertSessionHas('mksine_installer.locale', 'fa');

    $this->get('/install/requirements')->assertOk();
    expect(file_exists(storage_path(InstallerHandoff::FILE)))->toBeFalse();

    $this->flushSession();
    $this->post('/install/handoff', ['handoff' => 'handoff-code'])->assertRedirect(route('mksine.installer.welcome'));
    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.verify'));
});

it('rejects a wrong or expired handoff code', function (): void {
    $this->post('/install/handoff', ['handoff' => 'anything'])->assertRedirect(route('mksine.installer.welcome'));

    file_put_contents(storage_path(InstallerHandoff::FILE), json_encode(['token_hash' => hash('sha256', 'handoff-code'), 'expires_at' => time() + 600]));
    $this->post('/install/handoff', ['handoff' => 'other-code'])->assertRedirect(route('mksine.installer.welcome'));
    expect(file_exists(storage_path(InstallerHandoff::FILE)))->toBeTrue();

    file_put_contents(storage_path(InstallerHandoff::FILE), json_encode(['token_hash' => hash('sha256', 'handoff-code'), 'expires_at' => time() - 1]));
    $this->post('/install/handoff', ['handoff' => 'handoff-code'])->assertRedirect(route('mksine.installer.welcome'));
    $this->get('/install/requirements')->assertRedirect(route('mksine.installer.welcome'));
});

it('tests and saves an SQLite database', function (): void {
    becomeInstallerOwner();
    $database = $this->tmp . '/site.sqlite';

    $this->post('/install/database', ['connection' => 'sqlite', 'database' => $database])
        ->assertRedirect(route('mksine.installer.site'))
        ->assertSessionHas('mksine_installer.database_configured', true);

    $env = (string) file_get_contents($this->tmp . '/.env');

    expect(is_file($database))->toBeTrue()
        ->and($env)->toContain('DB_CONNECTION=sqlite')
        ->and($env)->toContain('DB_DATABASE=' . $database);
});

it('reports a database that cannot be reached and keeps .env unchanged', function (): void {
    becomeInstallerOwner();
    $before = file_get_contents($this->tmp . '/.env');

    $this->from('/install/database')
        ->post('/install/database', ['connection' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'mksine', 'username' => 'nobody', 'password' => 'secret'])
        ->assertRedirect('/install/database')
        ->assertSessionHasErrors('database')
        ->assertSessionMissing('mksine_installer.database_configured');

    expect(file_get_contents($this->tmp . '/.env'))->toBe($before);
});

it('requires the database step before the site step', function (): void {
    becomeInstallerOwner();
    $this->get('/install/site')->assertRedirect(route('mksine.installer.database'));
});

it('saves the site, writes production settings and never keeps the plain admin password', function (): void {
    becomeInstallerOwner();
    $this->withSession(['mksine_installer.database_configured' => true]);

    $this->get('/install/site')->assertOk()->assertSee('name="admin_email"', escape: false);

    $this->post('/install/site', [
        'site_name' => 'My Shop',
        'site_url' => 'https://shop.test/',
        'locale' => 'fa',
        'admin_name' => 'Owner',
        'admin_email' => 'owner@shop.test',
        'admin_password' => 'password123',
        'admin_password_confirmation' => 'password123',
        'theme' => 'mksine',
        'sample_content' => '0',
    ])->assertRedirect(route('mksine.installer.run'));

    $site = session('mksine_installer.site');
    $env = (string) file_get_contents($this->tmp . '/.env');

    expect($site['admin_email'])->toBe('owner@shop.test')
        ->and($site['admin_password'])->not->toBe('password123')
        ->and(Crypt::decryptString($site['admin_password']))->toBe('password123')
        ->and($env)->toContain("APP_NAME='My Shop'")
        ->and($env)->toContain('APP_URL=https://shop.test')
        ->and($env)->toContain('APP_ENV=production')
        ->and($env)->toContain('APP_DEBUG=false');
});

it('rejects a mismatched password confirmation and an unknown plugin', function (): void {
    becomeInstallerOwner();
    $this->withSession(['mksine_installer.database_configured' => true]);

    $this->post('/install/site', [
        'site_name' => 'My Shop',
        'site_url' => 'https://shop.test',
        'locale' => 'fa',
        'admin_name' => 'Owner',
        'admin_email' => 'owner@shop.test',
        'admin_password' => 'password123',
        'admin_password_confirmation' => 'different1',
        'plugins' => ['no-such-plugin'],
    ])->assertSessionHasErrors(['admin_password', 'plugins.0']);
});

it('runs one step per request until the installation is finished', function (): void {
    $received = null;
    useFakeInstallSteps([
        fakeWebStep('one', fn (): StepResult => StepResult::completed(['first'])),
        fakeWebStep('finalize', function (InstallContext $context) use (&$received): StepResult {
            $received = $context;
            app(InstallationState::class)->markInstalled();
            app(InstallProgress::class)->clear();

            return StepResult::completed();
        }),
    ]);
    completeInstallerForms();
    file_put_contents($this->tmp . '/' . SetupToken::FILE_NAME, "ABCD-EFGH-JKMN\n");

    $this->get('/install/run')->assertOk()->assertSee('Step one');

    $this->postJson('/install/run')->assertOk()->assertJson(['step' => 'one', 'done' => false, 'completed' => 1, 'total' => 2]);

    $this->postJson('/install/run')
        ->assertOk()
        ->assertJson(['step' => 'finalize', 'done' => true, 'completed' => 2])
        ->assertJsonStructure(['admin_url', 'site_url', 'cron_line']);

    expect($received?->adminEmail)->toBe('owner@shop.test')
        ->and($received?->adminPassword)->toBe('password123')
        ->and($received?->migrate)->toBeTrue()
        ->and(is_file(storage_path(InstallationState::LOCK_FILE)))->toBeTrue()
        ->and(is_file(storage_path(InstallerOwnership::FILE)))->toBeFalse()
        ->and(is_file($this->tmp . '/' . SetupToken::FILE_NAME))->toBeFalse()
        ->and(session()->has('mksine_installer.site'))->toBeFalse();
});

it('reports a failed step and retries it on the next request', function (): void {
    $attempts = 0;
    useFakeInstallSteps([
        fakeWebStep('flaky', function () use (&$attempts): StepResult {
            $attempts++;

            return $attempts === 1 ? StepResult::failed('database is locked') : StepResult::completed();
        }),
    ]);
    completeInstallerForms();

    $this->postJson('/install/run')->assertOk()->assertJson(['step' => 'flaky', 'done' => false, 'result' => ['status' => 'failed', 'error' => 'database is locked']]);
    $this->postJson('/install/run')->assertOk()->assertJson(['step' => 'flaky', 'result' => ['status' => 'completed']]);
});

it('refuses to run without the site details', function (): void {
    becomeInstallerOwner();
    $this->postJson('/install/run')->assertStatus(409);
    $this->get('/install/run')->assertRedirect(route('mksine.installer.site'));
});

it('switches drivers and generates an app key before installation', function (): void {
    config(['app.key' => '', 'session.driver' => 'database', 'cache.default' => 'database', 'queue.default' => 'database']);

    PreInstallBootstrap::apply(app(), app(InstallationState::class));

    expect(config('app.key'))->toStartWith('base64:')
        ->and(config('session.driver'))->toBe('file')
        ->and(config('session.cookie'))->toBe('mksine_installer_session')
        ->and(config('cache.default'))->toBe('file')
        ->and(config('queue.default'))->toBe('sync')
        ->and((string) file_get_contents($this->tmp . '/.env'))->toContain('APP_KEY=' . config('app.key'));
});

it('leaves an installed site alone', function (): void {
    app(InstallationState::class)->markInstalled();
    config(['app.key' => '', 'session.driver' => 'database']);

    PreInstallBootstrap::apply(app(), app(InstallationState::class));

    expect(config('app.key'))->toBe('')
        ->and(config('session.driver'))->toBe('database');
});
