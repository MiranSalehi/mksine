<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Environment;

use Closure;

/**
 * Checks PHP version, extensions and writable paths.
 *
 * Has no Laravel dependency, so the standalone web installer can run it before the
 * application boots (vendor/autoload.php is enough).
 */
final class RequirementsChecker
{
    public const MINIMUM_PHP_VERSION = '8.2.0';

    /** @var list<string> */
    public const REQUIRED_EXTENSIONS = ['ctype', 'curl', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'];

    /** @var list<string> */
    public const RECOMMENDED_EXTENSIONS = ['intl', 'sodium', 'gd'];

    /** @var list<string> */
    public const DATABASE_DRIVER_EXTENSIONS = ['pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'pdo_sqlsrv'];

    private readonly Closure $extensionLoaded;

    private readonly Closure $isWritable;

    /**
     * @param  array<string, string>  $writablePaths  Label => absolute path.
     */
    public function __construct(
        private readonly array $writablePaths,
        ?Closure $extensionLoaded = null,
        ?Closure $isWritable = null,
        private readonly string $phpVersion = PHP_VERSION,
    ) {
        $this->extensionLoaded = $extensionLoaded ?? extension_loaded(...);
        $this->isWritable = $isWritable ?? is_writable(...);
    }

    /**
     * @return array<string, string>
     */
    public static function writablePathsFor(string $basePath, string $storagePath, string $publicPath): array
    {
        return [
            'storage' => $storagePath,
            'bootstrap/cache' => rtrim($basePath, '/\\') . '/bootstrap/cache',
            'public' => $publicPath,
            '.env' => is_file($basePath . '/.env') ? $basePath . '/.env' : $basePath,
        ];
    }

    /**
     * @return list<RequirementCheck>
     */
    public function check(): array
    {
        $checks = [
            new RequirementCheck(
                key: 'php',
                labelKey: 'requirements.php',
                passed: version_compare($this->phpVersion, self::MINIMUM_PHP_VERSION, '>='),
                current: $this->phpVersion,
                parameters: ['version' => self::MINIMUM_PHP_VERSION],
            ),
        ];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks[] = $this->extensionCheck($extension, required: true);
        }

        $drivers = array_values(array_filter(self::DATABASE_DRIVER_EXTENSIONS, fn (string $driver): bool => ($this->extensionLoaded)($driver)));
        $checks[] = new RequirementCheck(
            key: 'database-driver',
            labelKey: 'requirements.database_driver',
            passed: $drivers !== [],
            current: $drivers === [] ? null : implode(', ', $drivers),
        );

        foreach (self::RECOMMENDED_EXTENSIONS as $extension) {
            $checks[] = $this->extensionCheck($extension, required: false);
        }

        foreach ($this->writablePaths as $label => $path) {
            $checks[] = new RequirementCheck(
                key: 'writable:' . $label,
                labelKey: 'requirements.writable',
                passed: ($this->isWritable)($path),
                current: $path,
                parameters: ['path' => $label],
            );
        }

        return $checks;
    }

    public function passes(): bool
    {
        return $this->blocking() === [];
    }

    /**
     * @return list<RequirementCheck>
     */
    public function blocking(): array
    {
        return array_values(array_filter($this->check(), fn (RequirementCheck $check): bool => $check->isBlocking()));
    }

    private function extensionCheck(string $extension, bool $required): RequirementCheck
    {
        return new RequirementCheck(
            key: 'ext:' . $extension,
            labelKey: 'requirements.extension',
            passed: ($this->extensionLoaded)($extension),
            required: $required,
            parameters: ['name' => $extension],
        );
    }
}
