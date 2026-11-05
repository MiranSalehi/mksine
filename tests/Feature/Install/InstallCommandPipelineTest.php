<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallProfile;
use Miran\Mksine\Core\Install\InstallProgress;
use Miran\Mksine\Core\Install\InstallStepStatus;
use Miran\Mksine\Core\Install\Steps\RunMigrationsStep;
use Miran\Mksine\Core\Install\Steps\WriteEnvironmentStep;
use Miran\Mksine\Models\Theme;
use Symfony\Component\Console\Output\BufferedOutput;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->originalStoragePath = $this->app->storagePath();
    $this->tempRoot = sys_get_temp_dir() . '/mksine-install-pipeline-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tempRoot . '/storage');
    $this->app->useStoragePath($this->tempRoot . '/storage');
    config(['filesystems.links' => []]);
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    (new Filesystem)->deleteDirectory($this->tempRoot);
});

it('writes the lock file once a migrated install finishes', function (): void {
    $exit = Artisan::call('mksine:install', ['--migrate' => true, '--profile' => 'vps']);
    $state = app(InstallationState::class);

    expect($exit)->toBe(0)
        ->and($state->hasLockFile())->toBeTrue()
        ->and($state->metadata())->toMatchArray(['profile' => 'vps'])
        ->and(app(InstallProgress::class)->exists())->toBeFalse();
});

it('does not write the lock file when migrations were not requested', function (): void {
    $exit = Artisan::call('mksine:install');

    expect($exit)->toBe(0)
        ->and(app(InstallationState::class)->hasLockFile())->toBeFalse();
});

it('activates the requested theme', function (): void {
    $exit = Artisan::call('mksine:install', ['--migrate' => true, '--theme' => 'mksine']);

    expect($exit)->toBe(0)
        ->and(Theme::active()?->identifier)->toBe('mksine');
});

it('fails with a non-zero exit code and resumes from the failed step', function (): void {
    $output = new BufferedOutput;
    $exit = Artisan::call('mksine:install', ['--migrate' => true, '--theme' => 'does-not-exist'], $output);
    $progress = app(InstallProgress::class);

    expect($exit)->toBe(1)
        ->and($output->fetch())->toContain('does-not-exist')
        ->and($progress->lastFailure())->toMatchArray(['step' => 'theme'])
        ->and($progress->isCompleted('migrate'))->toBeTrue()
        ->and(app(InstallationState::class)->isInstalled())->toBeFalse();

    $resumed = Artisan::call('mksine:install', ['--migrate' => true, '--resume' => true, '--theme' => 'mksine'], $output);

    expect($resumed)->toBe(0)
        ->and($output->fetch())->not->toContain(__('mksine::install.steps.migrate'))
        ->and(app(InstallationState::class)->hasLockFile())->toBeTrue();
});

it('fails on an unknown plugin', function (): void {
    $exit = Artisan::call('mksine:install', ['--migrate' => true, '--plugins' => 'no-such-plugin']);

    expect($exit)->toBe(1)
        ->and(app(InstallProgress::class)->lastFailure())->toMatchArray(['step' => 'plugins']);
});

it('rejects an unknown profile', function (): void {
    expect(fn () => Artisan::call('mksine:install', ['--profile' => 'cloud']))->toThrow(InvalidArgumentException::class);
});

it('writes requested values to .env and applies them to the running config', function (): void {
    $originalEnvironmentPath = $this->app->environmentPath();
    file_put_contents($this->tempRoot . '/.env.example', "APP_NAME=Laravel\nAPP_URL=http://localhost\n");
    $this->app->useEnvironmentPath($this->tempRoot);

    try {
        $result = app(WriteEnvironmentStep::class)->run(new InstallContext(environment: ['APP_URL' => 'https://example.test', 'APP_NAME' => 'My Site']));
        $written = (string) file_get_contents($this->tempRoot . '/.env');
    } finally {
        $this->app->useEnvironmentPath($originalEnvironmentPath);
    }

    expect($result->status)->toBe(InstallStepStatus::Completed)
        ->and($written)->toContain('APP_URL=https://example.test')
        ->and($written)->toContain("APP_NAME='My Site'")
        ->and($written)->toMatch('/^APP_KEY=base64:/m')
        ->and(config('app.url'))->toBe('https://example.test');
});

it('runs migrations in chunks on shared hosting', function (): void {
    $migrations = $this->tempRoot . '/migrations';
    (new Filesystem)->ensureDirectoryExists($migrations);

    foreach (['2099_01_01_000001_create_install_chunk_one', '2099_01_01_000002_create_install_chunk_two'] as $index => $name) {
        $table = 'install_chunk_' . ($index + 1);
        file_put_contents($migrations . "/{$name}.php", <<<PHP
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$table}', fn (Blueprint \$table) => \$table->id());
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        };
        PHP);
    }

    app('migrator')->path($migrations);

    $step = new class(app('migrator')) extends RunMigrationsStep
    {
        protected function timeBudget(): float
        {
            return 0.0;
        }
    };
    $context = new InstallContext(profile: InstallProfile::Shared);

    $first = $step->run($context);

    expect($first->status)->toBe(InstallStepStatus::Incomplete)
        ->and(Schema::hasTable('install_chunk_1'))->toBeTrue()
        ->and(Schema::hasTable('install_chunk_2'))->toBeFalse();

    $second = $step->run($context);

    expect($second->status)->toBe(InstallStepStatus::Completed)
        ->and(Schema::hasTable('install_chunk_2'))->toBeTrue();
});

it('creates the admin without a name when running non-interactively', function (): void {
    $exit = Artisan::call('mksine:install', [
        '--migrate' => true,
        '--no-interaction' => true,
        '--admin-email' => 'noname@example.com',
        '--admin-password' => 'password123',
    ]);
    $userClass = (string) config('auth.providers.users.model');

    expect($exit)->toBe(0)
        ->and($userClass::query()->where('email', 'noname@example.com')->value('name'))->toBe('Admin');
});
