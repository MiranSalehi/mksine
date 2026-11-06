<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use DateTimeInterface;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;

/**
 * Describes a published release for the download page: version, requirements and the
 * zip files with their sizes and SHA-256 checksums. Written as `release.json`, next to a
 * `SHA256SUMS` file that `sha256sum -c` can verify.
 */
final class ReleaseManifest
{
    public const FILE_NAME = 'release.json';

    public const CHECKSUMS_FILE_NAME = 'SHA256SUMS';

    /**
     * Layout => file name suffix and the audience it is for.
     *
     * @var array<string, array{suffix: string, for: string}>
     */
    public const LAYOUTS = [
        ReleaseArchiveOptions::LAYOUT_SPLIT => ['suffix' => 'shared-hosting', 'for' => 'shared-hosting'],
        ReleaseArchiveOptions::LAYOUT_SINGLE => ['suffix' => 'server', 'for' => 'server'],
    ];

    public static function fileName(string $version, string $layout): string
    {
        return 'mksine-' . $version . '-' . self::LAYOUTS[$layout]['suffix'] . '.zip';
    }

    /**
     * @param  array<string, string>  $files  Layout => absolute zip path.
     * @param  string|null  $installerPath  The stamped quick installer ({@see QuickInstaller}).
     * @return array<string, mixed>
     */
    public static function build(string $version, array $files, DateTimeInterface $releasedAt, ?string $installerPath = null): array
    {
        $entries = [];

        foreach ($files as $layout => $path) {
            $entries[] = [
                'name' => basename($path),
                'layout' => $layout,
                'for' => self::LAYOUTS[$layout]['for'],
                'size' => (int) filesize($path),
                'sha256' => (string) hash_file('sha256', $path),
            ];
        }

        $manifest = [
            'name' => 'MKSine',
            'version' => $version,
            'released_at' => $releasedAt->format(DATE_ATOM),
            'requirements' => [
                'php' => '>=' . RequirementsChecker::MINIMUM_PHP_VERSION,
                'extensions' => RequirementsChecker::REQUIRED_EXTENSIONS,
                'recommended_extensions' => RequirementsChecker::RECOMMENDED_EXTENSIONS,
                'databases' => ['mysql', 'mariadb', 'pgsql', 'sqlite'],
            ],
            'files' => $entries,
        ];

        if ($installerPath !== null) {
            $manifest['installer'] = [
                'name' => basename($installerPath),
                'size' => (int) filesize($installerPath),
                'sha256' => (string) hash_file('sha256', $installerPath),
            ];
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function checksums(array $manifest): string
    {
        $files = (array) $manifest['files'];

        if (isset($manifest['installer']) && is_array($manifest['installer'])) {
            $files[] = $manifest['installer'];
        }

        $lines = array_map(
            fn (array $file): string => $file['sha256'] . '  ' . $file['name'],
            $files,
        );

        return implode("\n", $lines) . "\n";
    }
}
