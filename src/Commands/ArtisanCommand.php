<?php

namespace Miran\Mksine\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ArtisanCommand extends Command
{
    public $signature = 'mksine:artisan 
                        {cmd* : The artisan command and its arguments}
                        {--path= : Custom path (ignored, use cmd arguments)}
                        {--namespace= : Custom namespace (ignored, use cmd arguments)}';

    public $description = 'Run artisan commands in the MKSine package context';

    protected $packagePath;

    protected $packageNamespace = 'Miran\\Mksine';

    public function __construct()
    {
        parent::__construct();
        $this->packagePath = dirname(__DIR__, 2);
    }

    public function handle(): int
    {
        $cmdParts = $this->argument('cmd');

        // If first part contains space, it's a quoted command - split it
        if (count($cmdParts) === 1 && str_contains($cmdParts[0], ' ')) {
            $cmdParts = explode(' ', $cmdParts[0]);
        }

        $commandName = $cmdParts[0] ?? null;

        if (! $commandName) {
            $this->error('Please provide a command name.');

            return self::FAILURE;
        }

        // For migration, use --path directly
        if (str_starts_with($commandName, 'make:migration')) {
            return $this->handleMigration($cmdParts);
        }

        // For other generator commands, execute and move files
        if (str_starts_with($commandName, 'make:')) {
            return $this->handleGeneratorCommand($cmdParts, $commandName);
        }

        // For non-generator commands, execute normally
        return $this->call($commandName, array_slice($cmdParts, 1));
    }

    protected function handleMigration(array $cmdParts): int
    {
        $name = $cmdParts[1] ?? null;
        $migrationPath = $this->packagePath . '/database/migrations';

        $options = ['name' => $name, '--path' => $migrationPath];

        // Parse additional options
        foreach (array_slice($cmdParts, 2) as $part) {
            if (str_starts_with($part, '--create=')) {
                $options['--create'] = substr($part, 9);
            } elseif (str_starts_with($part, '--table=')) {
                $options['--table'] = substr($part, 8);
            }
        }

        $this->info("Creating migration in: {$migrationPath}");

        return $this->call('make:migration', $options);
    }

    protected function handleGeneratorCommand(array $cmdParts, string $commandName): int
    {
        $name = $cmdParts[1] ?? null;

        if (! $name) {
            $this->error('Please provide a name for the generated file.');

            return self::FAILURE;
        }

        // Get target path and namespace
        [$targetPath, $namespace] = $this->getTargetPathAndNamespace($commandName, $name);

        // Execute command normally (will create in default location)
        $options = ['name' => $name];

        // Parse flags from all parts
        foreach ($cmdParts as $part) {
            if (in_array($part, ['-m', '--migration'])) {
                $options['--migration'] = true;
            } elseif (in_array($part, ['-f', '--factory'])) {
                $options['--factory'] = true;
            } elseif (in_array($part, ['-s', '--seed'])) {
                $options['--seed'] = true;
            } elseif (in_array($part, ['-r', '--resource'])) {
                $options['--resource'] = true;
            } elseif ($part === '--api') {
                $options['--api'] = true;
            }
        }

        $this->info("Executing: php artisan {$commandName} {$name}");
        $this->info("Will move to: {$targetPath}");

        // Build command string and execute via shell
        $commandString = "php artisan {$commandName} {$name}";
        foreach ($options as $key => $value) {
            if ($key !== 'name' && $value !== false) {
                if (is_bool($value)) {
                    $commandString .= " --{$key}";
                } else {
                    $commandString .= " --{$key}={$value}";
                }
            }
        }

        // Execute via Artisan::call with proper options
        $callOptions = ['name' => $name];
        foreach ($options as $key => $value) {
            if ($key !== 'name' && $value !== false) {
                $callOptions[$key] = $value;
            }
        }

        $result = $this->call($commandName, $callOptions);

        // Move generated files to package location
        if ($result === self::SUCCESS) {
            $this->moveGeneratedFiles($commandName, $name, $targetPath, $namespace);
        }

        return $result;
    }

