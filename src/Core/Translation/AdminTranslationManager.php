<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Translation;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use Miran\Mksine\Core\Plugins\PluginManifest;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Theme\ThemeData;
use Miran\Mksine\Core\Theme\ThemeManager;

/**
 * Aggregates translation files for the Languages admin page: application lang/,
 * published + source paths for plugins and themes. Saves plugin/theme source
 * then copies only the edited PHP file into lang/vendor so runtime picks up changes.
 */
final class AdminTranslationManager
{
    private const SOURCE_APP = 'app';

    private const PLUGIN_PREFIX = 'plugin:';

    private const THEME_PREFIX = 'theme:';

    public function __construct(
        private TranslationFileManager $files,
        private PluginManager $pluginManager,
        private ThemeManager $themeManager,
    ) {}

    /**
     * Options for the "translation source" select (value => label).
     *
     * @return array<string, string>
     */
    public function getSourceOptions(?string $locale): array
    {
        $options = [];
        $options[self::SOURCE_APP] = __('mksine::languages.source_application');

        if ($locale === null || $locale === '' || ! $this->files->isValidLocaleCode($locale)) {
            return $options;
        }

        $this->pluginManager->initialize();

        foreach ($this->pluginManager->getRegistry()->getManifests() as $pluginId => $manifest) {
            if ($this->pluginHasPhpFilesForLocale($manifest, $locale)) {
                $options[self::PLUGIN_PREFIX . $pluginId] = __('mksine::languages.source_plugin', [
                    'name' => $manifest->name(),
                ]);
            }
        }

        foreach ($this->themeManager->discover() as $identifier => $theme) {
            if ($this->themeHasPhpFilesForLocale($theme, $locale)) {
                $options[self::THEME_PREFIX . $identifier] = __('mksine::languages.source_theme', [
                    'name' => $theme->name,
                ]);
            }
        }

        return $options;
    }

    /**
     * PHP (and JSON for app-only) files for the locale and source.
     *
     * @return array<string, string> display => fileKey
     */
    public function getFilesForLocaleAndSource(string $locale, string $source): array
    {
        if (! $this->files->isValidLocaleCode($locale)) {
            return [];
        }

        if ($source === self::SOURCE_APP) {
            return $this->files->getFilesForLocale($locale);
        }

        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $pluginId = substr($source, strlen(self::PLUGIN_PREFIX));
            if (! $this->isSafeSegment($pluginId)) {
                return [];
            }
            $this->pluginManager->initialize();
            $manifest = $this->pluginManager->getManifest($pluginId);
            if (! $manifest) {
                return [];
            }

            return $this->mergedPhpFilesForLocale($this->pluginLocaleDirCandidates($manifest, $locale));
        }

        if (str_starts_with($source, self::THEME_PREFIX)) {
            $identifier = substr($source, strlen(self::THEME_PREFIX));
            if (! $this->isSafeSegment($identifier)) {
                return [];
            }
            $theme = $this->themeManager->get($identifier);
            if (! $theme) {
                return [];
            }

            return $this->mergedPhpFilesForLocale($this->themeLocaleDirCandidates($theme, $locale));
        }

