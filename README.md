# MKSine - Complete Documentation

> **⚠️ Development Status**  
> This package is **under active development Use in production only after thorough testing.**

MKSine is a powerful, extensible Content Management System built as a Filament plugin for Laravel. It provides a robust foundation for content management with a sophisticated hook system that allows deep customization without modifying core code.

### Recently Added Features

- **Page Builder** — Visual drag-and-drop page builder with blocks (heading, text, image, columns, CTA, etc.) and templates.
- **Access Control (Permissions)** — Integrate with Filament Shield or Spatie for roles and permissions; User resource is ready for extension.
- **Theme Management** — Create, activate, and manage themes; npm-based asset build and publish to `public/`.
- **Plugin NPM Build** — Plugins now have the same npm build system as themes: `package.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, `resources/dist/`. `npm run build` inside a plugin compiles CSS/JS, rebuilds the admin panel CSS, and publishes Filament assets automatically.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Architecture](#architecture)
4. [Hook System Deep Dive](#hook-system-deep-dive)
5. [Hooks: Usage, Disabling, and Queue Execution](#hooks-usage-disabling-and-queue-execution)
6. [Creating Events](#creating-events)
7. [Creating Listeners](#creating-listeners)
8. [Hook Types](#hook-types)
9. [Data Mutations](#data-mutations)
10. [Event Prevention](#event-prevention)
11. [Priority System](#priority-system)
12. [Discovery System](#discovery-system)
13. [State Management](#state-management)
14. [Hook Visibility & Plugin Ownership](#hook-visibility--plugin-ownership)
15. [Performance & Caching](#performance--caching)
16. [Best Practices](#best-practices)
17. [API Reference](#api-reference)
18. [Examples](#examples)
19. [Troubleshooting](#troubleshooting)
20. [Plugins: Create Plugin, Resource, Page, Widget](#plugins-create-plugin-resource-page-widget)
21. [Plugins: Composer packages (install and publish)](#plugins-composer-packages-install-and-publish)
22. [Plugin NPM Build & Assets](#plugin-npm-build--assets)
23. [Themes: Create, NPM, Publish Assets](#themes-create-npm-publish-assets)
24. [Settings: Adding a New Tab](#settings-adding-a-new-tab)
25. [Page Builder: Adding a Component](#page-builder-adding-a-component)
26. [Theme Manager](#theme-manager)
27. [Menu Management System](#menu-management-system)
28. [User Management](#user-management)
29. [Plugin Development Tools](#plugin-development-tools)

---

## Overview

MKSine is designed with extensibility and developer experience in mind. It features a comprehensive hook system that enables developers to extend and customize functionality at every level, from content lifecycle events to Filament form and table structures.

### Key Features

- **Page Builder**: Visual page builder with blocks (heading, text, image, columns, hero, CTA, etc.), templates, and extensible component registry
- **Access Control**: User management resource; ready for Filament Shield or Spatie Laravel Permission for roles and permissions
- **Theme Management**: Create themes with `mks:make-theme`, build assets with npm, publish to `public/` with `mks:theme-publish`
- **Filament-First Design**: Built specifically for Filament 4 with deep integration into forms, tables, and resources
- **Deterministic Execution**: All hook executions follow a strict, immutable 8-phase lifecycle that cannot be bypassed
- **System Hook Enforcement**: System-critical hooks cannot be disabled, ensuring core functionality integrity
- **Database-Driven State Management**: Hook state (enabled/disabled, priority overrides) is managed via database
- **Event-Driven Architecture**: Comprehensive event system with BEFORE and AFTER lifecycle events
- **Priority-Based Execution**: Control listener execution order with integer priorities
- **Data Mutation Tracking**: All data changes are tracked and traceable
- **Event Prevention**: BEFORE events can be prevented with custom reasons
- **Filament Integration Hooks**: Extend forms, tables, resources, and pages dynamically
- **Automatic Discovery**: Discover and register hooks via `mks:discover` command
- **Hook Visibility Control**: Public, private, and system-level hook visibility with plugin ownership
- **Plugin Ownership Metadata**: Explicit plugin ownership tracking for all listeners
- **Error Isolation Policy**: Comprehensive error handling with plugin context and mutation reversion

---

## Installation

### Requirements

- PHP 8.1 or higher
- Laravel 11 or higher
- Filament 4
- MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

### Install via Composer

```bash
composer require miran/mksine
```

### Publish and Run Migrations

```bash
php artisan mksine:install --migrate
```

Or manually:

```bash
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-config"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-migrations"
php artisan migrate
```

### Register the Plugin

In your `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Miran\Mksine\MksinePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            MksinePlugin::make(),
        ]);
}
```

### Discover Hooks

After installation, discover and register hooks:

```bash
php artisan mks:discover
```

---

## Architecture

### Core Components

The hook system consists of four main components:

#### 1. HookManager
**Central coordinator** for the hook system. Provides the unified public API.

**Responsibilities:**
- Coordinate registry + state + dispatcher
- Provide public API for registration and dispatch
- Merge listener definitions with runtime state
- Sort listeners by effective priority

#### 2. HookRegistry
**Stores hook definitions** discovered from code.

**Responsibilities:**
- Store listener definitions (event name, listener class, priority)
- Provide lookup by event name
- No database awareness
- Immutable and final (cannot be extended)

#### 3. HookStateRepository
**Manages runtime state** from database.

**Responsibilities:**
- Read enabled/disabled state for listeners
- Read priority overrides
- Read system flags
- Prioritize cache file over database for performance
- Thread-safe state loading with locks

#### 4. HookDispatcher
**Executes hooks** in priority order.

**Responsibilities:**
- Execute listeners in strict priority order
- Handle sync vs async listeners
- Stop execution on prevent
- Collect mutations and async listeners
- Enforce system hooks (MUST execute even if disabled)
- Performance metrics (execution timing)
- Revert mutations from failed listeners

### Database Schema

#### `mks_hooks` Table

```sql
- id (primary key)
- hook_type (string) - 'event', 'form', 'table', 'resource', 'page'
- event_name (string, nullable) - Event name for event hooks
- hook_name (string, nullable) - Hook identifier for form/table hooks
- listener_class (string, unique) - Fully qualified class name
- priority (integer, default: 0) - Execution priority
- is_enabled (boolean, default: true) - Whether hook is enabled
- is_system (boolean, default: false) - Whether hook is system-critical
- created_at, updated_at (timestamps)
```

---

## Hook System Deep Dive

### The 8-Phase Execution Lifecycle

All hook executions follow a strict, immutable 8-phase lifecycle:

#### Phase 1: Event Dispatch
The initial event is created and dispatched to `HookManager`.

```php
$event = new PostCreating($data, $context);
$result = $hookManager->dispatch($event);
```

#### Phase 2: Load Hook Definitions
`HookRegistry` provides listener definitions for the event name. Each definition contains:
- Listener class name
- Default priority (from code)

#### Phase 3: Merge Runtime State
`HookStateRepository` reads state from database:
- **Enabled/disabled state**: Is the listener enabled?
- **Priority overrides**: Has priority been overridden in database?
- **System flags**: Is this a system hook?

#### Phase 4: Sort Listeners by Final Priority
- Filter out disabled listeners (system hooks are never filtered)
- Sort by effective priority (**lower numbers execute first**, e.g., 0 before 10)
- Stable sort ensures deterministic order for equal priorities

#### Phase 5: Execute Synchronous Listeners
For each listener in sorted order:
1. Check if should queue (skip, collect for async)
2. Check if should handle (skip if false)
3. Take snapshot of mutations and data
4. Execute listener
5. If listener fails, revert mutations and continue
6. Check prevention (stop immediately if prevented)

#### Phase 6: Stop Execution Immediately on Prevent
If `event->prevent()` was called:
- All remaining listeners are skipped
- Execution breaks immediately
- No further mutations are allowed

#### Phase 7: Dispatch Async Listeners (If Event Allows)
If `event->isAsyncAllowed()` returns true:
- Process `pendingAsyncListeners` array
- Queue for async execution (handled by external queue system)

#### Phase 8: Return Execution Result
`EventResult` contains:
- `wasPrevented`: Boolean
- `preventReason`: String or null
- `mutations`: Array of all mutations applied
- `pendingAsyncListeners`: Array of listener class names
- `executionTime`: Total execution time in milliseconds

### Lifecycle Guarantees

- **IMMUTABLE**: The lifecycle cannot be bypassed or modified
- **DETERMINISTIC**: Same inputs always produce same execution order
- **THREAD-SAFE**: Concurrent requests are handled safely
- **ERROR-RESILIENT**: Failed listeners don't crash the system

---

## Hooks: Usage, Disabling, and Queue Execution

### How to Use Hooks

1. **Register a listener** (in a service provider or plugin boot):

```php
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\Hooks;

