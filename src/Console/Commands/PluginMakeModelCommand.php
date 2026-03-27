<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginMakeModelCommand extends Command
{
    protected $signature = 'mks-plugin:make-model
                            {plugin : The plugin ID (e.g., mks-booking)}
                            {name : The model class name (e.g., Appointment)}
                            {--m|migration : Also create a migration in the plugin database/migrations folder}';

    protected $description = 'Create an Eloquent model under the plugin src/Models directory';

    public function __construct(
        private readonly Filesystem $files,
        private readonly PluginManager $pluginManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $pluginId = $this->argument('plugin');
        $name = $this->argument('name');
        $name = Str::studly($name);

        $manifest = $this->pluginManager->getManifest($pluginId);

        if (! $manifest) {
            $this->error("Plugin '{$pluginId}' not found. Run 'php artisan mks-plugin:discover' first.");

            return self::FAILURE;
        }

        $namespace = $manifest->namespace();

        if (! $namespace) {
            $this->error("Plugin '{$pluginId}' does not have a namespace defined in plugin.php");

            return self::FAILURE;
        }

        $pluginPath = $manifest->basePath();
        $modelsDir = $pluginPath.'/src/Models';
        $this->files->ensureDirectoryExists($modelsDir);

        $modelPath = "{$modelsDir}/{$name}.php";

        if ($this->files->exists($modelPath)) {
            $this->error("Model already exists: {$modelPath}");

            return self::FAILURE;
        }

        $table = $this->inferTableName($pluginId, $name);

        $stub = $this->getModelStub($namespace, $name, $table);
        $this->files->put($modelPath, $stub);

        $this->info("✅ Model [{$name}] created at src/Models/{$name}.php");
        $this->line("   <fg=gray>Table: {$table}</>");

        if ($this->option('migration')) {
            $this->createMigration($pluginPath, $table);
        }

        return self::SUCCESS;
    }

    /**
     * Convention: mks_{plugin_id_underscore}_{snake_plural_of_model}
     * Example: mks-booking + Appointment → mks_booking_appointments
     */
    protected function inferTableName(string $pluginId, string $modelClassName): string
    {
        $pluginPart = str_replace('-', '_', $pluginId);
        $tableSuffix = Str::snake(Str::pluralStudly($modelClassName));

        return "mks_{$pluginPart}_{$tableSuffix}";
    }

    private function getModelStub(string $namespace, string $name, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Models;

use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        //
    ];

    protected function casts(): array
    {
        return [
            //
        ];
    }
}
PHP;
    }

    private function createMigration(string $pluginPath, string $table): void
    {
        $migrationsDir = $pluginPath.'/database/migrations';

        if (! $this->files->isDirectory($migrationsDir)) {
            $this->files->makeDirectory($migrationsDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$table}_table.php";
        $path = "{$migrationsDir}/{$fileName}";

        $stub = $this->getMigrationStub($table);
        $this->files->put($path, $stub);

        $this->info("✅ Migration created: database/migrations/{$fileName}");
    }

    private function getMigrationStub(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
    }
}
