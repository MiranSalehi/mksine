<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Command to discover and sync listeners with the database.
 */
class DiscoverHooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mks:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and sync hook listeners with the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Discovering hook listeners...');

        $listenersPath = __DIR__ . '/../../Core/Listeners';

        if (! is_dir($listenersPath)) {
            $this->error("Listeners directory not found: {$listenersPath}");

            return self::FAILURE;
        }

        $discoveredListeners = $this->discoverListeners($listenersPath);
        $discoveredFormHooks = $this->discoverFormHooks($listenersPath);
        $discoveredTableHooks = $this->discoverTableHooks($listenersPath);

        if (empty($discoveredListeners) && empty($discoveredFormHooks) && empty($discoveredTableHooks)) {
            $this->warn('No listeners or hooks found.');

            return self::SUCCESS;
        }

        // Sync event listeners to database
        if (! empty($discoveredListeners)) {
            $this->info('Found ' . count($discoveredListeners) . ' event listener(s).');
            $synced = $this->syncListeners($discoveredListeners);
            $this->info("Synced {$synced} event listener(s) with database.");
        }

        // Sync form hooks to database
        if (! empty($discoveredFormHooks)) {
            $this->info('Found ' . count($discoveredFormHooks) . ' form hook(s).');
            $synced = $this->syncFormHooks($discoveredFormHooks);
            $this->info("Synced {$synced} form hook(s) with database.");
        }

        // Sync table hooks to database
        if (! empty($discoveredTableHooks)) {
            $this->info('Found ' . count($discoveredTableHooks) . ' table hook(s).');
            $synced = $this->syncTableHooks($discoveredTableHooks);
            $this->info("Synced {$synced} table hook(s) with database.");
        }

        return self::SUCCESS;
    }

    /**
     * Discover listener classes in the given directory.
     *
     * @return array<string, array{event_name: string, listener_class: string, priority: int}>
     */
    private function discoverListeners(string $path): array
    {
        $listeners = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $file) {
            $filePath = $file[0];
            $className = $this->getClassNameFromFile($filePath);

            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);

            if (! $fullClassName || ! class_exists($fullClassName)) {
                continue;
            }

            // Check if class implements MksineListenerInterface
            $reflection = new \ReflectionClass($fullClassName);

            if (! $reflection->implementsInterface(MksineListenerInterface::class)) {
                continue;
            }

            // Instantiate to get event name and priority
            try {
                $instance = app()->make($fullClassName);

                if (! $instance instanceof MksineListenerInterface) {
                    continue;
                }

                // Determine event name from listener class name or namespace
                $eventName = $this->determineEventName($fullClassName, $instance);
                $priority = $instance->priority();

                $listeners[] = [
                    'event_name' => $eventName,
                    'listener_class' => $fullClassName,
                    'priority' => $priority,
                ];
            } catch (\Exception $e) {
                $this->warn("Could not instantiate listener {$fullClassName}: {$e->getMessage()}");

                continue;
            }
        }

        return $listeners;
    }

    /**
     * Get class name from PHP file.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Match class declaration, but exclude comments and strings
        // Pattern: class ClassName (with optional extends/implements)
        // Must be on its own line or after namespace/use statements
        if (preg_match('/^\s*class\s+(\w+)(?:\s+extends|\s+implements|\s*\{|\s*$)/m', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get full class name from file path.
     */
    private function getFullClassName(string $filePath, string $className): ?string
    {
        $content = file_get_contents($filePath);

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $fullClassName = $matches[1] . '\\' . $className;

            // Try to autoload the class
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            // Verify class exists
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }

            $this->warn("Class '{$fullClassName}' not found after autoload");
        }

        return null;
    }

    /**
     * Determine event name from listener class.
     * Uses namespace and class name conventions to determine the event.
     */
    private function determineEventName(string $listenerClass, MksineListenerInterface $instance): string
    {
        // Extract namespace parts
        $parts = explode('\\', $listenerClass);

        // Look for event-related namespace segments
        // e.g., ...\Listeners\Posts\... -> post.*
        // e.g., ...\Listeners\Media\... -> media.*
        // e.g., ...\Core\Listeners\... -> try to infer from class name

        $listenerIndex = array_search('Listeners', $parts);
        $eventPrefix = 'post'; // Default prefix

        if ($listenerIndex !== false && isset($parts[$listenerIndex + 1])) {
            $category = strtolower($parts[$listenerIndex + 1]);

            // Map category to event prefix
            $eventPrefix = match ($category) {
                'posts' => 'post',
                'media' => 'media',
                'plugins' => 'plugin',
                'themes' => 'theme',
                default => 'post', // Default to post for Core\Listeners
            };
        }

        // Try to determine event suffix from class name
        $className = class_basename($listenerClass);

        // Check for common patterns in class name
        // Direct mapping: PostCreatingListener -> post.creating
        // Pattern: *Creating* or *CreatingListener -> .creating
        if (str_contains($className, 'Creating')) {
            return "{$eventPrefix}.creating";
        }
        // Pattern: *Created* or *CreatedListener -> .created
        if (str_contains($className, 'Created')) {
            return "{$eventPrefix}.created";
        }
        // Pattern: *Updating* or *UpdatingListener -> .updating
        if (str_contains($className, 'Updating')) {
            return "{$eventPrefix}.updating";
        }
        // Pattern: *Updated* or *UpdatedListener -> .updated
        if (str_contains($className, 'Updated')) {
            return "{$eventPrefix}.updated";
        }
        // Pattern: *Deleting* or *DeletingListener -> .deleting
        if (str_contains($className, 'Deleting')) {
            return "{$eventPrefix}.deleting";
        }
        // Pattern: *Deleted* or *DeletedListener -> .deleted
        if (str_contains($className, 'Deleted')) {
            return "{$eventPrefix}.deleted";
        }
        // Pattern: *Publishing* or *PublishingListener -> .publishing
        if (str_contains($className, 'Publishing')) {
            return "{$eventPrefix}.publishing";
        }

        // For listeners in Core\Listeners, default to post.creating
        // This can be manually corrected in the database
        return "{$eventPrefix}.creating";
    }

    /**
     * Sync discovered listeners with database.
     *
     * @param  array<array{event_name: string, listener_class: string, priority: int}>  $listeners
     * @return int Number of synced listeners
     */
    private function syncListeners(array $listeners): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mks_hooks')) {
                $this->warn('mks_hooks table does not exist. Please run migrations first.');

                return 0;
            }
        } catch (\Exception $e) {
            $this->warn('Database connection error: ' . $e->getMessage());

            return 0;
        }

        $synced = 0;

        foreach ($listeners as $listener) {
            try {
                $existing = \Illuminate\Support\Facades\DB::table('mks_hooks')
                    ->where('listener_class', $listener['listener_class'])
                    ->first();

                if ($existing) {
                    // Update only if priority changed (don't overwrite is_enabled)
                    if ($existing->priority !== $listener['priority']) {
                        \Illuminate\Support\Facades\DB::table('mks_hooks')
                            ->where('listener_class', $listener['listener_class'])
                            ->update(['priority' => $listener['priority']]);
                        $synced++;
                    }
                } else {
                    // Insert new listener
                    \Illuminate\Support\Facades\DB::table('mks_hooks')->insert([
                        'hook_type' => 'event',
                        'event_name' => $listener['event_name'],
                        'hook_name' => null,
                        'listener_class' => $listener['listener_class'],
                        'priority' => $listener['priority'],
                        'is_enabled' => true,
                        'is_system' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $synced++;
                }

                $this->line("  - Synced event listener: {$listener['listener_class']} for event {$listener['event_name']}");
            } catch (\Exception $e) {
                $this->warn("Failed to sync listener {$listener['listener_class']}: " . $e->getMessage());
            }
        }

        return $synced;
    }

    /**
     * Discover form hook listeners in the given directory.
     *
     * @return array<string, string> Array of [formName => listenerClass]
     */
    private function discoverFormHooks(string $path): array
    {
        $hooks = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $file) {
            $filePath = $file[0];
            $className = $this->getClassNameFromFile($filePath);

            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);

            if (! $fullClassName) {
                $this->warn("Could not determine full class name for {$className} in {$filePath}");

                continue;
            }

            // Try to autoload if class doesn't exist
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            if (! class_exists($fullClassName)) {
                $this->warn("Class '{$fullClassName}' not found after autoload");

                continue;
            }

            // Check if class implements FormHookListenerInterface
            $reflection = new \ReflectionClass($fullClassName);

            if (! $reflection->implementsInterface(FormHookListenerInterface::class)) {
                continue;
            }

            // Get form name from static method
            try {
                $formName = $fullClassName::getFormName();
                $hooks[$formName] = $fullClassName;
                $this->line("  - Found form hook: {$fullClassName} for form '{$formName}'");
            } catch (\Exception $e) {
                $this->warn("Could not get form name from {$fullClassName}: {$e->getMessage()}");

                continue;
            }
        }

        return $hooks;
    }

    /**
     * Discover table hook listeners in the given directory.
     *
     * @return array<string, string> Array of [tableName => listenerClass]
     */
    private function discoverTableHooks(string $path): array
    {
        $hooks = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $file) {
            $filePath = $file[0];
            $className = $this->getClassNameFromFile($filePath);

            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);

            if (! $fullClassName) {
                $this->warn("Could not determine full class name for {$className} in {$filePath}");

                continue;
            }

            // Try to autoload if class doesn't exist
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            if (! class_exists($fullClassName)) {
                $this->warn("Class '{$fullClassName}' not found after autoload");

                continue;
            }

            // Check if class implements TableHookListenerInterface
            $reflection = new \ReflectionClass($fullClassName);

            if (! $reflection->implementsInterface(TableHookListenerInterface::class)) {
                continue;
            }

            // Get table name from static method
            try {
                $tableName = $fullClassName::getTableName();
                $hooks[$tableName] = $fullClassName;
                $this->line("  - Found table hook: {$fullClassName} for table '{$tableName}'");
            } catch (\Exception $e) {
                $this->warn("Could not get table name from {$fullClassName}: {$e->getMessage()}");

                continue;
            }
        }

        return $hooks;
    }

    /**
     * Sync discovered form hooks with database.
     *
     * @param  array<string, string>  $hooks  Array of [formName => listenerClass]
     * @return int Number of synced hooks
     */
    private function syncFormHooks(array $hooks): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mks_hooks')) {
                $this->warn('mks_hooks table does not exist. Please run migrations first.');

                return 0;
            }
        } catch (\Exception $e) {
            $this->warn('Database connection error: ' . $e->getMessage());

            return 0;
        }

        $synced = 0;

        foreach ($hooks as $formName => $listenerClass) {
            try {
                $existing = \Illuminate\Support\Facades\DB::table('mks_hooks')
                    ->where('listener_class', $listenerClass)
                    ->where('hook_type', 'form')
                    ->first();

                if ($existing) {
                    // Update if hook_name changed
                    if ($existing->hook_name !== $formName) {
                        \Illuminate\Support\Facades\DB::table('mks_hooks')
                            ->where('listener_class', $listenerClass)
                            ->where('hook_type', 'form')
                            ->update(['hook_name' => $formName]);
                        $synced++;
                    }
                } else {
                    // Insert new hook
                    \Illuminate\Support\Facades\DB::table('mks_hooks')->insert([
                        'hook_type' => 'form',
                        'event_name' => null,
                        'hook_name' => $formName,
                        'listener_class' => $listenerClass,
                        'priority' => 0,
                        'is_enabled' => true,
                        'is_system' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $synced++;
                }

                $this->line("  - Synced form hook: {$listenerClass} for {$formName}");
            } catch (\Exception $e) {
                $this->warn("Failed to sync form hook {$listenerClass}: " . $e->getMessage());
            }
        }

        return $synced;
    }

    /**
     * Sync discovered table hooks with database.
     *
     * @param  array<string, string>  $hooks  Array of [tableName => listenerClass]
     * @return int Number of synced hooks
     */
    private function syncTableHooks(array $hooks): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mks_hooks')) {
                $this->warn('mks_hooks table does not exist. Please run migrations first.');

                return 0;
            }
        } catch (\Exception $e) {
            $this->warn('Database connection error: ' . $e->getMessage());

            return 0;
        }

        $synced = 0;

        foreach ($hooks as $tableName => $listenerClass) {
            try {
                $existing = \Illuminate\Support\Facades\DB::table('mks_hooks')
                    ->where('listener_class', $listenerClass)
                    ->where('hook_type', 'table')
                    ->first();

                if ($existing) {
                    // Update if hook_name changed
                    if ($existing->hook_name !== $tableName) {
                        \Illuminate\Support\Facades\DB::table('mks_hooks')
                            ->where('listener_class', $listenerClass)
                            ->where('hook_type', 'table')
                            ->update(['hook_name' => $tableName]);
                        $synced++;
                    }
                } else {
                    // Insert new hook
                    \Illuminate\Support\Facades\DB::table('mks_hooks')->insert([
                        'hook_type' => 'table',
                        'event_name' => null,
                        'hook_name' => $tableName,
                        'listener_class' => $listenerClass,
                        'priority' => 0,
                        'is_enabled' => true,
                        'is_system' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $synced++;
                }

                $this->line("  - Synced table hook: {$listenerClass} for {$tableName}");
            } catch (\Exception $e) {
                $this->warn("Failed to sync table hook {$listenerClass}: " . $e->getMessage());
            }
        }

        return $synced;
    }
}
