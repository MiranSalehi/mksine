<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

use Illuminate\Contracts\Foundation\Application;
use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;
use Throwable;

/**
 * Lets a freshly uploaded site boot far enough to show the web installer.
 *
 * Before installation there may be no .env, no APP_KEY and no database tables, so
 * sessions, cache and queue are switched to drivers that need none of them, and an
 * APP_KEY is generated (and saved to .env when it is writable).
 */
final class PreInstallBootstrap
{
    /**
     * The session cookie name is pinned because Laravel derives it from APP_NAME, which the
     * installer changes; otherwise the browser would lose its installer session mid-way.
     *
     * @var array<string, string>
     */
    public const RUNTIME_DRIVERS = [
        'session.driver' => 'file',
        'session.cookie' => 'mksine_installer_session',
        'cache.default' => 'file',
        'queue.default' => 'sync',
    ];

    public static function apply(Application $app, InstallationState $installation): void
    {
        if ($installation->isInstalled()) {
            return;
        }

        $config = $app->make('config');

        if (blank($config->get('app.key'))) {
            $config->set('app.key', self::generateAndStoreAppKey($app));
        }

        foreach (self::RUNTIME_DRIVERS as $key => $driver) {
            $config->set($key, $driver);
        }
    }

    private static function generateAndStoreAppKey(Application $app): string
    {
        $key = EnvironmentFileEditor::generateAppKey();
        $path = $app->environmentFilePath();

        try {
            $editor = EnvironmentFileEditor::openOrCreate($path, dirname($path) . '/.env.example');

            if (filled($editor->get('APP_KEY'))) {
                return (string) $editor->get('APP_KEY');
            }

            $editor->set(['APP_KEY' => $key]);
        } catch (Throwable) {
            // The installer's requirements page reports an unwritable .env.
        }

        return $key;
    }
}