// Via HookManager
app(HookManager::class)->register(
    eventName: 'post.creating',
    listenerClass: GenerateSlugListener::class,
    priority: 10,
    pluginId: 'my-plugin'
);

// Or via Hooks helper
Hooks::register('post.creating', GenerateSlugListener::class, 10, 'my-plugin');
```

2. **Implement the listener** — implement `MksineListenerInterface` with `handle()`, `shouldHandle()`, `shouldQueue()`, and `priority()`.

3. **Dispatch the event** where the action occurs:

```php
$event = new PostCreating($data, $context);
$result = app(HookManager::class)->dispatch($event);
if ($result->wasPrevented()) {
    throw new ValidationException($result->preventReason());
}
foreach ($result->mutations() as $mutation) {
    $data[$mutation['key']] = $mutation['new'];
}
```

4. **Run discovery** so the hook is synced to the database (for state/cache):

```bash
php artisan mks:discover
```

### How to Disable or “Remove” a Hook

Hooks are **not unregistered in code** at runtime; they are **disabled** via the database so that the dispatcher skips them.

- **Disable a listener**: Set `is_enabled = 0` for the row in `mks_hooks` where `listener_class` equals your listener’s class name. System hooks (`is_system = 1`) cannot be disabled.
- **Clear state cache** after DB changes: delete `bootstrap/cache/mks_hook_state.php` or run `php artisan cache:clear`.
- **Permanent removal**: Remove the `register()` call from your code (and from any plugin), then run `php artisan mks:discover` to sync the registry; the row may remain in `mks_hooks` but the listener will no longer be in the registry. You can also delete or disable the row in `mks_hooks`.

### How to Run a Listener in the Queue

1. **Allow async on the event** — override `allowAsync()` in your event class to return `true`:

```php
class PostCreated extends MksineEvent
{
    protected function allowAsync(): bool
    {
        return true;
    }
}
```

2. **Opt in per listener** — in the listener, return `true` from `shouldQueue()`:

```php
public function shouldQueue(): bool
{
    return true;
}
```

3. **Queue configuration** — ensure Laravel queue is configured (e.g. `config/queue.php`, `.env` `QUEUE_CONNECTION`) and, if using the MKSine async dispatcher, that it is bound in the container so the manager can dispatch jobs.

Listeners that return `shouldQueue() === true` are **not** run synchronously; they are collected and dispatched as queued jobs when the event allows async. This keeps heavy work (emails, notifications, external APIs) off the request.

---

## Creating Events

### Event Structure

All events must extend `MksineEvent`:

```php
<?php

namespace App\Hooks\Events;

use Miran\Mksine\Core\Events\MksineEvent;

class PostPublishing extends MksineEvent
{
    /**
     * Get the event name.
     * Convention: resource.action (e.g., 'post.creating', 'post.created')
     */
    public function name(): string
    {
        return 'post.publishing';
    }

    /**
     * Check if this event can be prevented.
     * BEFORE events (creating, updating, deleting) can be prevented.
     * AFTER events (created, updated, deleted) cannot be prevented.
     */
    public function canBePrevented(): bool
    {
        return true; // BEFORE event
    }

    /**
     * Override this method to allow async execution.
     * Default is false (synchronous execution).
     */
    protected function allowAsync(): bool
    {
        return false; // Must run synchronously
    }
}
```

### Event Naming Convention

- **BEFORE events**: Use present continuous tense (e.g., `post.creating`, `post.updating`)
- **AFTER events**: Use past tense (e.g., `post.created`, `post.updated`)

### Event Data

Events receive data and context:

```php
$event = new PostCreating(
    data: [
        'title' => 'My Post',
        'content' => 'Post content...',
        'status' => 'draft',
    ],
    context: [
        'user_id' => Auth::id(),
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]
);
```

### Available Event Methods

```php
// Read-only access to event data
$event->data()->get('title');
$event->data()->has('slug');
$event->data()->all();

// Get event context
$event->context(); // Returns array

// Update event data (creates mutation)
$event->updateData('title', 'New Title');

// Prevent event (only for BEFORE events)
$event->prevent('Validation failed');

// Check prevention status
$event->isPrevented();
$event->preventReason();

// Get all mutations
$event->mutations();
```

---

## Creating Listeners

### Listener Interface

All listeners must implement `MksineListenerInterface`:

```php
<?php

namespace App\Hooks\Listeners;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

class GenerateSlugListener implements MksineListenerInterface
{
    /**
     * Handle the event.
     */
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        
        if ($title && empty($event->data()->get('slug'))) {
            $event->updateData('slug', \Illuminate\Support\Str::slug($title));
        }
    }

    /**
     * Determine if this listener should handle the given event.
     * Useful for conditional execution based on event data or context.
     */
    public function shouldHandle(MksineEvent $event): bool
    {
        // Only handle if title exists and slug is empty
        return $event->data()->has('title') && empty($event->data()->get('slug'));
    }

    /**
     * Determine if this listener should be queued for async execution.
     */
    public function shouldQueue(): bool
    {
        return false; // Run synchronously
    }

    /**
     * Get the priority of this listener.
     * Lower numbers execute first (e.g., 0 before 10).
     * 
     * Common priorities:
     * - 0-9: System-critical (validations, security checks) - Run first
     * - 10-49: Core functionality (slug generation, defaults) - Run early
     * - 50-99: Feature enhancements - Run mid-way
     * - 100+: Nice-to-have features - Run later
     * - 200+: Cleanup, logging, notifications - Run last
     */
    public function priority(): int
    {
        return 10; // Lower number = runs earlier
    }
}
```

### Registering Listeners

#### Method 1: Automatic Discovery

Place listeners in `app/Hooks/Listeners/` and run:

```bash
php artisan mks:discover
```

The discovery system will:
1. Scan for classes implementing `MksineListenerInterface`
2. Extract event name from class name or interface method
3. Sync with database
4. Register with `HookManager`

#### Method 2: Manual Registration

In your `AppServiceProvider`:

```php
use Miran\Mksine\Core\Hooks\HookManager;

public function boot(): void
{
    $hookManager = app(HookManager::class);
    $hookManager->register('post.creating', GenerateSlugListener::class, 50);
}
```

Or using the `Hooks` helper:

```php
use Miran\Mksine\Core\Hooks\Hooks;

public function boot(): void
{
    Hooks::register('post.creating', GenerateSlugListener::class, 50);
}
```

---

## Hook Types

### 1. Event Hooks

Event hooks listen to content lifecycle events (creating, created, updating, updated, etc.).

**Example: Auto-generate slug**

```php
class GenerateSlugListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        if ($title && empty($event->data()->get('slug'))) {
            $event->updateData('slug', Str::slug($title));
        }
    }
    
    // ... other required methods
}
```

### 2. Form Hooks

Form hooks extend Filament forms dynamically.

#### Using Interface

Create a listener implementing `FormHookListenerInterface`:

```php
<?php

namespace App\Hooks\Listeners;

use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;