        return [];
    }

    /**
     * Human-readable directory path shown in the UI (source folder when available).
     */
    public function getFilesDirectoryHint(string $locale, string $source): string
    {
        if (! $this->files->isValidLocaleCode($locale)) {
            return $this->files->getLangPath();
        }

        if ($source === self::SOURCE_APP) {
            return $this->files->getLocaleDirectoryPath($locale);
        }

        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $pluginId = substr($source, strlen(self::PLUGIN_PREFIX));
            $this->pluginManager->initialize();
            $manifest = $this->pluginManager->getManifest($pluginId);
            if (! $manifest) {
                return $this->files->getLangPath();
            }
            $src = $manifest->translationsPath();
            if ($src) {
                return $src . DIRECTORY_SEPARATOR . $locale;
            }

            return lang_path('vendor/' . $pluginId . DIRECTORY_SEPARATOR . $locale);
        }

        if (str_starts_with($source, self::THEME_PREFIX)) {
            $identifier = substr($source, strlen(self::THEME_PREFIX));
            $theme = $this->themeManager->get($identifier);
            if (! $theme) {
                return $this->files->getLangPath();
            }
            $src = $this->themeManager->getThemeTranslationsPath($theme);
            if ($src) {
                return $src . DIRECTORY_SEPARATOR . $locale;
            }

            return lang_path('vendor/theme-' . $identifier . DIRECTORY_SEPARATOR . $locale);
        }

        return $this->files->getLangPath();
    }

    /**
     * @return array<string, string>
     */
    public function getTranslations(string $locale, string $source, string $fileKey): array
    {
        $this->assertValidFileKey($fileKey);

        if ($source === self::SOURCE_APP) {
            return $this->files->getTranslations($locale, $fileKey);
        }

        $path = $this->resolvePluginOrThemeReadPath($locale, $source, $fileKey);
        if ($path === null) {
            return [];
        }

        return $this->files->readPhpTranslationFile($path);
    }

    /**
     * @param  array<string, string>  $translations
     */
    public function setTranslations(string $locale, string $source, string $fileKey, array $translations): void
    {
        $this->assertValidFileKey($fileKey);

        if ($source === self::SOURCE_APP) {
            $this->files->setTranslations($locale, $fileKey, $translations);

            return;
        }

        $writePath = $this->resolvePluginOrThemeWritePath($locale, $source, $fileKey);
        if ($writePath === null) {
            throw new InvalidArgumentException(__('mksine::languages.cannot_resolve_translation_path'));
        }

        $this->files->writePhpTranslationFile($writePath, $translations);

        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $pluginId = substr($source, strlen(self::PLUGIN_PREFIX));
            if (! $this->copyPluginTranslationFileToVendor($pluginId, $locale, $fileKey, $writePath)) {
                throw new RuntimeException(__('mksine::languages.plugin_publish_failed', ['plugin' => $pluginId]));
            }
        } elseif (str_starts_with($source, self::THEME_PREFIX)) {
            $identifier = substr($source, strlen(self::THEME_PREFIX));
            if (! $this->copyThemeTranslationFileToVendor($identifier, $locale, $fileKey, $writePath)) {
                throw new RuntimeException(__('mksine::languages.theme_publish_failed', ['theme' => $identifier]));
            }
        }
    }

    private function copyPluginTranslationFileToVendor(string $pluginId, string $locale, string $fileKey, string $sourceAbsolutePath): bool
    {
        if (! function_exists('lang_path') || ! File::isFile($sourceAbsolutePath)) {
            return false;
        }

        $destination = lang_path('vendor/' . $pluginId . '/' . $locale . '/' . $fileKey . '.php');
        File::ensureDirectoryExists(dirname($destination));

        return File::copy($sourceAbsolutePath, $destination);
    }

    private function copyThemeTranslationFileToVendor(string $identifier, string $locale, string $fileKey, string $sourceAbsolutePath): bool
    {
        if (! function_exists('lang_path') || ! File::isFile($sourceAbsolutePath)) {
            return false;
        }

        $destination = lang_path('vendor/theme-' . $identifier . '/' . $locale . '/' . $fileKey . '.php');
        File::ensureDirectoryExists(dirname($destination));

        return File::copy($sourceAbsolutePath, $destination);
    }

    public function isAllowedSource(string $source): bool
    {
        if ($source === self::SOURCE_APP) {
            return true;
        }

        $this->pluginManager->initialize();

        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $id = substr($source, strlen(self::PLUGIN_PREFIX));

            return $this->isSafeSegment($id) && $this->pluginManager->getManifest($id) !== null;
        }

        if (str_starts_with($source, self::THEME_PREFIX)) {
            $id = substr($source, strlen(self::THEME_PREFIX));

            return $this->isSafeSegment($id) && $this->themeManager->get($id) !== null;
        }

        return false;
    }

    private function pluginHasPhpFilesForLocale(PluginManifest $manifest, string $locale): bool
    {
        return $this->mergedPhpFilesForLocale($this->pluginLocaleDirCandidates($manifest, $locale)) !== [];
    }

    private function themeHasPhpFilesForLocale(ThemeData $theme, string $locale): bool
    {
        return $this->mergedPhpFilesForLocale($this->themeLocaleDirCandidates($theme, $locale)) !== [];
    }

    /**
     * @param  list<string|null>  $directories
     * @return array<string, string>
     */
    private function mergedPhpFilesForLocale(array $directories): array
    {
        $merged = [];
        foreach ($directories as $dir) {
            if ($dir === null || $dir === '' || ! File::isDirectory($dir)) {
                continue;
            }
            foreach (File::files($dir) as $file) {
                if (strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $merged[$file->getFilename()] = $file->getFilenameWithoutExtension();
            }
        }
        ksort($merged);

        return $merged;
    }

    /**
     * @return list<string|null>
     */
    private function pluginLocaleDirCandidates(PluginManifest $manifest, string $locale): array
    {
        $published = function_exists('lang_path')
            ? lang_path('vendor/' . $manifest->id() . DIRECTORY_SEPARATOR . $locale)
            : null;
        $src = $manifest->translationsPath();
        $srcLocale = $src ? $src . DIRECTORY_SEPARATOR . $locale : null;

        return [$srcLocale, $published];
    }

    /**
     * @return list<string|null>
     */
    private function themeLocaleDirCandidates(ThemeData $theme, string $locale): array
    {
        $src = $this->themeManager->getThemeTranslationsPath($theme);
        $srcLocale = $src ? $src . DIRECTORY_SEPARATOR . $locale : null;
        $published = function_exists('lang_path')
            ? lang_path('vendor/theme-' . $theme->identifier . DIRECTORY_SEPARATOR . $locale)
            : null;

        return [$srcLocale, $published];
    }

    private function resolvePluginOrThemeReadPath(string $locale, string $source, string $fileKey): ?string
    {
        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $pluginId = substr($source, strlen(self::PLUGIN_PREFIX));
            $this->pluginManager->initialize();
            $manifest = $this->pluginManager->getManifest($pluginId);
            if (! $manifest) {
                return null;
            }
            $src = $manifest->translationsPath();
            $srcFile = $src ? $src . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php' : null;
            $publishedFile = lang_path('vendor/' . $pluginId . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php');

            if ($srcFile && File::isFile($srcFile)) {
                return $srcFile;
            }
            if (File::isFile($publishedFile)) {
                return $publishedFile;
            }

            return $srcFile;
        }

        if (str_starts_with($source, self::THEME_PREFIX)) {
            $identifier = substr($source, strlen(self::THEME_PREFIX));
            $theme = $this->themeManager->get($identifier);
            if (! $theme) {
                return null;
            }
            $src = $this->themeManager->getThemeTranslationsPath($theme);
            $srcFile = $src ? $src . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php' : null;
            $publishedFile = lang_path('vendor/theme-' . $identifier . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php');

            if ($srcFile && File::isFile($srcFile)) {
                return $srcFile;
            }
            if (File::isFile($publishedFile)) {
                return $publishedFile;
            }

            return $srcFile;
        }

        return null;
    }

    private function resolvePluginOrThemeWritePath(string $locale, string $source, string $fileKey): ?string
    {
        if (str_starts_with($source, self::PLUGIN_PREFIX)) {
            $pluginId = substr($source, strlen(self::PLUGIN_PREFIX));
            $this->pluginManager->initialize();
            $manifest = $this->pluginManager->getManifest($pluginId);
            if (! $manifest) {
                return null;
            }
            $src = $manifest->translationsPath();
            if (! $src) {
                return null;
            }

            return $src . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php';
        }

        if (str_starts_with($source, self::THEME_PREFIX)) {
            $identifier = substr($source, strlen(self::THEME_PREFIX));
            $theme = $this->themeManager->get($identifier);
            if (! $theme) {
                return null;
            }
            $src = $this->themeManager->getThemeTranslationsPath($theme);
            if (! $src) {
                return null;
            }

            return $src . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php';
        }

        return null;
    }

    private function assertValidFileKey(string $fileKey): void
    {
        if ($fileKey === 'json') {
            return;
        }
        $base = basename($fileKey);
        if ($base !== $fileKey || preg_match('/[^a-zA-Z0-9_-]/', $fileKey)) {
            throw new InvalidArgumentException('Invalid translation file key.');
        }
    }

    private function isSafeSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $segment);
    }
}
