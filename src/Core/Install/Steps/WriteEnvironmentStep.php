<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Illuminate\Support\Facades\DB;
use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

/**
 * Writes requested .env keys and an APP_KEY, then applies them to the running config so
 * later steps in the same process use the new database.
 */
class WriteEnvironmentStep extends AbstractInstallStep
{
    /**
     * .env key => config key, for values later steps need without a reboot.
     *
     * @var array<string, string>
     */
    private const RUNTIME_CONFIG = [
        'APP_KEY' => 'app.key',
        'APP_URL' => 'app.url',
        'APP_NAME' => 'app.name',
        'APP_LOCALE' => 'app.locale',
        'MKSINE_PUBLIC_PATH' => 'mksine.public_path',
    ];

    /** @var array<string, string> */
    private const DATABASE_KEYS = [
        'DB_HOST' => 'host',
        'DB_PORT' => 'port',
        'DB_DATABASE' => 'database',
        'DB_USERNAME' => 'username',
        'DB_PASSWORD' => 'password',
    ];

    public function key(): string
    {
        return 'environment';
    }

    public function run(InstallContext $context): StepResult
    {
        $path = app()->environmentFilePath();
        $hasKey = filled(config('app.key'));

        if ($context->environment === [] && $hasKey && is_file($path)) {
            return StepResult::skipped($this->message('environment_unchanged'));
        }

        $editor = EnvironmentFileEditor::openOrCreate($path, dirname($path) . '/.env.example');
        $values = $context->environment;

        if (blank($editor->get('APP_KEY')) && ! isset($values['APP_KEY'])) {
            $values['APP_KEY'] = $hasKey ? (string) config('app.key') : EnvironmentFileEditor::generateAppKey();
        }

        $editor->set($values);
        $this->applyToRuntime($values);

        return StepResult::completed([$this->message('environment_written', ['count' => count($values)])]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function applyToRuntime(array $values): void
    {
        foreach (self::RUNTIME_CONFIG as $envKey => $configKey) {
            if (array_key_exists($envKey, $values)) {
                config([$configKey => $values[$envKey]]);
            }
        }

        $connection = $values['DB_CONNECTION'] ?? null;
        $databaseValues = array_intersect_key($values, self::DATABASE_KEYS);

        if ($connection === null && $databaseValues === []) {
            return;
        }

        $connection ??= (string) config('database.default');
        config(['database.default' => $connection]);

        foreach ($databaseValues as $envKey => $value) {
            config(["database.connections.{$connection}." . self::DATABASE_KEYS[$envKey] => $value]);
        }

        DB::purge($connection);
    }
}
