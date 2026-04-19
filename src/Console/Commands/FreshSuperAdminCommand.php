<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'mksine:fresh-super-admin', description: 'Reset the isolated mksine_setup database (drop tables + migrate), then create one super_admin')]
class FreshSuperAdminCommand extends Command
{
    public const SETUP_CONNECTION = 'mksine_setup';

    protected $signature = 'mksine:fresh-super-admin
                            {--name= : Super admin display name}
                            {--email= : Super admin email}
                            {--password= : Super admin password (min 8 characters)}
                            {--panel=admin : Filament panel ID (Shield guard context)}
                            {--force : Skip the danger confirmation}
                            {--export= : Portable DB file path (project-relative or absolute); default: mksine-setup-database.sql (MySQL) or mksine-setup-database.sqlite (SQLite)}
                            {--no-export : Do not write a database file to disk}';

    protected $description = 'Drop all tables on mksine_setup only, run migrations, create one super_admin, then export a portable DB file to the project root (unless --no-export).';

    private const CREDENTIALS_BASENAME = 'mksine-fresh-super-admin.txt';

    private const SUPPORTED_SETUP_DRIVERS = ['sqlite', 'mysql', 'mariadb'];

    public function handle(): int
    {
        if (Utils::isTenancyEnabled()) {
            $this->error('Shield tenancy (teams) is enabled. This command does not assign a tenant. Use `php artisan shield:super-admin` after creating a user manually.');

            return self::FAILURE;
        }

        if ($message = $this->validateSetupConnection()) {
            $this->error($message);
            $this->newLine();
            $this->line('Configure a separate database in .env, for example:');
            $this->line('  <comment>MKSINE_SETUP_DB_DATABASE=mksine_empty</comment>   <fg=gray># MySQL: create empty DB first</>');
            $this->line('  <comment>MKSINE_SETUP_DB_DRIVER=sqlite</comment>');
            $this->line('  <comment>MKSINE_SETUP_DB_DATABASE=mksine_setup.sqlite</comment>   <fg=gray># file under database/</>');
            $this->line('Optional overrides: <comment>MKSINE_SETUP_DB_HOST</comment>, <comment>MKSINE_SETUP_DB_USERNAME</comment>, … (fall back to <comment>DB_*</comment>).');

            return self::FAILURE;
        }

        if ($message = $this->assertSetupDatabaseDistinctFromApp()) {
            $this->error($message);

            return self::FAILURE;
        }

        $targetLabel = $this->setupDatabaseLabel();

        if (! $this->option('force')) {
            if (! confirm(
                "This will drop all tables on the isolated setup database only, then migrate:\n  <fg=yellow>{$targetLabel}</>\n\nYour app default database will NOT be touched. Continue?",
                false
            )) {
                $this->comment('Aborted.');

                return self::SUCCESS;
            }
        }

        // Avoid `migrate:fresh` / `db:wipe`: in production, AppServiceProvider often calls
        // `DB::prohibitDestructiveCommands(true)`, which blocks those Artisan commands globally.
        // Dropping tables only on `mksine_setup` and running `migrate` is not prohibited.
        $this->info('Resetting schema on connection '.self::SETUP_CONNECTION.'…');
        Schema::connection(self::SETUP_CONNECTION)->dropAllTables();

        $this->info('Running migrations…');
        $exit = $this->call('migrate', [
            '--database' => self::SETUP_CONNECTION,
            '--force' => true,
        ]);
        if ($exit !== self::SUCCESS) {
            return self::FAILURE;
        }

        $panelId = (string) $this->option('panel');
        if ($panelId === '') {
            $panelId = 'admin';
        }

        Filament::setCurrentPanel($panelId);

        $name = $this->option('name');
        if ($name === null || $name === '') {
            $name = text(label: 'Super admin name', required: true);
        }

        $email = $this->option('email');
        if ($email === null || $email === '') {
            $email = text(
                label: 'Super admin email',
                required: true,
                validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? null
                    : 'Enter a valid email address.',
            );
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid --email.');

            return self::FAILURE;
        }

        $plainPassword = $this->option('password');
        if ($plainPassword === null || $plainPassword === '') {
            $plainPassword = password(
                label: 'Super admin password',
                required: true,
                validate: fn (string $value): ?string => strlen($value) < 8
                    ? 'Password must be at least 8 characters.'
                    : null,
            );
        } elseif (strlen($plainPassword) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $userClass = config('auth.providers.users.model');
        if (! is_string($userClass) || ! is_subclass_of($userClass, Model::class)) {
            $this->error('config auth.providers.users.model must be an Eloquent model class.');

            return self::FAILURE;
        }

        $previousDefault = DB::getDefaultConnection();

        try {
            DB::setDefaultConnection(self::SETUP_CONNECTION);

            if ($userClass::query()->where('email', $email)->exists()) {
                $this->error('A user with this email already exists on the setup database.');

                return self::FAILURE;
            }

            $user = $userClass::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $plainPassword,
                'email_verified_at' => now(),
            ]);

            $superAdminRole = Utils::createRole();
            $superAdminRole->syncPermissions(Utils::getPermissionModel()::query()->pluck('id'));

            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->assignRole($superAdminRole);
        } finally {
            DB::setDefaultConnection($previousDefault);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $loginUrl = Filament::getPanel($panelId)?->getLoginUrl() ?? url('/admin/login');

        $path = base_path(self::CREDENTIALS_BASENAME);
        $body = implode("\n", [
            'MKSine — fresh setup database + super admin',
            'Generated: '.now()->toIso8601String(),
            '',
            'Database connection: '.self::SETUP_CONNECTION,
            'Database: '.$targetLabel,
            'App default connection (unchanged): '.$previousDefault,
            '',
            'Panel: '.$panelId,
            'Login URL: '.$loginUrl,
            '',
            'To run the app against this database, point DB_* (or a deploy .env) at the same',
            'credentials/name as MKSINE_SETUP_* — or import a dump of this DB.',
            '',
            'Name: '.$name,
            'Email: '.$email,
            'Password: '.$plainPassword,
            '',
            'Keep this file private; delete it after copying credentials.',
            '',
        ]);
        $exportAbsolute = null;
        if (! $this->option('no-export')) {
            $exportRelative = (string) ($this->option('export') ?? '');
            if ($exportRelative === '') {
                $setupDriver = (string) config('database.connections.'.self::SETUP_CONNECTION.'.driver');
                $exportRelative = $setupDriver === 'sqlite'
                    ? 'mksine-setup-database.sqlite'
                    : 'mksine-setup-database.sql';
            }
            $exportAbsolute = $this->resolveExportPath($exportRelative);

            $this->newLine();
            $this->info('Exporting portable database…');
            if (! $this->exportSetupDatabaseToFile($exportAbsolute)) {
                return self::FAILURE;
            }
            $body .= 'Portable database file: '.$exportAbsolute.PHP_EOL;
            $body .= '(MySQL/MariaDB on server: create an empty DB, then e.g. mysql -u USER -p DBNAME < '.basename($exportAbsolute).')'.PHP_EOL.PHP_EOL;
        }

        File::put($path, $body);

        $this->newLine();
        $this->info('Super admin created on setup database only.');
        $this->line('  Target: '.$targetLabel);
        $this->line('  Login: '.$loginUrl);
        $this->line('  Email: '.$email);
        if ($exportAbsolute !== null) {
            $this->line('  Database file: '.$exportAbsolute);
        }
        $this->newLine();
        $this->comment('Credentials saved to: '.self::CREDENTIALS_BASENAME);

        return self::SUCCESS;
    }

