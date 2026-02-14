<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginMakeResourceCommand extends Command
{
    protected $signature = 'mks-plugin:make-resource
                            {plugin : The plugin ID (e.g., my-shop)}
                            {name : The resource name (e.g., Product)}
                            {--model= : The model class name (defaults to resource name)}';

    protected $description = 'Create a new Filament resource for a plugin';

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
        $modelName = $this->option('model') ?: $name;

        // Get plugin manifest
        $manifest = $this->pluginManager->getManifest($pluginId);

        if (! $manifest) {
            $this->error("Plugin '{$pluginId}' not found. Run 'php artisan mks-plugin:discover' first.");

            return self::FAILURE;
        }

        $pluginPath = $manifest->basePath();
        $namespace = $manifest->namespace();

        if (! $namespace) {
            $this->error("Plugin '{$pluginId}' does not have a namespace defined in plugin.php");

            return self::FAILURE;
        }

        // Create resource class
        $this->createResource($pluginPath, $namespace, $name, $modelName);

        // Create resource schemas and tables
        $this->createSchemasAndTables($pluginPath, $namespace, $name);

        // Create pages
        $this->createPages($pluginPath, $namespace, $name);

        $this->newLine();
        $this->info("✅ Resource [{$name}Resource] created successfully!");
        $this->newLine();
        $this->line('Files created:');
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/{$name}Resource.php</comment>");
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/Schemas/{$name}Form.php</comment>");
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/Tables/{$name}Table.php</comment>");
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/Pages/List{$name}s.php</comment>");
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/Pages/Create{$name}.php</comment>");
        $this->line("  <comment>src/Filament/Resources/{$name}Resource/Pages/Edit{$name}.php</comment>");
        $this->newLine();
        $this->line("<fg=gray>Don't forget to create the model at: src/Models/{$modelName}.php</>");

        return self::SUCCESS;
    }

    private function createResource(string $pluginPath, string $namespace, string $name, string $modelName): void
    {
        $resourceDirectory = $pluginPath . "/src/Filament/Resources/{$name}Resource";
        $this->files->ensureDirectoryExists($resourceDirectory);

        $stub = $this->getResourceStub($namespace, $name, $modelName);
        $this->files->put("{$resourceDirectory}/{$name}Resource.php", $stub);
    }

    private function createSchemasAndTables(string $pluginPath, string $namespace, string $name): void
    {
        // Schemas
        $schemasPath = $pluginPath . "/src/Filament/Resources/{$name}Resource/Schemas";
        $this->files->ensureDirectoryExists($schemasPath);
        $formStub = $this->getFormStub($namespace, $name);
        $this->files->put("{$schemasPath}/{$name}Form.php", $formStub);

        // Tables
        $tablesPath = $pluginPath . "/src/Filament/Resources/{$name}Resource/Tables";
        $this->files->ensureDirectoryExists($tablesPath);
        $tableStub = $this->getTableStub($namespace, $name);
        $this->files->put("{$tablesPath}/{$name}Table.php", $tableStub);
    }

    private function createPages(string $pluginPath, string $namespace, string $name): void
    {
        $pagesPath = $pluginPath . "/src/Filament/Resources/{$name}Resource/Pages";
        $this->files->ensureDirectoryExists($pagesPath);

        // List page
        $listStub = $this->getListPageStub($namespace, $name);
        $this->files->put("{$pagesPath}/List{$name}s.php", $listStub);

        // Create page
        $createStub = $this->getCreatePageStub($namespace, $name);
        $this->files->put("{$pagesPath}/Create{$name}.php", $createStub);

        // Edit page
        $editStub = $this->getEditPageStub($namespace, $name);
        $this->files->put("{$pagesPath}/Edit{$name}.php", $editStub);
    }

    private function getResourceStub(string $namespace, string $name, string $modelName): string
    {
        $pluralName = Str::plural($name);
        $lowerPluralName = Str::lower($pluralName);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource;

use BackedEnum;
use Filament\\Resources\\Resource;
use Filament\\Schemas\\Schema;
use Filament\\Tables\\Table;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\Pages;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\Schemas\\{$name}Form;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\Tables\\{$name}Table;
use {$namespace}\\Models\\{$modelName};

class {$name}Resource extends Resource
{
    protected static ?string \$model = {$modelName}::class;

    protected static ?string \$slug = '{$lowerPluralName}';

    protected static string|BackedEnum|null \$navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('{$pluralName}');
    }

    public static function getModelLabel(): string
    {
        return __('{$name}');
    }

    public static function getPluralModelLabel(): string
    {
        return __('{$pluralName}');
    }

    public static function form(Schema \$schema): Schema
    {
        return {$name}Form::configure(\$schema);
    }

    public static function table(Table \$table): Table
    {
        return {$name}Table::configure(\$table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\\List{$pluralName}::route('/'),
            'create' => Pages\\Create{$name}::route('/create'),
            'edit' => Pages\\Edit{$name}::route('/{record}/edit'),
        ];
    }
}
PHP;
    }

    private function getFormStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource\\Schemas;

use Filament\\Forms\\Components\\TextInput;
use Filament\\Schemas\\Components\\Section;
use Filament\\Schemas\\Schema;
use Miran\\Mksine\\Core\\Hooks\\FormHookManager;

class {$name}Form
{
    public static function configure(Schema \$schema): Schema
    {
        \$schema = \$schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);

        // Apply form hooks
        \$formHookManager = app(FormHookManager::class);

        return \$formHookManager->apply('{$name}.form', \$schema);
    }
}
PHP;
    }

    private function getTableStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource\\Tables;

use Filament\\Actions\\DeleteAction;
use Filament\\Actions\\EditAction;
use Filament\\Actions\\DeleteBulkAction;
use Filament\\Actions\\BulkActionGroup;
use Filament\\Tables\\Columns\\TextColumn;
use Filament\\Tables\\Table;
use Miran\\Mksine\\Core\\Hooks\\TableHookManager;

class {$name}Table
{
    public static function configure(Table \$table): Table
    {
        \$table = \$table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);

        // Apply table hooks
        \$hookManager = app(TableHookManager::class);

        return \$hookManager->apply('{$name}.table', \$table);
    }
}
PHP;
    }

    private function getListPageStub(string $namespace, string $name): string
    {
        $pluralName = Str::plural($name);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource\\Pages;

use Miran\\Mksine\\Filament\\Resources\\Pages\\MksineListRecords;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\{$name}Resource;

class List{$pluralName} extends MksineListRecords
{
    protected static string \$resource = {$name}Resource::class;
}
PHP;
    }

    private function getCreatePageStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource\\Pages;

use Filament\\Resources\\Pages\\CreateRecord;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\{$name}Resource;

class Create{$name} extends CreateRecord
{
    protected static string \$resource = {$name}Resource::class;
}
PHP;
    }

    private function getEditPageStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Resources\\{$name}Resource\\Pages;

use Filament\\Actions\\DeleteAction;
use Filament\\Resources\\Pages\\EditRecord;
use {$namespace}\\Filament\\Resources\\{$name}Resource\\{$name}Resource;

class Edit{$name} extends EditRecord
{
    protected static string \$resource = {$name}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
PHP;
    }
}