    protected function getTargetPathAndNamespace(string $commandName, string $name): array
    {
        $basePath = $this->packagePath . '/src';
        $baseNamespace = $this->packageNamespace;

        return match (true) {
            str_starts_with($commandName, 'make:model') => [
                $basePath . '/Models',
                $baseNamespace . '\\Models',
            ],
            str_starts_with($commandName, 'make:controller') => [
                $basePath . '/Http/Controllers',
                $baseNamespace . '\\Http\\Controllers',
            ],
            str_starts_with($commandName, 'make:request') => [
                $basePath . '/Http/Requests',
                $baseNamespace . '\\Http\\Requests',
            ],
            str_starts_with($commandName, 'make:resource') => [
                $basePath . '/Http/Resources',
                $baseNamespace . '\\Http\\Resources',
            ],
            str_starts_with($commandName, 'make:factory') => [
                $this->packagePath . '/database/factories',
                $baseNamespace . '\\Database\\Factories',
            ],
            str_starts_with($commandName, 'make:seeder') => [
                $this->packagePath . '/database/seeders',
                $baseNamespace . '\\Database\\Seeders',
            ],
            str_starts_with($commandName, 'make:test') => [
                $this->packagePath . '/tests',
                $baseNamespace . '\\Tests',
            ],
            str_starts_with($commandName, 'make:command') => [
                $basePath . '/Commands',
                $baseNamespace . '\\Commands',
            ],
            default => [
                $basePath,
                $baseNamespace,
            ],
        };
    }

    protected function moveGeneratedFiles(string $commandName, string $name, string $targetPath, ?string $namespace): void
    {
        $filesystem = new Filesystem;
        $className = Str::studly($name);
        $defaultAppPath = app_path();

        // Ensure target directory exists
        if (! $filesystem->exists($targetPath)) {
            $filesystem->makeDirectory($targetPath, 0755, true);
        }

        // Determine source file based on command type
        $sourceFile = match (true) {
            str_starts_with($commandName, 'make:model') => $defaultAppPath . '/Models/' . $className . '.php',
            str_starts_with($commandName, 'make:controller') => $defaultAppPath . '/Http/Controllers/' . $className . '.php',
            str_starts_with($commandName, 'make:request') => $defaultAppPath . '/Http/Requests/' . $className . '.php',
            str_starts_with($commandName, 'make:resource') => $defaultAppPath . '/Http/Resources/' . $className . '.php',
            str_starts_with($commandName, 'make:factory') => database_path('factories/' . $className . 'Factory.php'),
            str_starts_with($commandName, 'make:seeder') => database_path('seeders/' . $className . '.php'),
            str_starts_with($commandName, 'make:test') => base_path('tests/' . $className . 'Test.php'),
            str_starts_with($commandName, 'make:command') => $defaultAppPath . '/Console/Commands/' . $className . '.php',
            default => null,
        };

        if ($sourceFile && $filesystem->exists($sourceFile)) {
            $targetFile = $targetPath . '/' . basename($sourceFile);

            // Update namespace in file content
            $content = $filesystem->get($sourceFile);
            if ($namespace) {
                // Replace namespace declarations - match "namespace App\Models;" or "namespace App\Something;"
                $content = preg_replace('/namespace\s+App\\\\([^;]+);/', 'namespace ' . $namespace . ';', $content);
                // Also update use statements if needed (but be careful not to break vendor packages)
                // Only replace App\ namespaces
                $content = preg_replace('/use\s+App\\\\(Models|Http|Console)\\\\([^;]+);/', 'use ' . $namespace . '\\\\$2;', $content);
            }

            $filesystem->put($targetFile, $content);
            $filesystem->delete($sourceFile);

            $this->info("Moved to: {$targetFile}");
        }

        // Also move migration if it was created with model
        if (str_starts_with($commandName, 'make:model')) {
            $migrationPath = database_path('migrations');
            $migrationFiles = $filesystem->glob($migrationPath . '/*_create_' . Str::snake(Str::plural($name)) . '_table.php');

            if (! empty($migrationFiles)) {
                $packageMigrationPath = $this->packagePath . '/database/migrations';
                if (! $filesystem->exists($packageMigrationPath)) {
                    $filesystem->makeDirectory($packageMigrationPath, 0755, true);
                }

                foreach ($migrationFiles as $migrationFile) {
                    $targetMigration = $packageMigrationPath . '/' . basename($migrationFile);
                    $filesystem->move($migrationFile, $targetMigration);
                    $this->info("Moved migration to: {$targetMigration}");
                }
            }
        }
    }
}
