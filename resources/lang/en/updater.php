<?php

return [
    'update_plugin' => 'Update a Plugin',
    'update_theme' => 'Update a Theme',
    'upload_core' => 'Upload Core Update',

    'select_plugin' => 'Plugin to update',
    'select_theme' => 'Theme to update',

    'zip_file' => 'Update ZIP file',
    'plugin_zip_helper' => 'Upload the pre-built plugin ZIP. Version must be higher than the currently installed version.',
    'theme_zip_helper' => 'Upload the pre-built theme ZIP including dist/. Version must be higher than the currently installed version.',
    'core_zip_helper' => 'Upload the pre-built core miran/mksine ZIP. Composer dependencies must be identical to the currently installed version.',

    'force_toggle' => 'Force (override version guard)',
    'force_helper' => 'Allow same-version reinstalls and downgrades. Only use during recovery; the default rejects both.',

    'plugin_risk_label' => 'About plugin updates',
    'plugin_risk_body' => 'This will back up the current plugin, swap in the new version, publish assets and translations, then run migrations last. If the plugin is active it will be deactivated; you must re-activate it in the next request so the autoloader picks up the new code cleanly.',

    'theme_risk_label' => 'About theme updates',
    'theme_risk_body' => 'This will back up the current theme, swap in the new version, and republish its assets and translations. Active themes stay active; views refresh on next render.',

    'core_risk_label' => 'About core updates',
    'core_risk_body' => 'This replaces packages/mksine in place. For maximum safety run the CLI command below via SSH instead. ZIPs that change Composer dependencies are rejected because this server has no composer access.',

    'core_title' => 'System Update',
    'core_navigation_label' => 'System Update',
    'core_subheading' => 'Installed core version: :version',
    'core_current_version_heading' => 'Current core version',
    'core_current_version_label' => 'Version',
    'core_cli_recommended' => 'Recommended: run this from an SSH session so replacement happens in a fresh process.',

    'plugin_update_title' => 'Plugin update',
    'plugin_rollback_title' => 'Plugin rollback',
    'theme_update_title' => 'Theme update',
    'theme_rollback_title' => 'Theme rollback',

    'upload_failed' => 'Upload failed',
    'invalid_upload' => 'The uploaded file is invalid or missing.',
    'update_failed' => 'Update failed',
    'core_updated' => 'Core updated',
    'core_updated_body' => 'miran/mksine :from → :to. Remember to clear browser and opcache if needed.',

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
