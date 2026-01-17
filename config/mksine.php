<?php

/**
 * MKSine Configuration
 *
 * This file contains all configuration options for MKSine.
 * You can publish this file to your config directory using:
 *
 * php artisan vendor:publish --tag="mksine-config"
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Package Version
    |--------------------------------------------------------------------------
    |
    | The current version of MKSine.
    | This is used for compatibility checks and migrations.
    |
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of MKSine.
    | Disabling a feature will prevent its resources from loading.
    |
    */
    'features' => [
        // Core content management (posts, categories)
        'content_management' => env('MKS_CMS_CONTENT_MANAGEMENT', true),

        // Media library management
        'media_management' => env('MKS_CMS_MEDIA_MANAGEMENT', true),

        // Plugin system
        'plugin_system' => env('MKS_CMS_PLUGIN_SYSTEM', true),

        // Theme management (coming soon)
        'theme_management' => env('MKS_CMS_THEME_MANAGEMENT', false),

        // Page builder (coming soon)
        'page_builder' => env('MKS_CMS_PAGE_BUILDER', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for caching hook states and plugin discovery.
    |
    */
    'cache' => [
        // Enable caching
        'enabled' => env('MKS_CMS_CACHE_ENABLED', true),

        // Cache key prefix
        'prefix' => env('MKS_CMS_CACHE_PREFIX', 'mks_cms'),

        // Cache TTL in seconds (default: 1 hour)
        'ttl' => env('MKS_CMS_CACHE_TTL', 3600),

        // Cache driver (null = default driver)
        'driver' => env('MKS_CMS_CACHE_DRIVER', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class to use for author relationships.
    | This should be the fully qualified class name of your User model.
    |
    */
    'user_model' => env('MKS_CMS_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Plugins Directory
    |--------------------------------------------------------------------------
    |
    | The directory where plugins are stored.
    | Relative to the base path of your application.
    |
    */
    'plugins_path' => env('MKS_CMS_PLUGINS_PATH', 'plugins'),

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for media file handling.
    |
    */
    'media' => [
        // Default disk for media storage
        'disk' => env('MKS_CMS_MEDIA_DISK', 'public'),

        // Media upload path (relative to disk)
        'path' => env('MKS_CMS_MEDIA_PATH', 'media'),

        // Maximum file size in KB (default: 10MB)
        'max_size' => env('MKS_CMS_MEDIA_MAX_SIZE', 10240),

        // Allowed mime types
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'video/mp4',
            'video/webm',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],

        // Image optimization
        'optimize_images' => env('MKS_CMS_OPTIMIZE_IMAGES', true),

        // Generate thumbnails
        'generate_thumbnails' => env('MKS_CMS_GENERATE_THUMBNAILS', true),

        // Thumbnail sizes
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for content management.
    |
    */
    'content' => [
        // Post statuses
        'post_statuses' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ],

        // Enable post revisions (coming soon)
        'enable_revisions' => env('MKS_CMS_ENABLE_REVISIONS', false),

        // Maximum revisions to keep per post
        'max_revisions' => env('MKS_CMS_MAX_REVISIONS', 10),

        // Enable post scheduling
        'enable_scheduling' => env('MKS_CMS_ENABLE_SCHEDULING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hook System Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the hook system.
    |
    */
    'hooks' => [
        // Log slow hooks (execution time > threshold in ms)
        'log_slow_hooks' => env('MKS_CMS_LOG_SLOW_HOOKS', true),

        // Slow hook threshold in milliseconds
        'slow_hook_threshold' => env('MKS_CMS_SLOW_HOOK_THRESHOLD', 100),

        // Enable hook discovery caching
        'cache_discovery' => env('MKS_CMS_CACHE_HOOK_DISCOVERY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */
    'security' => [
        // Require authorization for media access
        'authorize_media' => env('MKS_CMS_AUTHORIZE_MEDIA', false),

        // Sanitize uploaded filenames
        'sanitize_filenames' => env('MKS_CMS_SANITIZE_FILENAMES', true),

        // Scan uploaded files for malware (requires external service)
        'scan_uploads' => env('MKS_CMS_SCAN_UPLOADS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Configuration for the REST API.
    |
    */
    'api' => [
        // Enable API
        'enabled' => env('MKS_CMS_API_ENABLED', false),

        // API prefix
        'prefix' => env('MKS_CMS_API_PREFIX', 'api/cms'),

        // API version
        'version' => 'v1',

        // Rate limiting (requests per minute)
        'rate_limit' => env('MKS_CMS_API_RATE_LIMIT', 60),
    ],
];