class AddCustomFieldsToPostForm implements FormHookListenerInterface
{
    public static function getFormName(): string
    {
        return 'post.form';
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public static function extend(Schema $schema): Schema
    {
        $existingComponents = method_exists($schema, 'getComponents') 
            ? $schema->getComponents() 
            : [];
        
        return $schema->components([
            ...$existingComponents,
            Section::make('Custom Fields')
                ->schema([
                    TextInput::make('custom_field')
                        ->label('Custom Field')
                        ->maxLength(255),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }
}
```

#### Using Helper

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

Hooks::extendForm('post.form', function (Schema $schema) {
    $existingComponents = $schema->getComponents() ?? [];
    
    return $schema->components([
        ...$existingComponents,
        Section::make('Custom Fields')
            ->schema([
                TextInput::make('custom_field')
                    ->label('Custom Field'),
            ]),
    ]);
}, priority: 10); // Optional priority
```

### 3. Table Hooks

Table hooks extend Filament tables dynamically.

#### Using Interface

```php
<?php

namespace App\Hooks\Listeners;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;

class AddCustomColumnToPostTable implements TableHookListenerInterface
{
    public static function getTableName(): string
    {
        return 'post.table';
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public static function extend(Table $table): Table
    {
        $existingColumns = method_exists($table, 'getColumns') 
            ? $table->getColumns() 
            : [];
        
        return $table->columns([
            ...$existingColumns,
            TextColumn::make('custom_field')
                ->label('Custom Field')
                ->searchable()
                ->sortable(),
        ]);
    }
}
```

#### Using Helper

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Filament\Tables\Columns\TextColumn;

// Extend entire table
Hooks::extendTable('post.table', function (Table $table) {
    return $table->columns([...]);
});

// Extend columns only
Hooks::extendTableColumns('post.table', function (Table $table) {
    return $table->columns([...]);
});

// Extend actions
Hooks::extendTableActions('post.table', function (Table $table) {
    return $table->actions([...]);
});

// Extend bulk actions
Hooks::extendTableBulkActions('post.table', function (Table $table) {
    return $table->bulkActions([...]);
});

// Extend filters
Hooks::extendTableFilters('post.table', function (Table $table) {
    return $table->filters([...]);
});
```

### 4. Resource Hooks

Resource hooks extend Filament resources (relations, widgets).

```php
// Extend relations
Hooks::extendResourceRelations('post.resource', function (array $relations) {
    return [
        ...$relations,
        'customRelation' => RelationManager::make(),
    ];
});

// Extend widgets
Hooks::extendResourceWidgets('post.resource', function (array $widgets) {
    return [
        ...$widgets,
        PostStatsWidget::class,
    ];
});
```

### 5. Page Hooks

Page hooks extend Filament pages (header actions).

```php
use Filament\Actions\Action;

Hooks::extendPageHeaderActions('post.edit', function (array $actions) {
    return [
        ...$actions,
        Action::make('export')
            ->label('Export')
            ->action(fn () => $this->export()),
    ];
});
```

---

## Data Mutations

### Understanding Mutations

Mutations track all changes made to event data by listeners.

```php
$event->updateData('title', 'New Title');
// Creates a mutation:
// [
//     'key' => 'title',
//     'old' => 'Old Title',
//     'new' => 'New Title'
// ]
```

### Accessing Mutations

```php
$result = $hookManager->dispatch($event);

foreach ($result->mutations() as $mutation) {
    echo "Field '{$mutation['key']}' changed from '{$mutation['old']}' to '{$mutation['new']}'";
}
```

### Applying Mutations

In Filament pages, mutations are automatically applied:

```php
// In CreatePost::mutateFormDataBeforeCreate()
$result = $hookManager->dispatch($event);

// Apply only mutations (prevents data loss)
foreach ($result->mutations() as $mutation) {
    $data[$mutation['key']] = $mutation['new'];
}
```

### Mutation Safety

- **Automatic Revert**: If a listener fails after making mutations, those mutations are automatically reverted
- **Only Successful Mutations**: Only mutations from successfully executed listeners are included in `EventResult`
- **Data Integrity**: Original data is preserved if all listeners fail

---

## Event Prevention

### Preventing Events

BEFORE events (creating, updating, deleting) can be prevented:

```php
class ValidatePostListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        
        if (empty($title)) {
            $event->prevent('Title is required');
        }
    }
    
    public function priority(): int
    {
        return 100; // Run early
    }
    
    // ... other methods
}
```

### Handling Prevention

In Filament pages:

```php
$result = $hookManager->dispatch($event);

if ($result->wasPrevented()) {
    $validator = Validator::make([], []);
    $validator->errors()->add('post', $result->preventReason());
    throw new ValidationException($validator);
}
```

### Prevention Behavior

- **Immediate Stop**: When `prevent()` is called, all remaining listeners are skipped
- **No Mutations After Prevent**: Once prevented, no further mutations are allowed
- **Prevention Reason**: Always provide a clear reason for prevention

---

## Priority System

### How Priority Works

- **Lower numbers execute first**: Priority 0 executes before priority 10 (Laravel standard)
- **Consistent Across All Hook Types**: Event hooks, form hooks, table hooks all use the same priority system
- **Ascending Order**: All hooks are sorted in ascending order (0, 10, 50, 100)
- **Database Override**: Priority can be overridden in database (via `mks_hooks` table)

### Priority Guidelines

**Lower numbers execute first** - This follows Laravel's standard priority system.

```php
// System-critical (validations, security) - Run first
public function priority(): int
{
    return 0; // Executes first
}

// Core functionality (slug generation, defaults) - Run early
public function priority(): int
{
    return 10;
}

// Feature enhancements - Run mid-way
public function priority(): int
{
    return 50;
}

// Nice-to-have features - Run later
public function priority(): int
{
    return 100;
}

// Cleanup, logging, notifications (run last) - Run at the end
public function priority(): int
{
    return 200; // Executes last
}
```

**Execution Order Example:**
- Priority 0 → Executes first
- Priority 10 → Executes second
- Priority 50 → Executes third
- Priority 100 → Executes fourth
- Priority 200 → Executes last

### Setting Priority

#### In Code

```php
public function priority(): int
{
    return 50;
}
```

#### Via Database

```php
DB::table('mks_hooks')
    ->where('listener_class', GenerateSlugListener::class)
    ->update(['priority' => 75]);
```

#### Via Helper (Deprecated)

```php
// This is deprecated - use database instead
Hooks::setPriority(GenerateSlugListener::class, 75);
```

---

## Discovery System

### Automatic Discovery

The discovery system automatically finds and registers hooks:

```bash
php artisan mks:discover
```

### Discovery Process

1. **Scan Files**: Recursively scan for PHP files in specified directories
2. **Parse Classes**: Extract class names and check interfaces
3. **Extract Metadata**: Extract event names, hook names, priorities
4. **Sync with Database**: Update `mks_hooks` table
5. **Cache Results**: Cache discovery results for performance

### Discovery Configuration

By default, discovery scans:
- `app/Hooks/Listeners/`
- Package's `Core/Listeners/`

### Manual Discovery

```php
use Miran\Mksine\Core\Services\DiscoveryService;

$discoveryService = app(DiscoveryService::class);
$listeners = $discoveryService->discoverListeners(app_path('Hooks/Listeners'));
$discoveryService->syncListeners($listeners);
```

---

## State Management

### Database-Driven State

Hook state is managed via the `mks_hooks` table:

- **Enabled/Disabled**: Control whether a hook executes
- **Priority Overrides**: Override priority from database
- **System Flags**: Mark hooks as system-critical

### Enabling/Disabling Hooks

```php
// Disable a hook
DB::table('mks_hooks')
    ->where('listener_class', GenerateSlugListener::class)
    ->update(['is_enabled' => false]);

// Enable a hook
DB::table('mks_hooks')
    ->where('listener_class', GenerateSlugListener::class)
    ->update(['is_enabled' => true]);
```

### System Hooks

System hooks **cannot be disabled**:

```php
DB::table('mks_hooks')
    ->where('listener_class', ValidatePostListener::class)
    ->update(['is_system' => true]);
```

### State Cache

State is cached for performance:
- **Production**: Reads from `bootstrap/cache/mks_hook_state.php` (zero database queries)
- **Development**: Falls back to database if cache doesn't exist
- **Cache Invalidation**: Cache invalidates when database is updated

---

## Hook Visibility & Plugin Ownership

### Overview

The hook system now supports explicit hook visibility control and plugin ownership metadata. This allows for fine-grained access control and better error isolation.

### Hook Visibility Levels

Hooks can have three visibility levels:

#### 1. Public Hooks
**Accessible by:** All listeners from any plugin

Public hooks can be accessed by any listener regardless of plugin ownership. This is useful for hooks that are meant to be extended by third-party plugins.

```php
use Miran\Mksine\Core\Hooks\HookDefinition;

$definition = new HookDefinition(
    hookName: 'post.created',
    ownerPluginId: 'my-plugin',
    visibility: HookDefinition::VISIBILITY_PUBLIC
);

HookManager::registerHookDefinition($definition);
```

#### 2. Private Hooks
**Accessible by:** Only listeners from the owner plugin

Private hooks can only be accessed by listeners that belong to the same plugin as the hook owner. This is the **default visibility** for all hooks.

```php
$definition = new HookDefinition(
    hookName: 'post.creating',
    ownerPluginId: 'my-plugin',
    visibility: HookDefinition::VISIBILITY_PRIVATE // Default
);
```

#### 3. System Hooks
**Accessible by:** Only core system or the owner plugin

System hooks can only be accessed by core system listeners or listeners from the owner plugin. This is used for critical system functionality.

```php
$definition = new HookDefinition(
    hookName: 'post.validating',
    ownerPluginId: HookDefinition::PLUGIN_CORE,
    visibility: HookDefinition::VISIBILITY_SYSTEM
);
```

### Registering Hook Definitions

Hook definitions must be registered before listeners attempt to access them:

```php
use Miran\Mksine\Core\Hooks\HookDefinition;
use Miran\Mksine\Core\Hooks\HookManager;

// In your ServiceProvider
public function boot(): void
{
    $hookManager = app(HookManager::class);
    
    // Register a private hook
    $definition = new HookDefinition(
        hookName: 'post.creating',
        ownerPluginId: 'my-plugin',
        visibility: HookDefinition::VISIBILITY_PRIVATE
    );
    $hookManager->registerHookDefinition($definition);
    
    // Register a public hook
    $publicDefinition = new HookDefinition(
        hookName: 'post.created',
        ownerPluginId: 'my-plugin',
        visibility: HookDefinition::VISIBILITY_PUBLIC
    );
    $hookManager->registerHookDefinition($publicDefinition);
}
```

### Plugin Ownership

Every listener must be associated with a plugin identifier. This metadata is used for:
- Visibility enforcement
- Error isolation (future use)

#### Registering Listeners with Plugin Ownership

```php
use Miran\Mksine\Core\Hooks\HookManager;

$hookManager = app(HookManager::class);

// Register listener with plugin ID
$hookManager->register(
    eventName: 'post.creating',
    listenerClass: MyListener::class,
    priority: 10, // Lower number = runs earlier
    pluginId: 'my-plugin' // Explicit plugin ownership
);

// Without plugin ID (defaults to 'core' for backward compatibility)
$hookManager->register(
    eventName: 'post.creating',
    listenerClass: CoreListener::class,
    priority: 0 // Lower number = runs first (system-critical)
    // pluginId defaults to 'core'
);
```

#### Core Plugin Identifier

Use `HookDefinition::PLUGIN_CORE` for core/system listeners:

```php
use Miran\Mksine\Core\Hooks\HookDefinition;

// Core listener
$hookManager->register(
    'post.creating',
    CoreValidationListener::class,
    100,
    HookDefinition::PLUGIN_CORE
);
```

### Visibility Enforcement

Visibility is enforced at **dispatch time** (the earliest safe enforcement point):

1. **Hook Definition Check**: If a hook definition exists, visibility rules are applied
2. **Private Hook Access**: Only listeners from the owner plugin can access private hooks
3. **System Hook Access**: Only core or owner plugin listeners can access system hooks
4. **Violation Exception**: If a listener attempts to access a hook it doesn't have permission for, `HookVisibilityViolationException` is thrown

#### Example: Visibility Violation

```php
// Plugin A registers a private hook
$definition = new HookDefinition(
    hookName: 'post.creating',
    ownerPluginId: 'plugin-a',
    visibility: HookDefinition::VISIBILITY_PRIVATE
);
HookManager::registerHookDefinition($definition);

// Plugin B tries to listen to the hook
HookManager::register(
    'post.creating',
    PluginBListener::class,
    10, // Priority value doesn't matter for visibility check
    'plugin-b' // Different plugin!
);

// When dispatched, this will throw HookVisibilityViolationException
// because Plugin B is not the owner of this private hook
```

### Error Isolation Policy

The hook system implements a comprehensive error isolation policy:

#### Policy Rules

1. **Fatal/Throwable Errors**:
   - ✅ **Logged** with plugin + hook context
   - ✅ **Mutations reverted** automatically
   - ✅ **System hooks re-throw** (critical functionality)
   - ✅ **Non-system hooks continue** execution

2. **Error Context**:
   All errors include:
   - Listener class name
   - Plugin identifier (`plugin_id`)
   - Hook name (`hook_name`)
   - Hook owner (`hook_owner`)
   - Event name
   - Error message and trace
   - Number of mutations reverted

3. **System Hook Protection**:
   - System hooks that fail **MUST re-throw** the exception
   - This ensures critical functionality is not silently skipped

4. **Non-System Hook Isolation**:
   - Non-system hooks are isolated
   - One faulty listener does not break the entire hook chain
   - Execution continues with remaining listeners

#### Example Error Log

```json
{
  "listener": "App\\Hooks\\Listeners\\MyListener",
  "plugin_id": "my-plugin",
  "hook_name": "post.creating",
  "hook_owner": "my-plugin",
  "event": "post.creating",
  "error": "Undefined variable: $undefinedVar",
  "trace": "...",
  "mutations_reverted": 2
}
```

### Best Practices

1. **Always Register Hook Definitions**:
   - Register hook definitions before registering listeners
   - Use appropriate visibility levels

2. **Plugin Ownership**:
   - Always specify plugin ID when registering listeners
   - Use `HookDefinition::PLUGIN_CORE` for core/system listeners
   - Keep plugin IDs consistent across your application

3. **Visibility Levels**:
   - Use **private** for internal hooks (default)
   - Use **public** for hooks meant to be extended by others
   - Use **system** for critical core functionality

4. **Error Handling**:
   - System hooks should be extremely robust
   - Non-system hooks should handle errors gracefully
   - Always test error scenarios

### API Reference

#### HookDefinition

```php
class HookDefinition
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_SYSTEM = 'system';
    public const PLUGIN_CORE = 'core';

    public function __construct(
        string $hookName,
        string $ownerPluginId,
        string $visibility = self::VISIBILITY_PRIVATE
    );

    public function hookName(): string;
    public function ownerPluginId(): string;
    public function visibility(): string;
    public function isPublic(): bool;
    public function isPrivate(): bool;
    public function isSystem(): bool;
    public function isCoreOwned(): bool;
}
```

#### HookManager

```php
// Register hook definition
$hookManager->registerHookDefinition(HookDefinition $definition): void;

// Register listener with plugin ownership
$hookManager->register(
    string $eventName,
    string $listenerClass,
    int $priority = 0,
    ?string $pluginId = null
): void;
```

#### Exceptions

```php
// Thrown when visibility violation occurs
HookVisibilityViolationException extends \RuntimeException
```

---

## Performance & Caching

### Performance Optimizations

1. **Lazy Loading**: Listeners are instantiated only when their event is dispatched
2. **State Caching**: Hook state is cached in production (zero database queries)
3. **Discovery Caching**: Discovery results are cached
4. **Performance Monitoring**: Slow hooks (>100ms) are logged

### Cache Management

```bash
# Clear hook state cache
php artisan cache:clear

# Clear discovery cache
# Cache is automatically invalidated when running mks:discover
```

### Performance Monitoring

Slow hooks are automatically logged:

```
[warning] Slow hook detected for 'post.form'
- execution_time_ms: 125.5
```

---

## Best Practices

### 1. Event Naming

- Use consistent naming: `resource.action` (e.g., `post.creating`, `post.created`)
- BEFORE events: present continuous (creating, updating)
- AFTER events: past tense (created, updated)

### 2. Priority Management

- Use clear priority ranges (100+, 50-99, 10-49, 0-9, negative)
- Document priority choices in code comments
- Avoid priority conflicts when possible

### 3. Error Handling

- Always validate data before mutations
- Use `shouldHandle()` for conditional execution
- Don't throw exceptions unless necessary (use prevention instead)

### 4. Mutations

- Only mutate data when necessary
- Provide clear mutation reasons in comments
- Test mutation behavior thoroughly

### 5. Performance

- Keep listeners lightweight
- Use `shouldHandle()` to skip unnecessary processing
- Consider async execution for heavy operations

### 6. Testing

```php
// Test listener execution
$event = new PostCreating(['title' => 'Test']);
$listener = new GenerateSlugListener();
$listener->handle($event);
$this->assertEquals('test', $event->data()->get('slug'));

// Test prevention
$event = new PostCreating(['title' => '']);
$listener = new ValidatePostListener();
$listener->handle($event);
$this->assertTrue($event->isPrevented());
```

---

## API Reference

### HookManager

```php
// Register a listener
$hookManager->register(string $eventName, string $listenerClass, int $priority = 0): void

// Dispatch an event
$hookManager->dispatch(MksineEvent $event): EventResult

// Check if listener is enabled
$hookManager->isListenerEnabled(string $listenerClass): bool

// Get all registered listeners
$hookManager->getRegisteredListeners(): array
```

### Hooks Helper

```php
// Event hooks
Hooks::register(
    string $eventName,
    string $listenerClass,
    int $priority = 0,
    ?string $pluginId = null
): void

// Register hook definition
Hooks::registerHookDefinition(HookDefinition $definition): void

// Form hooks
Hooks::extendForm(string $formName, callable $callback, int $priority = 0): void

// Table hooks
Hooks::extendTable(string $tableName, callable $callback, int $priority = 0): void
Hooks::extendTableColumns(string $tableName, callable $callback, int $priority = 0): void
Hooks::extendTableActions(string $tableName, callable $callback, int $priority = 0): void
Hooks::extendTableBulkActions(string $tableName, callable $callback, int $priority = 0): void
Hooks::extendTableFilters(string $tableName, callable $callback, int $priority = 0): void

// Resource hooks
Hooks::extendResourceRelations(string $resourceName, callable $callback, int $priority = 0): void
Hooks::extendResourceWidgets(string $resourceName, callable $callback, int $priority = 0): void

// Page hooks
Hooks::extendPageHeaderActions(string $pageName, callable $callback, int $priority = 0): void
```

### MksineEvent

```php
// Data access
$event->data(): EventDataBag
$event->data()->get(string $key): mixed
$event->data()->has(string $key): bool
$event->data()->all(): array

// Context
$event->context(): array

// Mutations
$event->updateData(string $key, mixed $value): void
$event->mutations(): array

// Prevention
$event->prevent(string $reason): void
$event->isPrevented(): bool
$event->preventReason(): ?string
```

### EventResult

```php
$result->wasPrevented(): bool
$result->preventReason(): ?string
$result->mutations(): array
$result->pendingAsyncListeners(): array
$result->executionTime(): float
```

---

## Examples

### Example 1: Auto-generate Slug

```php
class GenerateSlugListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        if ($title && empty($event->data()->get('slug'))) {
            $event->updateData('slug', Str::slug($title));
        }
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return $event->data()->has('title');
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 10; // Lower number = runs earlier
    }
}
```

### Example 2: Validate Before Create

```php
class ValidatePostListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        
        if (empty($title)) {
            $event->prevent('Title is required');
            return;
        }
        
        if (strlen($title) > 255) {
            $event->prevent('Title cannot exceed 255 characters');
        }
    }
    
    public function priority(): int
    {
        return 0; // Lower number = runs first (system-critical validation)
    }
    
    // ... other methods
}
```

### Example 3: Send Notification After Create

```php
class NotifyPostCreatedListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $postId = $event->context()['post_id'] ?? null;
        $title = $event->data()->get('title');
        
        // Send notification
        Notification::send(
            User::admins()->get(),
            new PostCreatedNotification($postId, $title)
        );
    }

    public function shouldQueue(): bool
    {
        return true; // Queue for async execution
    }

    public function priority(): int
    {
        return 200; // Higher number = runs last (executes after all other listeners)
    }
    
    // ... other methods
}
```

### Example 4: Extend Form with Custom Fields

```php
class AddSeoFieldsToPostForm implements FormHookListenerInterface
{
    public static function getFormName(): string
    {
        return 'post.form';
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public static function extend(Schema $schema): Schema
    {
        $existingComponents = $schema->getComponents() ?? [];
        
        return $schema->components([
            ...$existingComponents,
            Section::make('SEO')
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->maxLength(60),
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->maxLength(160)
                        ->rows(3),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }
}
```

### Example 5: Hook Visibility and Plugin Ownership

```php
use Miran\Mksine\Core\Hooks\HookDefinition;
use Miran\Mksine\Core\Hooks\HookManager;

// In your ServiceProvider
public function boot(): void
{
    $hookManager = app(HookManager::class);
    
    // 1. Register a private hook (only your plugin can access)
    $privateHook = new HookDefinition(
        hookName: 'my-plugin.internal.process',
        ownerPluginId: 'my-plugin',
        visibility: HookDefinition::VISIBILITY_PRIVATE
    );
    $hookManager->registerHookDefinition($privateHook);
    
    // 2. Register a public hook (any plugin can extend)
    $publicHook = new HookDefinition(
        hookName: 'my-plugin.post.before-save',
        ownerPluginId: 'my-plugin',
        visibility: HookDefinition::VISIBILITY_PUBLIC
    );
    $hookManager->registerHookDefinition($publicHook);
    
    // 3. Register listeners with plugin ownership
    $hookManager->register(
        eventName: 'my-plugin.internal.process',
        listenerClass: InternalProcessorListener::class,
        priority: 10, // Lower number = runs earlier
        pluginId: 'my-plugin' // Must match hook owner for private hooks
    );
    
    // 4. Third-party plugin can listen to public hooks
    // This would be in a different plugin:
    $hookManager->register(
        eventName: 'my-plugin.post.before-save',
        listenerClass: ThirdPartyListener::class,
        priority: 50, // Higher number = runs later than priority 10
        pluginId: 'third-party-plugin' // Different plugin, but public hook allows it
    );
}
```

---

## Troubleshooting

### Hooks Not Executing

1. **Check Discovery**: Run `php artisan mks:discover`
2. **Check Enabled State**: Verify hook is enabled in database
3. **Check Priority**: Verify priority allows execution
4. **Check shouldHandle()**: Verify `shouldHandle()` returns true

### Mutations Not Applied

1. **Check Event Result**: Verify `$result->mutations()` contains mutations
2. **Check Prevention**: Verify event wasn't prevented
3. **Check Listener Failure**: Check logs for listener errors

### Performance Issues

1. **Check Slow Hooks**: Review logs for slow hook warnings
2. **Clear Cache**: Run `php artisan cache:clear`
3. **Review Priority**: Optimize priority order
4. **Use Async**: Consider queuing heavy listeners

### Cache Issues

1. **Clear State Cache**: Delete `bootstrap/cache/mks_hook_state.php`
2. **Clear Discovery Cache**: Delete `bootstrap/cache/mks_hook_discovery.php`
3. **Re-run Discovery**: Run `php artisan mks:discover`

---

## System Requirements

- PHP 8.1+
- Laravel 11+
- Filament 4
- Database (MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+)

## Contributing

Contributions are welcome! Please ensure all code follows PSR-12 coding standards and includes appropriate tests.

## License

[Your License Here]

## Support

[Your Support Information Here]

---

## Plugins: Create Plugin, Resource, Page, Widget

### Creating a Plugin

Use the artisan command to scaffold a new plugin:

```bash
php artisan mks-plugin:make {name}
```

**Example:**

```bash
php artisan mks-plugin:make my-shop
```

**Options:** `--namespace=`, `--author=`, `--description=`

This creates `plugins/{name}/` with:

- `plugin.php` (plugin class and ID)
- `composer.json`
- `package.json`, `vite.config.js` — npm build config
- `src/` (Filament, Models, Hooks)
- `resources/css/app.css` — Tailwind CSS source
- `resources/js/app.js` — JS entry point
- `resources/dist/` — compiled output (committed to git)
- `routes/web.php`, `routes/api.php`
- `publishes/` — optional JSON presets for copying vendor package files into this plugin (see [Composer packages in plugins](#plugins-composer-packages-install-and-publish)); includes `README.md`

Then:

1. Run **Plugins** discovery if needed: `php artisan mks-plugin:discover`
2. In **Admin Panel → System → Plugins**, find the plugin and click **Install**, then **Activate**
3. Install npm deps and build: `cd plugins/{name} && npm install && npm run build`

### Creating a Resource in a Plugin

Generate a Filament resource that follows MKSine structure (Form schema, Table, pages):

```bash
php artisan mks-plugin:make-resource {plugin-id} {ResourceName}
```

**Example:**

```bash
php artisan mks-plugin:make-resource my-shop Product
```

This creates under the plugin’s `src/Filament/Resources/`:

- `ProductResource/ProductResource.php`
- `ProductResource/Schemas/ProductForm.php` (with form hooks)
- `ProductResource/Tables/ProductTable.php` (with table hooks)
- `ProductResource/Pages/ListProducts.php`, `CreateProduct.php`, `EditProduct.php`

The resource is auto-discovered when the plugin is active.

### Creating a Page in a Plugin

Generate a custom Filament page:

```bash
php artisan mks-plugin:make-page {plugin-id} {PageName}
```

**Example:**

```bash
php artisan mks-plugin:make-page my-shop Reports
```

This creates `src/Filament/Pages/Reports.php` (and optionally a view). The page is discovered when the plugin is active.

### Creating a Widget in a Plugin

Generate a Filament widget:

```bash
php artisan mks-plugin:make-widget {plugin-id} {WidgetName}
```

**Options:** `--chart`, `--stats` for chart or stats overview widgets.

**Example:**

```bash
php artisan mks-plugin:make-widget my-shop SalesChart --chart
php artisan mks-plugin:make-widget my-shop OrderStats --stats
```

This creates `src/Filament/Widgets/{WidgetName}.php`. For basic widgets, a Blade view is also created under `resources/views/filament/widgets/`. Widgets are discovered when the plugin is active.

## Plugins: Composer packages (install and publish)

If you add a Composer dependency **inside** a plugin (`cd plugins/{id} && composer require …`), many third-party packages document a **`php artisan …:install`** step or **`vendor:publish`** for config and migrations. Those commands assume the package is installed on the **host Laravel app** and usually write under the application’s `config/` and `database/migrations/`. A package that exists **only** under `plugins/{id}/vendor/` is not wired the same way at the app root, and **you should not rely on running those installers on production** when plugins are deployed as ZIPs without Composer.

**Use MKSine’s plugin-local workflow instead:**

1. **Define a preset** — Add `plugins/{id}/publishes/{preset}.json` describing where the package lives under `vendor/` and what to copy into the plugin (optional `config.from` / `config.to`, optional `migrations` list). The scaffolded plugin includes `publishes/README.md` with the JSON shape.
2. **Register a console command** in that plugin’s `boot()` that calls `Miran\Mksine\Core\Plugins\Publishing\PluginVendorPublishRunner` (pass the plugin manifest, preset name, console I/O, and `--force` if you need to overwrite config). Example in the **mks-booking** plugin: `php artisan mks-booking:publish-vendor {preset}` from the **app root** (`--force` overwrites published config targets).
3. **Apply migrations** — After files exist under the plugin’s `database/migrations/`, run `php artisan mks-plugin:migrate {id}` (or your usual plugin migration flow).

There is **no** `mks-plugin:publish-vendor` in the core package: each plugin owns its preset files and command name so third-party authors can ship plugins without modifying `miran/mksine`.

---

## Plugin NPM Build & Assets

Plugins have the same npm build system as themes. Every plugin scaffolded with `mks-plugin:make` ships with a self-contained frontend build setup.

### Directory Layout

```
plugins/{name}/
├── package.json          ← npm scripts
├── vite.config.js        ← Vite + @tailwindcss/vite
├── resources/
│   ├── css/
│   │   └── app.css       ← Tailwind source (scans src/ and views/)
│   ├── js/
│   │   └── app.js        ← JS entry point
│   └── dist/
│       ├── app.css       ← compiled (committed to git)
│       └── app.js        ← compiled (committed to git)
└── node_modules/         ← gitignored
```

### NPM Scripts

| Command | What it does |
|---|---|
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | Full production build (3 steps below) |
| `npm run build:admin` | Rebuild mksine admin panel CSS only |

`npm run build` runs the following three steps **in order**:

1. **`vite build`** — compiles `resources/css/app.css` + `resources/js/app.js` → `resources/dist/`
2. **`build:admin`** — rebuilds `packages/mksine/resources/dist/mksine.css` so the admin panel picks up any new Tailwind classes from plugin views
3. **`build:publish`** — runs `php artisan filament:assets` to copy compiled CSS/JS to `public/`

### Workflow

```bash
cd plugins/my-plugin

# First time
npm install

# During development — watch & rebuild admin CSS manually when needed
npm run dev

# Production / after editing Blade views or CSS
npm run build
```

### How Admin Panel CSS Works

Plugin Blade views use Tailwind utility classes (including Filament's `primary-*` colours). These classes are generated by the **mksine admin panel CSS** (`packages/mksine/resources/dist/mksine.css`), not by the plugin's own CSS.

`packages/mksine/resources/css/index.css` is configured to scan **all plugin view directories**:

```css
@source '../../../../plugins';   /* scans all plugins recursively */
```

This means:
- After editing a plugin's Blade view, run `npm run build` in the plugin (or `npm run build:styles` in `packages/mksine`) to pick up new classes.
- The plugin's own `resources/dist/app.css` is for **custom non-Tailwind styles** only (animations, vendor overrides, etc.). It is registered as a Filament asset automatically when the plugin is active.

### Plugin CSS is Registered Automatically

When a plugin is **active** and `resources/dist/app.css` exists, MKSine registers it as a Filament asset automatically — no manual registration needed:

```php
// MksinePlugin::boot() handles this for every active plugin
if ($cssPath = $manifest->distCssPath()) {
    FilamentAsset::register([Css::make("{$pluginId}-styles", $cssPath)]);
}
```

### Tailwind Source in Plugin CSS

`resources/css/app.css` is pre-configured to scan the plugin's own source files:

```css
@import 'tailwindcss' source(none);

/* Scans plugin PHP source and Blade views for Tailwind classes */
@source '../../src';
@source '../views';

/* Add your custom (non-utility) styles below */
```

> **Note:** Tailwind's `primary-*`, `danger-*`, and other Filament colour utilities are NOT generated in the plugin's own CSS — they come from the mksine admin panel CSS. Run `npm run build` (not just `vite build`) to ensure these are included.

### Comparing Plugin vs Theme Build

| | Plugin | Theme |
|---|---|---|
| **Purpose** | Admin panel (Filament) | Frontend (public site) |
| **Tailwind utilities** | Shared via mksine admin CSS | Self-contained per theme |
| **Primary colour** | Filament panel colour (runtime) | Theme-defined |
| **`npm run build` rebuilds mksine CSS** | ✅ Yes | ❌ No |
| **`npm run build` runs `filament:assets`** | ✅ Yes | ❌ No |
| **`npm run build` runs theme-publish** | ❌ No | ✅ Yes |

---

## Themes: Create, NPM, Publish Assets

### Creating a Theme

```bash
php artisan mks:make-theme "My Theme"
```

**Options:** `--identifier=`, `--author=`, `--description=`, `--force`

This creates `resources/views/themes/{identifier}/` with:

- `theme.json` — name, version, author, description, `assets.css` / `assets.js`
- `layouts/index.blade.php`, templates (`home`, `single`, `page`, `category`, etc.)
- `src/css/app.css`, `src/js/app.js` — source for build
- `dist/app.css`, `dist/app.js` — compiled output (commit these)
- `package.json`, `tailwind.config.js`, `BUILD.md`, `.gitignore`

### Using NPM in a Theme

1. Go to the theme directory: `cd resources/views/themes/{identifier}`
2. Install dependencies: `npm install`
3. Development (watch): `npm run dev`
4. Production build: `npm run build` — compiles `src/` into `dist/` (CSS/JS)

Themes use Tailwind and modern JS by default; you can change the build in `package.json`.

### Publishing CSS/JS to Public

To serve theme assets from the web root:

```bash
php artisan mks:theme-publish {identifier}
```

**Examples:**

```bash
php artisan mks:theme-publish my-theme
php artisan mks:theme-publish my-theme --force   # Overwrite existing
php artisan mks:theme-publish                    # Publish all themes
```

This copies the theme’s `dist/` (and optionally `images/`) to:

- **Project themes:** `public/themes/{identifier}/`
- **Package themes:** `public/vendor/mksine/themes/{identifier}/`

The layout should use `@themeAssets` so the active theme’s CSS/JS are loaded from these paths.

---

## Settings: Adding a New Tab

The Settings page supports extra tabs via `SettingsTabManager`. Use it in `AppServiceProvider` or a plugin’s `boot()`:

```php
use Miran\Mksine\Core\Hooks\SettingsTabManager;
use Filament\Forms\Components\TextInput;

public function boot(): void
{
    app(SettingsTabManager::class)->registerTab(
        id: 'seo',
        label: __('SEO'),
        schema: [
            TextInput::make('meta_description')->label('Meta Description'),
            TextInput::make('meta_keywords')->label('Meta Keywords'),
        ],
        sortOrder: 50
    );
}
```

- **id**: Unique tab key (e.g. `seo`, `my_plugin`).
- **label**: Tab label (can be a translation key).
- **schema**: Array of Filament form components, or a callable that returns the array.
- **sortOrder**: Lower values appear first (default `0`).

Values are stored via the same mechanism as core settings (e.g. `Setting::updateOrCreate`). Core tabs (General, Permalinks, etc.) are defined by the Settings page; your tabs are appended in order.

---

## Page Builder: Adding a Component

The page builder shows blocks from `ComponentRegistry`. To add a custom component:

1. **Implement the interface** — create a class that implements `BuilderComponentInterface` (or extends `BaseBuilderComponent`):

```php
namespace App\PageBuilder;

use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Core\PageBuilder\Contracts\BuilderComponentInterface;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class MyBlockComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'my_block';
    }

    public static function getName(): string
    {
        return __('My Block');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT; // or CATEGORY_MEDIA, CATEGORY_LAYOUT, CATEGORY_INTERACTIVE
    }

    public static function getDescription(): string
    {
        return __('A custom block for the page builder.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->required(),
            Select::make('style')->options(['default' => 'Default', 'highlight' => 'Highlight'])->default('default'),
        ];
    }

    public static function getDefaultData(): array
    {
        return ['title' => '', 'style' => 'default'];
    }
}
```

2. **Register the component** — in `AppServiceProvider::boot()` (or a plugin):

```php
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;

public function boot(): void
{
    app(ComponentRegistry::class)->register(MyBlockComponent::class);
}
```

3. **Render the block on the front** — in your theme’s page builder view, use the block’s `type` and `data`. The built-in renderer will use the same type to pick the Blade partial or Livewire component; add a view for `page-builder.blocks.my_block` (or the convention your theme uses) that reads `$block['data']`.

Required interface methods:

- `getType()` — unique key (e.g. `heading`, `my_block`)
- `getName()`, `getIcon()`, `getCategory()`, `getDescription()` — for the builder UI
- `getSchema()` — Filament form components for editing the block
- `getDefaultData()` — default values for new instances
- `supportsChildren()` / `getMaxChildren()` — optional; for nested blocks
- `validate(array $data)` — optional validation

Categories: `BaseBuilderComponent::CATEGORY_CONTENT`, `CATEGORY_MEDIA`, `CATEGORY_LAYOUT`, `CATEGORY_INTERACTIVE`.

---

## Theme Manager

MKSine includes a powerful Theme Management System that supports both package themes (bundled with MKSine) and project themes (created by developers).

### Key Features

- **Dual Theme Sources**: Support for both package default themes and developer-created themes
- **Visual Theme Manager**: Filament-based UI for managing and activating themes
- **Automatic Discovery**: Themes are automatically discovered from configured directories
- **Pre-built Assets**: All themes use pre-compiled CSS/JS - no Node.js required on production
- **Upload & Install**: Upload theme ZIP files directly from the admin panel

### Theme Architecture

| Theme Type | Location | Public Assets |
|------------|----------|---------------|
| **Package Theme** | `packages/mksine/resources/views/themes/` | `public/vendor/mksine/themes/{id}/` |
| **Project Theme** | `resources/views/themes/` | `public/themes/{id}/` |

### Theme Workflow

MKSine uses a clean, production-ready approach for theme assets:

```
┌─────────────────────────────────────────────────────────────┐
│  DEVELOPMENT                                                 │
│  ┌─────────┐    ┌─────────────┐    ┌──────────┐            │
│  │  src/   │ ──▶│ npm run     │ ──▶│  dist/   │            │
│  │  css/js │    │   build     │    │  css/js  │            │
│  └─────────┘    └─────────────┘    └──────────┘            │
│       │              │                   │                  │
│   Tailwind      Compile &           Compiled               │
│   + Modern JS    Minify             Assets                 │
└─────────────────────────────────────────────────────────────┘
                                           │
                                           ▼
┌─────────────────────────────────────────────────────────────┐
│  PRODUCTION                                                  │
│  ┌──────────┐    ┌─────────────┐    ┌──────────┐            │
│  │  dist/   │ ──▶│ mks:theme-  │ ──▶│ public/  │            │
│  │  css/js  │    │   publish   │    │ themes/  │            │
│  └──────────┘    └─────────────┘    └──────────┘            │
│                                           │                  │
│                                      Served to               │
│                                       Browser                │
└─────────────────────────────────────────────────────────────┘
```

**Key Points:**
- **Development**: Use Tailwind, modern JS, any build tools you prefer
- **Pre-build**: Run `npm run build` to generate `dist/` files
- **Production**: Server only needs compiled files, no Node.js required
- **Themes are switchable** without rebuilding anything

### Creating a Theme

#### Using the Artisan Command (Recommended)

```bash
# Create a new theme
php artisan mks:make-theme "My Theme"

# Create with options
php artisan mks:make-theme "My Theme" \
    --identifier=my-theme \
    --author="Your Name" \
    --description="A beautiful custom theme"

# Overwrite existing theme
php artisan mks:make-theme "My Theme" --force
```

This command generates:
```
resources/views/themes/my-theme/
├── theme.json              # Theme metadata
├── package.json            # Node.js dependencies for development
├── tailwind.config.js      # Tailwind configuration
├── BUILD.md                # Build instructions
├── .gitignore              # Git ignore rules
├── src/                    # Development source files
│   ├── css/
│   │   └── app.css         # Tailwind CSS source
│   └── js/
│       └── app.js          # JavaScript source
├── dist/                   # Compiled assets (commit these!)
│   ├── app.css             # Compiled CSS
│   └── app.js              # Compiled JavaScript
├── images/                 # Theme images
├── layouts/
│   └── index.blade.php     # Main layout
├── partials/
│   ├── header.blade.php    # Header partial
│   └── footer.blade.php    # Footer partial
├── home.blade.php          # Homepage template
├── single.blade.php        # Single post template
├── category.blade.php      # Category archive
└── page.blade.php          # Static page template
```

### Theme Development Workflow

#### 1. Install Dependencies

```bash
cd resources/views/themes/my-theme
npm install
```

#### 2. Development Mode

```bash
npm run dev
```

This watches `src/css/app.css` and rebuilds `dist/app.css` on changes.

#### 3. Production Build

```bash
npm run build
```

This creates minified CSS and JS in `dist/`.

#### 4. Publish to Public Directory

```bash
php artisan mks:theme-publish my-theme
```

This copies `dist/` to `public/themes/my-theme/`.

#### 5. Activate Theme

Go to **Admin Panel → Appearance → Themes** and click "Activate".

### theme.json Reference

```json
{
    "name": "My Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A custom theme for MKSine",
    "screenshot": "screenshot.png",
    "assets": {
        "css": ["dist/app.css"],
        "js": ["dist/app.js"]
    }
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Display name for the theme |
| `version` | No | Theme version (default: 1.0.0) |
| `author` | No | Theme author |
| `description` | No | Brief description |
| `screenshot` | No | Screenshot filename (PNG recommended) |
| `assets.css` | Yes | Array of CSS file paths (relative to theme root) |
| `assets.js` | Yes | Array of JS file paths (relative to theme root) |

### Theme Templates

#### Layout (layouts/index.blade.php)

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @themeAssets
</head>
<body>
    @include($__theme_namespace . '.partials.header')

    <main>
        {{ $slot }}
    </main>

    @include($__theme_namespace . '.partials.footer')
</body>
</html>
```

#### Page Template (home.blade.php)

```blade
<x-dynamic-component :component="$__theme_layout">
    <x-slot:title>{{ __('Home') }}</x-slot:title>

    <div class="container mx-auto px-4 py-8">
        <h1>Welcome to {{ config('app.name') }}</h1>

        @foreach($posts as $post)
            <article>
                <h2>{{ $post->title }}</h2>
                <p>{{ $post->excerpt }}</p>
            </article>
        @endforeach
    </div>
</x-dynamic-component>
```

### Theme Variables

These variables are automatically available in all theme templates:

| Variable | Description |
|----------|-------------|
| `$__theme_layout` | Dynamic component name for the layout |
| `$__theme_namespace` | View namespace for includes (`theme::identifier` or `mksine::themes.identifier`) |

### Helper Functions

```php
// Get ThemeManager instance
theme_manager()

// Get URL for a theme asset
theme_asset('images/logo.png')

// Get full view name for a theme view
theme_view('home')  // Returns: theme::my-theme.home

// Get the layout view name
theme_layout()

// Get active theme data
active_theme()
active_theme()->name
active_theme()->version
```

### Blade Directives

#### @themeAssets

Renders CSS and JS tags for the active theme:

```blade
@themeAssets

{{-- Renders something like: --}}
<link rel="stylesheet" href="/themes/my-theme/dist/app.css">
<script src="/themes/my-theme/dist/app.js" defer></script>
```

### Artisan Commands

#### mks:make-theme

Generate a new theme scaffold with build tools configured.

```bash
php artisan mks:make-theme {name}
    [--identifier=]       # Custom identifier (defaults to slugified name)
    [--author=]           # Theme author name
    [--description=]      # Theme description
    [--force]             # Overwrite existing theme
```

**Examples:**
```bash
# Basic theme
php artisan mks:make-theme "Corporate Theme"

# Theme with all options
php artisan mks:make-theme "Blog Theme" \
    --identifier=blog \
    --author="John Doe" \
    --description="A minimal blog theme"
```

#### mks:theme-publish

Publish theme assets (dist/, images/, screenshot) to the public directory.

```bash
php artisan mks:theme-publish [theme]
    [--force]             # Overwrite existing published assets
```

**Examples:**
```bash
# Publish all themes
php artisan mks:theme-publish

# Publish specific theme
php artisan mks:theme-publish my-theme

# Force overwrite
php artisan mks:theme-publish my-theme --force
```

### Uploading Themes via Admin Panel

1. Navigate to **Appearance → Themes**
2. Click **Upload Theme**
3. Select a ZIP file containing your theme
4. The theme will be extracted to `resources/views/themes/`
5. Click **Activate** to enable the theme

**ZIP Structure Requirements:**
```
my-theme.zip
└── my-theme/           # Theme folder
    ├── theme.json      # Required
    ├── layouts/
    │   └── index.blade.php
    ├── home.blade.php
    └── dist/           # Pre-compiled assets
        ├── app.css
        └── app.js
```

### Deleting Themes

- **Project themes** can be deleted from the admin panel
- **Package themes** cannot be deleted (they are part of the core system)
- **Active themes** cannot be deleted (activate another theme first)

### Important Notes

#### Commit the `dist/` folder

Unlike typical JavaScript projects, you **SHOULD commit** the `dist/` folder:

```gitignore
# In your theme's .gitignore
node_modules/
# dist/ is NOT ignored - it should be committed
```

This ensures:
- Themes work on production servers without Node.js
- No build step required during deployment
- Themes are immediately usable after upload

#### Quick Testing (Without Build)

For quick testing without setting up build tools, add Tailwind CDN to your layout:

```html
<script src="https://cdn.tailwindcss.com"></script>
```

### Best Practices

1. **Always include theme.json** - Required for theme discovery
2. **Commit dist/ folder** - Ensures theme works without build tools
3. **Use semantic versioning** - Helps track theme updates
4. **Include a screenshot** - Improves UX in the theme selector (recommended: 800x600 PNG)
5. **Test RTL support** - Use `dir="rtl"` for RTL languages
6. **Run npm run build** before publishing - Ensures latest assets are compiled
7. **Use @themeAssets** - Ensures correct asset loading

---

## Menu Management System

MKSine includes a powerful Menu Management System fully integrated with Filament.

### Key Features
- **Visual Menu Builder**: Drag-and-drop interface for managing menu structures.
- **Nested Menus**: Support for multi-level menus with infinite depth (configurable).
- **Multiple Locations**: Manage menus for different parts of your site (Header, Footer, Sidebar).
- **Custom Links & Polymorphic Items**: Link to internal pages, categories, or external URLs.

### Using the Menu Builder
Navigate to **Menus** in the admin panel.
1. **Create a Menu**: Give it a name and assign it to a registered location (optional).
2. **Add Items**: Use the sidebar panels to add:
   - **Custom Links**: External URLs or arbitrary paths.
   - **Pages**: Links to static pages.
   - **Categories**: Links to post categories.
3. **Structure**: Drag items to reorder. Drag slightly to the right to nest an item under another.
4. **Edit Items**: Click the "Edit" button on any item to change its label, URL, or target (New Tab/Same Tab).

### Registering Menu Locations
You can register menu locations from your `AppServiceProvider` or any Plugin's service provider using the `MenuLocationManager`.

```php
use Miran\Mksine\Core\Hooks\MenuLocationManager;

public function boot(): void
{
    MenuLocationManager::register([
        'header_primary' => 'Primary Header Menu',
        'footer_links'   => 'Footer Links Section',
        'sidebar_main'   => 'Sidebar Main Menu',
    ]);
}
```

These locations will automatically appear in the Menu Resource for assignment.

### Retrieving Menus in Blade
Use the `MenuService` to retrieve menu trees for your frontend themes.

```php
@inject('menuService', 'Miran\Mksine\Services\MenuService')

{{-- Get menu by location --}}
@php $menuTree = $menuService->forLocation('header_primary'); @endphp

<ul>
    @foreach($menuTree as $item)
        <li>
            <a href="{{ $item->url }}" target="{{ $item->target }}">
                {{ $item->title }}
            </a>
            @if($item->children->count())
                <ul>
                    {{-- Render children recursively --}}
                </ul>
            @endif
        </li>
    @endforeach
</ul>
```

---

## User Management

MKSine provides a built-in, Filament-native User Management resource.

- **Standard Resource**: fully customizable `UserResource`.
- **Dedicated Components**: Segregated `UserForm` and `UserTable` classes for better code organization.
- **Role & Permission Ready**: The structure is ready to be extended with Roles & Permissions packages (like Spatie or Filament Shield).

---

## Plugin Development Tools

MKSine offers robust tools to speed up plugin development.

### Generating Resources
We provide a dedicated command to generate Filament resources that follow the MKSine architectural standards (Filament v4 ready).

```bash
php artisan mks-plugin:make-resource <plugin-id> <ResourceName>
```

**Example:**
```bash
php artisan mks-plugin:make-resource my-shop Product
```

**What it generates:**
1. **Resource Class**: `src/Filament/Resources/ProductResource/ProductResource.php`
   - Includes explicit `slug` definition for clean URLs (e.g., `admin/products`).
   - Delegates Form and Table configuration to separate classes.
2. **Schema Class**: `src/Filament/Resources/ProductResource/Schemas/ProductForm.php`
   - Contains the form schema definition.
   - Automatically registers `form` hooks.
3. **Table Class**: `src/Filament/Resources/ProductResource/Tables/ProductTable.php`
   - Contains the table columns and actions.
   - Automatically registers `table` hooks.
4. **Pages**: List, Create, and Edit pages with correct routing.

This structure ensures your plugins remain clean, maintainable, and fully hookable by other developers.

### Automatic Route Loading
MKSine automatically discovers and loads routes from your plugins if they follow the standard convention:

1. **Web Routes**: `routes/web.php`
   - Automatically wrapped in the `web` middleware group.
   - Ideal for frontend pages or custom admin routes.
2. **API Routes**: `routes/api.php`
   - Automatically wrapped in the `api` middleware group.
   - Automatically prefixed with `/api`.

If you need custom middleware or prefixes, you can still register routes manually in your plugin class's `boot()` method.
