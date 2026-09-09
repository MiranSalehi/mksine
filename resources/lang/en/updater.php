<?php

return [
    'update_plugin' => 'Update a Plugin',
    'update_theme' => 'Update a Theme',

    'select_plugin' => 'Plugin to update',
    'select_theme' => 'Theme to update',

    'zip_file' => 'Update ZIP file',
    'plugin_zip_helper' => 'Upload the pre-built plugin ZIP. Version must be higher than the currently installed version unless Force is enabled.',
    'theme_zip_helper' => 'Upload the pre-built theme ZIP including dist/. Version must be higher than the currently installed version unless Force is enabled.',

    'force_toggle' => 'Force (override version guard)',
    'force_helper' => 'Allow same-version reinstalls and downgrades. Only use during recovery; the default rejects both.',

    'plugin_risk_label' => 'About plugin updates',
    'plugin_risk_body' => 'This backs up the current plugin, swaps in the new version, publishes assets and translations, then runs migrations last. If the plugin is active it is marked installed (the plugin deactivate hook is not run); you must re-activate it on the next request so the autoloader picks up the new code.',

    'theme_risk_label' => 'About theme updates',
    'theme_risk_body' => 'This backs up the current theme, swaps in the new version, and republishes its assets and translations. Active themes stay active; views refresh on next render.',

    'core_title' => 'System Update',
    'core_navigation_label' => 'System Update',
    'core_subheading' => 'Installed core version: :version',
    'core_current_version_heading' => 'Current core version',
    'core_current_version_label' => 'Version',
    'core_composer_intro' => 'Update miran/mksine with Composer so composer.lock and the autoloader stay in sync. Replacing vendor/ or packages/mksine from a ZIP is not supported.',
    'core_path_composer_heading' => 'When Composer is on the server',
    'core_path_composer_body' => 'Run these from the application root (SSH or a deploy job). Then clear caches if your host uses config/route/view cache.',
    'core_cli_equivalent' => 'Or one command that runs Composer then publishes package migrations:',
    'core_composer_missing' => 'Composer was not found on this server. Use the offline path below, or install Composer and retry.',
    'core_console_warning' => 'The admin console can run composer commands, but a long update may hit the HTTP timeout. Prefer SSH.',
    'core_console_link' => 'Open Console Terminal',
    'core_path_offline_heading' => 'When Composer is not on the server',
    'core_path_offline_body' => 'On a build machine that has Composer, run composer update miran/mksine, then deploy at least these paths so the lockfile and autoloader match:',
    'core_path_repo_note' => 'Path-repository installs: git-pull (or otherwise update) packages/mksine first. composer update will not pull that git tree for you.',
    'core_release_archive_note' => 'php artisan mks:release-archive packages the full deployable tree (including vendor/) for hosts without Composer. It is not a core-only ZIP.',

    'plugin_update_title' => 'Plugin update',
    'plugin_rollback_title' => 'Plugin rollback',
    'theme_update_title' => 'Theme update',
    'theme_rollback_title' => 'Theme rollback',

    'upload_failed' => 'Upload failed',
    'invalid_upload' => 'The uploaded file is invalid or missing.',
    'update_failed' => 'Update failed',

    'result_success_heading' => 'Update succeeded',
    'result_failure_heading' => 'Update failed',
    'result_versions_label' => 'Versions',
    'result_steps_label' => 'Steps',
    'result_warnings_label' => 'Warnings',
    'result_error_label' => 'Error',
    'result_log_label' => 'Log file',
    'result_backup_label' => 'Backup',
    'result_db_dirty_heading' => 'Database may be partially migrated',
    'result_db_dirty_body' => 'A post-swap migration failed. The target is running the new CODE but the DB state is unknown. Inspect the log and run migrations manually or roll back the code and restore a DB snapshot.',

    'rollback' => 'Rollback',
    'rollback_confirm_title' => 'Roll back?',
    'rollback_confirm_body' => 'Restores the most recent backup. Migrations are NOT reversed.',
];