    private function resolveExportPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return base_path('mksine-setup-database.sql');
        }

        if (str_starts_with($path, '/') || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'))) {
            return $path;
        }

        return base_path($path);
    }

    private function exportSetupDatabaseToFile(string $absolutePath): bool
    {
        $config = config('database.connections.'.self::SETUP_CONNECTION);
        if (! is_array($config)) {
            $this->error('Cannot export: missing mksine_setup connection config.');

            return false;
        }

        $driver = (string) ($config['driver'] ?? '');

        DB::purge(self::SETUP_CONNECTION);

        try {
            if ($driver === 'sqlite') {
                $src = (string) ($config['database'] ?? '');
                if ($src === '' || ! is_file($src)) {
                    $this->error('SQLite database file not found: '.$src);

                    return false;
                }

                File::ensureDirectoryExists(dirname($absolutePath));
                File::copy($src, $absolutePath);

                $this->line('  Copied SQLite to: '.$absolutePath);

                return true;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $binary = (string) env('MYSQLDUMP_PATH', 'mysqldump');
                $database = (string) ($config['database'] ?? '');
                if ($database === '') {
                    $this->error('Cannot export: empty database name in mksine_setup config.');

                    return false;
                }

                $command = [
                    $binary,
                    '--single-transaction',
                    '--quick',
                    '--set-charset',
                    '--no-tablespaces',
                    '-h', (string) ($config['host'] ?? '127.0.0.1'),
                    '-P', (string) ($config['port'] ?? '3306'),
                    '-u', (string) ($config['username'] ?? 'root'),
                ];

                $socket = (string) ($config['unix_socket'] ?? '');
                if ($socket !== '') {
                    $command[] = '--socket='.$socket;
                }

                $command[] = $database;

                $process = new Process($command, base_path(), null, null, 600.0);
                $process->run(null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);

                if (! $process->isSuccessful()) {
                    $this->error(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'mysqldump failed.');
                    $this->newLine();
                    $this->comment('Ensure `mysqldump` is installed (MySQL / MariaDB client). Override binary with MYSQLDUMP_PATH in .env if needed.');

                    return false;
                }

                File::ensureDirectoryExists(dirname($absolutePath));
                File::put($absolutePath, $process->getOutput());
                $this->line('  SQL dump written: '.$absolutePath);

                return true;
            }

            $this->error('Export is only implemented for sqlite, mysql, and mariadb.');

            return false;
        } finally {
            DB::reconnect(self::SETUP_CONNECTION);
        }
    }

    private function validateSetupConnection(): ?string
    {
        $config = config('database.connections.'.self::SETUP_CONNECTION);
        if (! is_array($config)) {
            return 'Missing database connection "'.self::SETUP_CONNECTION.'" in config/database.php.';
        }

        $driver = $config['driver'] ?? null;
        if (! is_string($driver) || ! in_array($driver, self::SUPPORTED_SETUP_DRIVERS, true)) {
            return 'Connection '.self::SETUP_CONNECTION.' must use one of: '.implode(', ', self::SUPPORTED_SETUP_DRIVERS).'. Set MKSINE_SETUP_DB_DRIVER if needed.';
        }

        $database = $config['database'] ?? null;
        if (! is_string($database) || $database === '') {
            return 'MKSINE_SETUP_DB_DATABASE is not set or is empty. Set it in .env to a database name (MySQL) or SQLite filename (see .env.example).';
        }

        return null;
    }

    private function assertSetupDatabaseDistinctFromApp(): ?string
    {
        $appConnection = (string) config('database.default');
        $appConfig = config('database.connections.'.$appConnection);
        $setupConfig = config('database.connections.'.self::SETUP_CONNECTION);

        if (! is_array($appConfig) || ! is_array($setupConfig)) {
            return null;
        }

        $appDriver = $appConfig['driver'] ?? '';
        $setupDriver = $setupConfig['driver'] ?? '';

        $appDb = $appConfig['database'] ?? '';
        $setupDb = $setupConfig['database'] ?? '';

        if (! is_string($appDb) || ! is_string($setupDb)) {
            return null;
        }

        if ($appDriver === 'sqlite' && $setupDriver === 'sqlite') {
            if ($appDb !== '' && $setupDb !== '' && $appDb === $setupDb) {
                return 'MKSINE_SETUP_DB_DATABASE must not point to the same SQLite path as your app database.';
            }

            $realApp = @realpath($appDb) ?: null;
            $realSetup = @realpath($setupDb) ?: null;
            if ($realApp !== null && $realSetup !== null && $realApp === $realSetup) {
                return 'MKSINE_SETUP_DB_DATABASE resolves to the same file as your app database. Use a different SQLite file.';
            }

            return null;
        }

        if (in_array($appDriver, ['mysql', 'mariadb'], true) && in_array($setupDriver, ['mysql', 'mariadb'], true)) {
            $appHost = (string) ($appConfig['host'] ?? '');
            $setupHost = (string) ($setupConfig['host'] ?? '');
            $appPort = (string) ($appConfig['port'] ?? '');
            $setupPort = (string) ($setupConfig['port'] ?? '');

            if (strcasecmp($appDb, $setupDb) === 0 && $appHost === $setupHost && $appPort === $setupPort) {
                return 'MKSINE_SETUP_DB_DATABASE must not be the same as DB_DATABASE on the same host/port. Create a separate empty database.';
            }
        }

        return null;
    }

    private function setupDatabaseLabel(): string
    {
        $setup = config('database.connections.'.self::SETUP_CONNECTION);
        if (! is_array($setup)) {
            return self::SETUP_CONNECTION;
        }

        $driver = (string) ($setup['driver'] ?? '');
        $database = (string) ($setup['database'] ?? '');

        return $driver.' / '.$database;
    }
}
