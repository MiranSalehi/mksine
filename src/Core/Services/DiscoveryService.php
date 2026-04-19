<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Service responsible for discovering and registering hook listeners.
 *
 * This service handles:
 * - Discovering event listeners, form hooks, and table hooks
 * - Syncing discovered hooks with the database
 * - Caching discovery results for performance
 *
 * Responsibilities:
 * - File system scanning and reflection
 * - Database synchronization
 * - Cache management
 */
class DiscoveryService
{
    /**
     * Discover all listeners and hooks from the given directory.
     *
     * @param  string  $listenersPath  Path to scan for listeners
     * @return array{listeners: array, form_hooks: array, table_hooks: array}
     */
    public function discoverAll(string $listenersPath): array
    {
        if (! is_dir($listenersPath)) {
            return [
                'listeners' => [],
                'form_hooks' => [],
                'table_hooks' => [],
            ];
        }

        // Optimize: Scan files once and reuse
        $phpFiles = $this->getPhpFiles($listenersPath);

        return [
            'listeners' => $this->discoverListeners($listenersPath, $phpFiles),
            'form_hooks' => $this->discoverFormHooks($listenersPath, $phpFiles),
            'table_hooks' => $this->discoverTableHooks($listenersPath, $phpFiles),
        ];
    }

    /**
     * Get all PHP files from the given directory (cached for performance).
     *
     * @return array<string> Array of file paths
     */
    private function getPhpFiles(string $path): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $file) {
            $files[] = $file[0];
        }

        return $files;
    }

    /**
     * Discover listener classes in the given directory.
     *
     * @param  array<string>|null  $phpFiles  Pre-scanned PHP files (for optimization)
     * @return array<array{event_name: string, listener_class: string, priority: int}>
     */
    public function discoverListeners(string $path, ?array $phpFiles = null): array
    {
        $listeners = [];

        // Use provided file list or scan if not provided (backward compatibility)
        if ($phpFiles === null) {
            $phpFiles = $this->getPhpFiles($path);
        }

        foreach ($phpFiles as $filePath) {
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

                // Determine event name: try interface method first, then fallback to pattern matching
                $eventName = $this->determineEventName($fullClassName, $instance);
                $priority = $instance->priority();

                $listeners[] = [
                    'event_name' => $eventName,
                    'listener_class' => $fullClassName,
                    'priority' => $priority,
                ];
            } catch (\Exception $e) {
                // Skip if instantiation fails
                continue;
            }
        }

        return $listeners;
    }

    /**
     * Discover form hook listeners in the given directory.
     *
     * @param  array<string>|null  $phpFiles  Pre-scanned PHP files (for optimization)
     * @return array<string, string> Array of [formName => listenerClass]
     */
    public function discoverFormHooks(string $path, ?array $phpFiles = null): array
    {
        $hooks = [];

        // Use provided file list or scan if not provided (backward compatibility)
        if ($phpFiles === null) {
            $phpFiles = $this->getPhpFiles($path);
        }

        foreach ($phpFiles as $filePath) {
            $className = $this->getClassNameFromFile($filePath);

            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);

            if (! $fullClassName) {
                continue;
            }

            // Try to autoload if class doesn't exist
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            if (! class_exists($fullClassName)) {
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
            } catch (\Exception $e) {
                // Skip if getFormName() fails
                continue;
            }
        }

        return $hooks;
    }

    /**
     * Discover table hook listeners in the given directory.
     *
     * @param  array<string>|null  $phpFiles  Pre-scanned PHP files (for optimization)
     * @return array<string, string> Array of [tableName => listenerClass]
     */
    public function discoverTableHooks(string $path, ?array $phpFiles = null): array
    {
        $hooks = [];

        // Use provided file list or scan if not provided (backward compatibility)
        if ($phpFiles === null) {
            $phpFiles = $this->getPhpFiles($path);
        }

        foreach ($phpFiles as $filePath) {
            $className = $this->getClassNameFromFile($filePath);

            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);

            if (! $fullClassName) {
                continue;
            }

            // Try to autoload if class doesn't exist
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            if (! class_exists($fullClassName)) {
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
            } catch (\Exception $e) {
                // Skip if getTableName() fails
                continue;
            }
        }

        return $hooks;
    }

    /**
     * Sync discovered listeners with database.
     *
     * @param  array<array{event_name: string, listener_class: string, priority: int}>  $listeners
     * @return int Number of synced listeners
     */
    public function syncListeners(array $listeners): int
    {
        if (! $this->isDatabaseAvailable()) {
            return 0;
        }

        return DB::transaction(function () use ($listeners) {
            $synced = 0;

            foreach ($listeners as $listener) {
                try {
                    $existing = DB::table('mks_hooks')
                        ->where('listener_class', $listener['listener_class'])
                        ->first();

                    if ($existing) {
                        // Update only if priority changed (don't overwrite is_enabled)
                        if ($existing->priority !== $listener['priority']) {
                            DB::table('mks_hooks')
                                ->where('listener_class', $listener['listener_class'])
                                ->update(['priority' => $listener['priority']]);
                            $synced++;
                        }
                    } else {
                        // Insert new listener
                        DB::table('mks_hooks')->insert([
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
                } catch (\Exception $e) {
                    // Log in debug mode or when explicitly enabled
                    if (config('app.debug') || config('mksine.log_hook_errors', false)) {
                        Log::warning('MKS CMS: Failed to sync listener', [
                            'event_name' => $listener['event_name'] ?? 'unknown',
                            'listener_class' => $listener['listener_class'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Skip on error and continue
                    continue;
                }
            }

            return $synced;
        });
    }

    /**
     * Sync discovered form hooks with database.
     *
     * @param  array<string, string>  $hooks  Array of [formName => listenerClass]
     * @return int Number of synced hooks
     */
    public function syncFormHooks(array $hooks): int
    {
        if (! $this->isDatabaseAvailable()) {
            return 0;
        }

        return DB::transaction(function () use ($hooks) {
            $synced = 0;

            foreach ($hooks as $formName => $listenerClass) {
                try {
                    $existing = DB::table('mks_hooks')
                        ->where('listener_class', $listenerClass)
                        ->where('hook_type', 'form')
                        ->first();

                    if ($existing) {
                        // Update if hook_name changed
                        if ($existing->hook_name !== $formName) {
                            DB::table('mks_hooks')
                                ->where('listener_class', $listenerClass)
                                ->where('hook_type', 'form')
                                ->update(['hook_name' => $formName]);
                            $synced++;
                        }
                    } else {
                        // Insert new hook
                        DB::table('mks_hooks')->insert([
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
                } catch (\Exception $e) {
                    // Log in debug mode or when explicitly enabled
                    if (config('app.debug') || config('mksine.log_hook_errors', false)) {
                        Log::warning('MKS CMS: Failed to sync form hook', [
                            'form_name' => $formName,
                            'listener_class' => $listenerClass,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Skip on error and continue
                    continue;
                }
            }

            return $synced;
        });
    }

    /**
     * Sync discovered table hooks with database.
     *
     * @param  array<string, string>  $hooks  Array of [tableName => listenerClass]
     * @return int Number of synced hooks
     */
    public function syncTableHooks(array $hooks): int
    {
        if (! $this->isDatabaseAvailable()) {
            return 0;
        }

        return DB::transaction(function () use ($hooks) {
            $synced = 0;

            foreach ($hooks as $tableName => $listenerClass) {
                try {
                    $existing = DB::table('mks_hooks')
                        ->where('listener_class', $listenerClass)
                        ->where('hook_type', 'table')
                        ->first();

                    if ($existing) {
                        // Update if hook_name changed
                        if ($existing->hook_name !== $tableName) {
                            DB::table('mks_hooks')
                                ->where('listener_class', $listenerClass)
                                ->where('hook_type', 'table')
                                ->update(['hook_name' => $tableName]);
                            $synced++;
                        }
                    } else {
                        // Insert new hook
                        DB::table('mks_hooks')->insert([
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
                } catch (\Exception $e) {
                    // Log in debug mode or when explicitly enabled
                    if (config('app.debug') || config('mksine.log_hook_errors', false)) {
                        Log::warning('MKS CMS: Failed to sync table hook', [
                            'table_name' => $tableName,
                            'listener_class' => $listenerClass,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Skip on error and continue
                    continue;
                }
            }

            return $synced;
        });
    }

    /**
     * Check if database is available.
     */
    private function isDatabaseAvailable(): bool
    {
        try {
            if (! Schema::hasTable('mks_hooks')) {
                return false;
            }

            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
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
        if (preg_match('/^\s*(?:(?:abstract|final)\s+)?class\s+(\w+)(?:\s+extends|\s+implements|\s*\{|\s*$)/m', $content, $matches)) {
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
            $fullClassName = $matches[1].'\\'.$className;

            // Try to autoload the class
            if (! class_exists($fullClassName)) {
                spl_autoload_call($fullClassName);
            }

            // Verify class exists
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }
        }

        return null;
    }

    /**
     * Determine event name from listener class.
     * Uses namespace and class name conventions to determine the event.
     */
    private function determineEventName(string $listenerClass, MksineListenerInterface $instance): string
    {
        // Improved discovery: Try multiple methods for better reliability

        // Method 1: Check if listener has a static method to explicitly define event name
        if (method_exists($listenerClass, 'getEventName')) {
            try {
                $explicitEventName = $listenerClass::getEventName();
                if (! empty($explicitEventName) && is_string($explicitEventName)) {
                    return $explicitEventName;
                }
            } catch (\Exception $e) {
                // Fall through to pattern matching
            }
        }

        // Method 2: Pattern matching from namespace and class name (original method)
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
                'categories' => 'category',
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
}
