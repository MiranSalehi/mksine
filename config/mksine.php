<?php

use App\Models\User;
use Miran\Mksine\Models\Post;

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
    'version' => '1.0.14',

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

        // Visual page builder (Page type "builder" and PageBuilderField)
        'page_builder' => env('MKS_CMS_PAGE_BUILDER', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Verbose debug logs (plugin boot traces, Filament discovery, etc.) are written
    | only when APP_ENV is local or dev. Override with MKSINE_VERBOSE_LOGS=true|false.
    | On production/pro servers keep APP_ENV=production and LOG_LEVEL=error.
    |
    */
    'logging' => [
        'verbose' => env('MKSINE_VERBOSE_LOGS'),
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
    'user_model' => env('MKS_CMS_USER_MODEL', User::class),

    /*
    |--------------------------------------------------------------------------
    | Sync Laravel auth + Filament Shield user class
    |--------------------------------------------------------------------------
    |
    | When true, auth.providers.users.model and filament-shield.auth_provider_model
    | are set from user_model so installers do not duplicate the FQCN in auth.php
    | or multiple .env keys. Plugins may still override at boot (e.g. a subclass).
    | Set MKS_CMS_SYNC_AUTH_USER_MODEL=false only if you manage those keys yourself.
    |
    */
    'sync_auth_user_model' => env('MKS_CMS_SYNC_AUTH_USER_MODEL', true),

    /*
    |--------------------------------------------------------------------------
    | Upload limits (admin)
    |--------------------------------------------------------------------------
    |
    | Maximum upload size in megabytes for Filament file fields: media library,
    | plugin/theme ZIP install, and the ZIP updater UI. Drives the published
    | config/livewire.php stub (MKS_CMS_MAX_UPLOAD_MB). PHP upload_max_filesize
    | and post_max_size on the server must be >= this value.
    |
    */
    'uploads' => [
        'max_size_mb' => (int) env('MKS_CMS_MAX_UPLOAD_MB', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | CKEditor (rich text) — language / direction
    |--------------------------------------------------------------------------
    |
    | CKEditor uses language.ui and language.content for UI strings and for the
    | editable region’s text direction (e.g. fa → RTL). When null, the active
    | application locale is used. Override when the panel is English but authors
    | write Persian: set content_language to fa.
    |
    */
    'ckeditor' => [
        'ui_language' => env('MKS_CMS_CKEDITOR_UI_LANGUAGE'),
        'content_language' => env('MKS_CMS_CKEDITOR_CONTENT_LANGUAGE'),
    ],

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
    | Plugin Boot Guard
    |--------------------------------------------------------------------------
    |
    | TTL in seconds for boot flag staleness. Flags younger than this are
    | considered "still booting" (safe for concurrent requests). Only flags
    | older than TTL trigger "boot failure" detection.
    |
    */
    'plugins' => [
        'boot_guard_ttl' => env('MKS_CMS_PLUGIN_BOOT_GUARD_TTL', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Updater Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the ZIP updater (plugins, themes, core).
    |
    | The updater is Super-Admin-only and performs atomic on-disk swaps with
    | backups next to each target. Production servers do NOT need composer or
    | npm: ZIPs must contain pre-built assets and must NOT introduce new
    | Composer dependencies (for core). See docs/operations/zip-updater.md.
    |
    */
    'updater' => [
        // Master switch. Disable to hide updater UI and reject CLI invocations.
        'enabled' => env('MKS_CMS_UPDATER_ENABLED', true),

        // How many historical backups to retain per target (oldest pruned).
        'keep_backups' => (int) env('MKS_CMS_UPDATER_KEEP_BACKUPS', 3),

        // Upload size cap for ZIPs (in megabytes). Defaults to uploads.max_size_mb.
        'max_zip_size_mb' => (int) env('MKS_CMS_UPDATER_MAX_ZIP_MB', env('MKS_CMS_MAX_UPLOAD_MB', 100)),

        // Lock file staleness threshold (informational; flock itself is blocking).
        'lock_timeout_sec' => (int) env('MKS_CMS_UPDATER_LOCK_TTL', 300),

        // When true, same-version re-uploads are accepted (useful for recovering
        // corrupted files). Default rejects same-version as a safety rail.
        'allow_same_version_reinstall' => env('MKS_CMS_UPDATER_ALLOW_REINSTALL', false),
    ],

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

        // Maximum file size in KB (default: 100 MB — see uploads.max_size_mb).
        'max_size' => (int) env('MKS_CMS_MEDIA_MAX_SIZE', (int) env('MKS_CMS_MAX_UPLOAD_MB', 100) * 1024),

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
        /*
        |--------------------------------------------------------------------------
        | Extra hook listener discovery paths
        |--------------------------------------------------------------------------
        |
        | php artisan mks:discover always scans the package Core/Listeners tree first.
        | Add absolute paths (e.g. app_path('Hooks/Listeners')) for app or plugin
        | listener classes. Missing directories are skipped with a warning.
        |
        */
        'discovery_paths' => [
            // base_path('app/Hooks/Listeners'),
        ],

        // Log slow hooks (execution time > threshold in ms)
        'log_slow_hooks' => env('MKS_CMS_LOG_SLOW_HOOKS', true),

        // Slow hook threshold in milliseconds
        'slow_hook_threshold' => env('MKS_CMS_SLOW_HOOK_THRESHOLD', 100),

        // Enable hook discovery caching
        'cache_discovery' => env('MKS_CMS_CACHE_HOOK_DISCOVERY', true),

        // Async queue for listeners that shouldQueue() === true
        'queue' => [
            'enabled' => env('MKS_CMS_HOOKS_QUEUE_ENABLED', true),
            'connection' => env('MKS_CMS_HOOKS_QUEUE_CONNECTION'),
            'queue' => env('MKS_CMS_HOOKS_QUEUE_NAME'),
            'tries' => env('MKS_CMS_HOOKS_QUEUE_TRIES', 3),
            'backoff' => env('MKS_CMS_HOOKS_QUEUE_BACKOFF', 60),
            'timeout' => env('MKS_CMS_HOOKS_QUEUE_TIMEOUT', 120),
        ],
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
    | Country Dial Codes
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 country dial codes for phone number inputs.
    | Format: '+code' => 'Country Name (+code)'
    | You can override or extend this in your published config.
    |
    */
    'country_dial_codes' => [
        '+93' => 'Afghanistan (+93)',
        '+355' => 'Albania (+355)',
        '+213' => 'Algeria (+213)',
        '+376' => 'Andorra (+376)',
        '+244' => 'Angola (+244)',
        '+1264' => 'Anguilla (+1264)',
        '+1268' => 'Antigua and Barbuda (+1268)',
        '+54' => 'Argentina (+54)',
        '+374' => 'Armenia (+374)',
        '+297' => 'Aruba (+297)',
        '+61' => 'Australia (+61)',
        '+43' => 'Austria (+43)',
        '+994' => 'Azerbaijan (+994)',
        '+1242' => 'Bahamas (+1242)',
        '+973' => 'Bahrain (+973)',
        '+880' => 'Bangladesh (+880)',
        '+1246' => 'Barbados (+1246)',
        '+375' => 'Belarus (+375)',
        '+32' => 'Belgium (+32)',
        '+501' => 'Belize (+501)',
        '+229' => 'Benin (+229)',
        '+1441' => 'Bermuda (+1441)',
        '+975' => 'Bhutan (+975)',
        '+591' => 'Bolivia (+591)',
        '+387' => 'Bosnia and Herzegovina (+387)',
        '+267' => 'Botswana (+267)',
        '+55' => 'Brazil (+55)',
        '+673' => 'Brunei (+673)',
        '+359' => 'Bulgaria (+359)',
        '+226' => 'Burkina Faso (+226)',
        '+257' => 'Burundi (+257)',
        '+855' => 'Cambodia (+855)',
        '+237' => 'Cameroon (+237)',
        '+1' => 'Canada (+1)',
        '+238' => 'Cape Verde (+238)',
        '+1345' => 'Cayman Islands (+1345)',
        '+236' => 'Central African Republic (+236)',
        '+235' => 'Chad (+235)',
        '+56' => 'Chile (+56)',
        '+86' => 'China (+86)',
        '+57' => 'Colombia (+57)',
        '+269' => 'Comoros (+269)',
        '+242' => 'Congo (+242)',
        '+243' => 'DR Congo (+243)',
        '+682' => 'Cook Islands (+682)',
        '+506' => 'Costa Rica (+506)',
        '+385' => 'Croatia (+385)',
        '+53' => 'Cuba (+53)',
        '+357' => 'Cyprus (+357)',
        '+420' => 'Czech Republic (+420)',
        '+45' => 'Denmark (+45)',
        '+253' => 'Djibouti (+253)',
        '+1767' => 'Dominica (+1767)',
        '+1809' => 'Dominican Republic (+1809)',
        '+593' => 'Ecuador (+593)',
        '+20' => 'Egypt (+20)',
        '+503' => 'El Salvador (+503)',
        '+240' => 'Equatorial Guinea (+240)',
        '+291' => 'Eritrea (+291)',
        '+372' => 'Estonia (+372)',
        '+268' => 'Eswatini (+268)',
        '+251' => 'Ethiopia (+251)',
        '+500' => 'Falkland Islands (+500)',
        '+298' => 'Faroe Islands (+298)',
        '+679' => 'Fiji (+679)',
        '+358' => 'Finland (+358)',
        '+33' => 'France (+33)',
        '+594' => 'French Guiana (+594)',
        '+689' => 'French Polynesia (+689)',
        '+241' => 'Gabon (+241)',
        '+220' => 'Gambia (+220)',
        '+995' => 'Georgia (+995)',
        '+49' => 'Germany (+49)',
        '+233' => 'Ghana (+233)',
        '+350' => 'Gibraltar (+350)',
        '+30' => 'Greece (+30)',
        '+299' => 'Greenland (+299)',
        '+1473' => 'Grenada (+1473)',
        '+590' => 'Guadeloupe (+590)',
        '+1671' => 'Guam (+1671)',
        '+502' => 'Guatemala (+502)',
        '+44' => 'Guernsey (+44)',
        '+224' => 'Guinea (+224)',
        '+245' => 'Guinea-Bissau (+245)',
        '+592' => 'Guyana (+592)',
        '+509' => 'Haiti (+509)',
        '+504' => 'Honduras (+504)',
        '+852' => 'Hong Kong (+852)',
        '+36' => 'Hungary (+36)',
        '+354' => 'Iceland (+354)',
        '+91' => 'India (+91)',
        '+62' => 'Indonesia (+62)',
        '+98' => 'Iran (+98)',
        '+964' => 'Iraq (+964)',
        '+353' => 'Ireland (+353)',
        '+44' => 'Isle of Man (+44)',
        '+972' => 'Israel (+972)',
        '+39' => 'Italy (+39)',
        '+225' => 'Ivory Coast (+225)',
        '+1876' => 'Jamaica (+1876)',
        '+81' => 'Japan (+81)',
        '+44' => 'Jersey (+44)',
        '+962' => 'Jordan (+962)',
        '+7' => 'Kazakhstan (+7)',
        '+254' => 'Kenya (+254)',
        '+686' => 'Kiribati (+686)',
        '+383' => 'Kosovo (+383)',
        '+965' => 'Kuwait (+965)',
        '+996' => 'Kyrgyzstan (+996)',
        '+856' => 'Laos (+856)',
        '+371' => 'Latvia (+371)',
        '+961' => 'Lebanon (+961)',
        '+266' => 'Lesotho (+266)',
        '+231' => 'Liberia (+231)',
        '+218' => 'Libya (+218)',
        '+423' => 'Liechtenstein (+423)',
        '+370' => 'Lithuania (+370)',
        '+352' => 'Luxembourg (+352)',
        '+853' => 'Macau (+853)',
        '+389' => 'North Macedonia (+389)',
        '+261' => 'Madagascar (+261)',
        '+265' => 'Malawi (+265)',
        '+60' => 'Malaysia (+60)',
        '+960' => 'Maldives (+960)',
        '+223' => 'Mali (+223)',
        '+356' => 'Malta (+356)',
        '+692' => 'Marshall Islands (+692)',
        '+596' => 'Martinique (+596)',
        '+222' => 'Mauritania (+222)',
        '+230' => 'Mauritius (+230)',
        '+262' => 'Mayotte (+262)',
        '+52' => 'Mexico (+52)',
        '+691' => 'Micronesia (+691)',
        '+373' => 'Moldova (+373)',
        '+377' => 'Monaco (+377)',
        '+976' => 'Mongolia (+976)',
        '+382' => 'Montenegro (+382)',
        '+1664' => 'Montserrat (+1664)',
        '+212' => 'Morocco (+212)',
        '+258' => 'Mozambique (+258)',
        '+95' => 'Myanmar (+95)',
        '+264' => 'Namibia (+264)',
        '+674' => 'Nauru (+674)',
        '+977' => 'Nepal (+977)',
        '+31' => 'Netherlands (+31)',
        '+687' => 'New Caledonia (+687)',
        '+64' => 'New Zealand (+64)',
        '+505' => 'Nicaragua (+505)',
        '+227' => 'Niger (+227)',
        '+234' => 'Nigeria (+234)',
        '+683' => 'Niue (+683)',
        '+672' => 'Norfolk Island (+672)',
        '+1670' => 'Northern Mariana Islands (+1670)',
        '+47' => 'Norway (+47)',
        '+968' => 'Oman (+968)',
        '+92' => 'Pakistan (+92)',
        '+680' => 'Palau (+680)',
        '+970' => 'Palestine (+970)',
        '+507' => 'Panama (+507)',
        '+675' => 'Papua New Guinea (+675)',
        '+595' => 'Paraguay (+595)',
        '+51' => 'Peru (+51)',
        '+63' => 'Philippines (+63)',
        '+48' => 'Poland (+48)',
        '+351' => 'Portugal (+351)',
        '+1787' => 'Puerto Rico (+1787)',
        '+974' => 'Qatar (+974)',
        '+262' => 'Réunion (+262)',
        '+40' => 'Romania (+40)',
        '+7' => 'Russia (+7)',
        '+250' => 'Rwanda (+250)',
        '+590' => 'Saint Barthélemy (+590)',
        '+290' => 'Saint Helena (+290)',
        '+1869' => 'Saint Kitts and Nevis (+1869)',
        '+1758' => 'Saint Lucia (+1758)',
        '+590' => 'Saint Martin (+590)',
        '+508' => 'Saint Pierre and Miquelon (+508)',
        '+1784' => 'Saint Vincent and the Grenadines (+1784)',
        '+685' => 'Samoa (+685)',
        '+378' => 'San Marino (+378)',
        '+239' => 'São Tomé and Príncipe (+239)',
        '+966' => 'Saudi Arabia (+966)',
        '+221' => 'Senegal (+221)',
        '+381' => 'Serbia (+381)',
        '+248' => 'Seychelles (+248)',
        '+232' => 'Sierra Leone (+232)',
        '+65' => 'Singapore (+65)',
        '+1721' => 'Sint Maarten (+1721)',
        '+421' => 'Slovakia (+421)',
        '+386' => 'Slovenia (+386)',
        '+677' => 'Solomon Islands (+677)',
        '+252' => 'Somalia (+252)',
        '+27' => 'South Africa (+27)',
        '+211' => 'South Sudan (+211)',
        '+34' => 'Spain (+34)',
        '+94' => 'Sri Lanka (+94)',
        '+249' => 'Sudan (+249)',
        '+597' => 'Suriname (+597)',
        '+46' => 'Sweden (+46)',
        '+41' => 'Switzerland (+41)',
        '+963' => 'Syria (+963)',
        '+886' => 'Taiwan (+886)',
        '+992' => 'Tajikistan (+992)',
        '+255' => 'Tanzania (+255)',
        '+66' => 'Thailand (+66)',
        '+670' => 'Timor-Leste (+670)',
        '+228' => 'Togo (+228)',
        '+690' => 'Tokelau (+690)',
        '+676' => 'Tonga (+676)',
        '+1868' => 'Trinidad and Tobago (+1868)',
        '+216' => 'Tunisia (+216)',
        '+90' => 'Turkey (+90)',
        '+993' => 'Turkmenistan (+993)',
        '+1649' => 'Turks and Caicos (+1649)',
        '+688' => 'Tuvalu (+688)',
        '+256' => 'Uganda (+256)',
        '+380' => 'Ukraine (+380)',
        '+971' => 'UAE (+971)',
        '+44' => 'United Kingdom (+44)',
        '+1' => 'United States (+1)',
        '+598' => 'Uruguay (+598)',
        '+998' => 'Uzbekistan (+998)',
        '+678' => 'Vanuatu (+678)',
        '+379' => 'Vatican City (+379)',
        '+58' => 'Venezuela (+58)',
        '+84' => 'Vietnam (+84)',
        '+1284' => 'British Virgin Islands (+1284)',
        '+1340' => 'US Virgin Islands (+1340)',
        '+681' => 'Wallis and Futuna (+681)',
        '+212' => 'Western Sahara (+212)',
        '+967' => 'Yemen (+967)',
        '+260' => 'Zambia (+260)',
        '+263' => 'Zimbabwe (+263)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment targets (polymorphic)
    |--------------------------------------------------------------------------
    |
    | Models listed here appear in the Filament comment form and may receive
    | public comments via Livewire. Plugins (e.g. ecom) can merge Product into
    | this list at boot.
    |
    */
    'commentable_types' => [
        Post::class,
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

    /*
    |--------------------------------------------------------------------------
    | Admin console terminal (Filament)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Release archive (mks:release-archive)
    |--------------------------------------------------------------------------
    |
    | Theme/plugin trees are excluded by default. List project theme source paths
    | that must be in the zip (required when composer.json autoloads Themes\* from them).
    |
    */
    'release_archive' => [
        'include_theme_paths' => [],
    ],

    'console_terminal' => [
        'php_binary' => env('MKS_CONSOLE_PHP_BINARY'),
        'api_prefix' => env('MKS_CONSOLE_TERMINAL_API_PREFIX', 'admin/mksine/console'),
        'timeout_seconds' => (int) env('MKS_CONSOLE_TERMINAL_TIMEOUT', 300),
        'max_output_bytes' => (int) env('MKS_CONSOLE_TERMINAL_MAX_OUTPUT', 512_000),
        'stream_max_seconds' => (int) env('MKS_CONSOLE_TERMINAL_STREAM_MAX_SECONDS', 86_400),
        'default_output_height_px' => (int) env('MKS_CONSOLE_TERMINAL_HEIGHT', 500),
        'max_output_height_px' => (int) env('MKS_CONSOLE_TERMINAL_MAX_HEIGHT', 900),
        'status_poll_interval_ms' => (int) env('MKS_CONSOLE_TERMINAL_POLL_MS', 2000),
        'daemon_presets' => [
            ['label' => 'queue:work', 'command' => 'php artisan queue:work --tries=3'],
            ['label' => 'schedule:work', 'command' => 'php artisan schedule:work'],
            ['label' => 'queue:work (once)', 'command' => 'php artisan queue:work --once'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo import (mks:geo:import)
    |--------------------------------------------------------------------------
    |
    | Full imports dispatch queue jobs; city rows are processed per country.
    | Progress is written to storage/logs/mksine-geo-import/geo-import-{runId}.log
    | and to the application log with context run_id.
    |
    */
    'geo_import' => [
        'queue_connection' => env('MKS_GEO_IMPORT_QUEUE_CONNECTION'),
        'queue_name' => env('MKS_GEO_IMPORT_QUEUE_NAME'),
        'job_timeout' => (int) env('MKS_GEO_IMPORT_JOB_TIMEOUT', 3600),
        'memory_limit' => env('MKS_GEO_IMPORT_MEMORY_LIMIT', '512M'),
    ],
];
