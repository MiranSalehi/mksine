<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use InvalidArgumentException;

/**
 * What goes into a release zip and how it is laid out.
 *
 * - single: the project at the zip root; point the web server's document root at public/.
 * - split: shared hosting; the project goes in {appDirectory}/ and the contents of public/
 *   go in {publicDirectory}/ with an index.php that loads ../{appDirectory}.
 */
final readonly class ReleaseArchiveOptions
{
    public const LAYOUT_SINGLE = 'single';

    public const LAYOUT_SPLIT = 'split';

    public function __construct(
        public bool $includeExtensions = false,
        public string $layout = self::LAYOUT_SINGLE,
        public string $appDirectory = 'mksine',
        public string $publicDirectory = 'public_html',
    ) {
        if (! in_array($layout, [self::LAYOUT_SINGLE, self::LAYOUT_SPLIT], true)) {
            throw new InvalidArgumentException("Unknown layout [{$layout}]. Use single or split.");
        }

        foreach (['app' => $appDirectory, 'public' => $publicDirectory] as $label => $directory) {
            if (preg_match('/^[A-Za-z0-9._-]+$/', $directory) !== 1 || in_array($directory, ['.', '..'], true)) {
                throw new InvalidArgumentException("Invalid {$label} directory name [{$directory}].");
            }
        }

        if ($appDirectory === $publicDirectory) {
            throw new InvalidArgumentException('The app and public directories must have different names.');
        }
    }

    public function isSplit(): bool
    {
        return $this->layout === self::LAYOUT_SPLIT;
    }

    /**
     * Zip entry name for a project-relative path, or null to leave the file out.
     */
    public function entryName(string $relativePath): ?string
    {
        if (! $this->isSplit()) {
            return $relativePath;
        }

        // Shared hosting runs no Composer, so the path-repository source is not needed:
        // autoload points at vendor/miran/mksine, which is packed from the symlink target.
        if ($relativePath === 'packages/mksine' || str_starts_with($relativePath, 'packages/mksine/')) {
            return null;
        }

        if ($relativePath === 'public/index.php') {
            return null;
        }

        if (str_starts_with($relativePath, 'public/')) {
            return $this->publicDirectory . '/' . substr($relativePath, strlen('public/'));
        }

        return $this->appDirectory . '/' . $relativePath;
    }

    /**
     * Files generated for the split layout, keyed by zip entry name.
     *
     * @return array<string, string>
     */
    public function generatedFiles(string $projectRoot): array
    {
        if (! $this->isSplit()) {
            return [];
        }

        $files = [
            $this->publicDirectory . '/index.php' => $this->splitIndexPhp(),
            $this->appDirectory . '/.htaccess' => "# The application must never be served directly.\nRequire all denied\n",
        ];

        $example = rtrim($projectRoot, '/\\') . '/.env.example';
        if (is_file($example)) {
            $files[$this->appDirectory . '/.env.example'] = rtrim((string) file_get_contents($example), "\n")
                . "\n\n# Shared hosting: public files live in ../{$this->publicDirectory}\nMKSINE_PUBLIC_PATH=../{$this->publicDirectory}\n";
        }

        return $files;
    }

    /**
     * Entries the generated files replace, so they are not added twice.
     *
     * @return list<string>
     */
    public function replacedRelativePaths(): array
    {
        return $this->isSplit() ? ['.env.example'] : [];
    }

    public function splitIndexPhp(): string
    {
        $app = $this->appDirectory;

        return <<<PHP
        <?php

        use Illuminate\Foundation\Application;
        use Illuminate\Http\Request;

        define('LARAVEL_START', microtime(true));

        // The application lives next to this folder (shared hosting layout).
        \$appPath = dirname(__DIR__).'/{$app}';

        if (file_exists(\$maintenance = \$appPath.'/storage/framework/maintenance.php')) {
            require \$maintenance;
        }

        require \$appPath.'/vendor/autoload.php';

        /** @var Application \$app */
        \$app = require_once \$appPath.'/bootstrap/app.php';

        \$app->usePublicPath(__DIR__);

        \$app->handleRequest(Request::capture());

        PHP;
    }
}
