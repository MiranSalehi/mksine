<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Translation;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Manages application translation files under lang_path().
 * Supports PHP files (lang/{locale}/*.php) and JSON (lang/{locale}.json).
 */
class TranslationFileManager
{
    private string $langPath;

    /** Locale code: 2-5 letters, optional _suffix (e.g. en, en_US, pt_BR). */
    private const LOCALE_REGEX = '/^[a-z]{2}(_[A-Za-z0-9]+)?$/';

    public function __construct(?string $langPath = null)
    {
        $this->langPath = $langPath ?? (function_exists('lang_path') ? lang_path() : base_path('lang'));
    }

    /**
     * List available locale codes (from subdirs and root JSON files).
     * If none exist, ensures the app's default locale (e.g. en) exists so the Languages page is usable.
     *
     * @return array<int, string>
     */
    public function getAvailableLocales(): array
    {
        $locales = [];

        if (! File::isDirectory($this->langPath)) {
            $this->ensureDefaultLocale();
        }

        if (File::isDirectory($this->langPath)) {
            foreach (File::directories($this->langPath) as $dir) {
                $name = basename($dir);
                if ($this->isValidLocaleCode($name)) {
                    $locales[] = $name;
                }
            }

            foreach (File::files($this->langPath) as $file) {
                if (strtolower($file->getExtension()) === 'json' && $this->isValidLocaleCode($file->getFilenameWithoutExtension())) {
                    $code = $file->getFilenameWithoutExtension();
                    if (! in_array($code, $locales, true)) {
                        $locales[] = $code;
                    }
                }
            }
        }

        if ($locales === []) {
            $this->ensureDefaultLocale();
            $default = $this->getDefaultLocaleCode();
            if ($default !== null) {
                $locales = [$default];
            }
        }

        sort($locales);

        return $locales;
    }

    /**
     * Ensure lang path exists and at least the default locale (e.g. en) has a file so it appears in the list.
     */
    protected function ensureDefaultLocale(): void
    {
        $code = $this->getDefaultLocaleCode();
        if ($code === null) {
            return;
        }

        if (! File::isDirectory($this->langPath)) {
            File::makeDirectory($this->langPath, 0755, true);
        }

        $jsonPath = $this->langPath . DIRECTORY_SEPARATOR . $code . '.json';
        if (! File::isFile($jsonPath)) {
            File::put($jsonPath, "{\n}\n");
        }
    }

    protected function getDefaultLocaleCode(): ?string
    {
        $code = config('app.locale', 'en');
        if (is_string($code) && $this->isValidLocaleCode($code)) {
            return $code;
        }

        return 'en';
    }

    /**
     * List translation files for a locale: PHP files in lang/{locale}/*.php and JSON lang/{locale}.json.
     * Keys: display name (e.g. "mksine.php"), values: file key for getTranslations (e.g. "mksine" for mksine.php, "json" for locale JSON).
     *
     * @return array<string, string> [ 'mksine.php' => 'mksine', 'validation.php' => 'validation', 'en.json' => 'json' ]
     */
    public function getFilesForLocale(string $locale): array
    {
        if (! $this->isValidLocaleCode($locale)) {
            return [];
        }

        $files = [];
        $dir = $this->langPath . DIRECTORY_SEPARATOR . $locale;
        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $file) {
                if (strtolower($file->getExtension()) === 'php') {
                    $name = $file->getFilename();
                    $key = $file->getFilenameWithoutExtension();
                    $files[$name] = $key;
                }
            }
        }

        $jsonPath = $this->langPath . DIRECTORY_SEPARATOR . $locale . '.json';
        if (File::isFile($jsonPath)) {
            $files[$locale . '.json'] = 'json';
        }

        ksort($files);

        return $files;
    }

    /**
     * Get translations for a locale and file as flattened key => value (e.g. "settings.title" => "Settings").
     *
     * @return array<string, string>
     */
    public function getTranslations(string $locale, string $fileKey): array
    {
        $path = $this->resolvePath($locale, $fileKey);
        if ($path === null || ! File::isFile($path)) {
            return [];
        }

        $data = $fileKey === 'json'
            ? json_decode(File::get($path), true)
            : (static function () use ($path) {
                return require $path;
            })();

        if (! is_array($data)) {
            return [];
        }

        if ($fileKey !== 'json') {
            $data = $this->repairCorruptedLangArrayStructure($data);
        }

        return Arr::dot($data);
    }

    /**
     * Save flattened translations for a locale and file.
     *
     * @param  array<string, string>  $translations  Flattened key => value
     */
    public function setTranslations(string $locale, string $fileKey, array $translations): void
    {
        $path = $this->resolvePath($locale, $fileKey);
        if ($path === null) {
            throw new \InvalidArgumentException("Invalid locale or file: {$locale} / {$fileKey}");
        }

        $translations = $fileKey === 'json'
            ? $translations
            : $this->normalizeKeyValueStateToFlatMap($translations);

        $data = Arr::undot($translations);

        $dir = dirname($path);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if ($fileKey === 'json') {
            $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            File::put($path, $encoded ?: '{}');
        } else {
            $php = "<?php\n\nreturn " . $this->exportArray($data) . ";\n";
            File::put($path, $php);
        }
    }

    /**
     * Read a PHP translation file as flattened key => value (dot notation).
     *
     * @return array<string, string>
     */
    public function readPhpTranslationFile(string $absolutePath): array
    {
        if (! File::isFile($absolutePath)) {
            return [];
        }

        $data = require $absolutePath;

        if (! is_array($data)) {
            return [];
        }

        $data = $this->repairCorruptedLangArrayStructure($data);

        return Arr::dot($data);
    }

    /**
     * Write flattened translations to an arbitrary PHP lang file (nested array on disk).
     *
     * @param  array<string, string>  $flatTranslations
     */
    public function writePhpTranslationFile(string $absolutePath, array $flatTranslations): void
    {
        $flatTranslations = $this->normalizeKeyValueStateToFlatMap($flatTranslations);

        $data = Arr::undot($flatTranslations);
        $dir = dirname($absolutePath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $php = "<?php\n\nreturn " . $this->exportArray($data) . ";\n";
        File::put($absolutePath, $php);
    }

    /**
     * Add a new locale: create directory and optionally copy from another locale.
     */
    public function addLocale(string $code, ?string $copyFrom = null): void
    {
        if (! $this->isValidLocaleCode($code)) {
            throw new \InvalidArgumentException("Invalid locale code: {$code}. Use 2-letter code, optionally with suffix (e.g. en, fa, pt_BR).");
        }

        $localePath = $this->langPath . DIRECTORY_SEPARATOR . $code;
        if (File::isDirectory($localePath)) {
            throw new \RuntimeException("Locale already exists: {$code}");
        }

        File::makeDirectory($localePath, 0755, true);

        if ($copyFrom !== null && $this->isValidLocaleCode($copyFrom)) {
            $fromPath = $this->langPath . DIRECTORY_SEPARATOR . $copyFrom;
            if (File::isDirectory($fromPath)) {
                foreach (File::files($fromPath) as $file) {
                    if (strtolower($file->getExtension()) === 'php') {
                        File::copy($file->getPathname(), $localePath . DIRECTORY_SEPARATOR . $file->getFilename());
                    }
                }
            }
            $fromJson = $this->langPath . DIRECTORY_SEPARATOR . $copyFrom . '.json';
            if (File::isFile($fromJson)) {
                File::copy($fromJson, $this->langPath . DIRECTORY_SEPARATOR . $code . '.json');
            }
        } else {
            // Create empty JSON so the locale appears in the list
            File::put($this->langPath . DIRECTORY_SEPARATOR . $code . '.json', "{\n}\n");
        }
    }

    /**
     * Remove a locale (delete directory and JSON file). Use with caution.
     */
    public function removeLocale(string $code): void
    {
        if (! $this->isValidLocaleCode($code)) {
            throw new \InvalidArgumentException("Invalid locale code: {$code}");
        }

        $localePath = $this->langPath . DIRECTORY_SEPARATOR . $code;
        if (File::isDirectory($localePath)) {
            File::deleteDirectory($localePath);
        }

        $jsonPath = $this->langPath . DIRECTORY_SEPARATOR . $code . '.json';
        if (File::isFile($jsonPath)) {
            File::delete($jsonPath);
        }
    }

    public function isValidLocaleCode(string $code): bool
    {
        return (bool) preg_match(self::LOCALE_REGEX, $code);
    }

    public function getLangPath(): string
    {
        return $this->langPath;
    }

    /**
     * Directory path used for a locale: lang_path() + locale (e.g. lang/en for PHP files).
     * Used to show in UI which directory's files are listed.
     */
    public function getLocaleDirectoryPath(string $locale): string
    {
        if (! $this->isValidLocaleCode($locale)) {
            return $this->langPath;
        }

        return $this->langPath . DIRECTORY_SEPARATOR . $locale;
    }

    /**
     * Turn Filament KeyValue / Livewire state into a flat map for Arr::undot().
     *
     * @param  mixed  $state
     * @return array<string, string>
     */
    public function normalizeKeyValueStateToFlatMap(mixed $state): array
    {
        if ($state === null || $state === []) {
            return [];
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);
            $state = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($state)) {
            return [];
        }

        if ($this->isListOfFilamentKeyValueRows($state)) {
            return $this->rowsToFlatMap($state);
        }

        if (isset($state['key'], $state['value']) && is_array($state['key']) && is_array($state['value'])
            && count($state) === 2) {
            return $this->parallelKeyValueArraysToFlatMap($state['key'], $state['value']);
        }

        $dottedPairMap = $this->repairFlatDottedKeyValuePairs($state);
        if ($dottedPairMap !== null) {
            return $dottedPairMap;
        }

        $out = [];
        foreach ($state as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            if (! is_string($k) && ! is_int($k)) {
                continue;
            }
            $ks = (string) $k;
            if ($ks === '') {
                continue;
            }
            $out[$ks] = is_scalar($v) ? (string) $v : '';
        }

        return $out;
    }

    /**
     * Fix lang files that were saved as Filament KeyValue row structures by mistake.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function repairCorruptedLangArrayStructure(array $data): array
    {
        if ($this->isListOfFilamentKeyValueRows($data)) {
            return $this->rowsToFlatMap($data);
        }

        if (isset($data['key'], $data['value']) && is_array($data['key']) && is_array($data['value'])
            && count($data) === 2) {
            return $this->parallelKeyValueArraysToFlatMap($data['key'], $data['value']);
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $state
     */
    private function isListOfFilamentKeyValueRows(array $state): bool
    {
        if ($state === []) {
            return false;
        }

        $keys = array_keys($state);
        $expected = range(0, count($state) - 1);
        if ($keys !== $expected) {
            return false;
        }

        foreach ($state as $row) {
            if (! is_array($row) || ! array_key_exists('key', $row) || ! array_key_exists('value', $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<mixed>  $rows
     * @return array<string, string>
     */
    private function rowsToFlatMap(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $k = $row['key'] ?? null;
            if ($k === null || $k === '') {
                continue;
            }
            $out[(string) $k] = is_scalar($row['value'] ?? null) ? (string) ($row['value'] ?? '') : '';
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $keys
     * @param  array<mixed>  $values
     * @return array<string, string>
     */
    private function parallelKeyValueArraysToFlatMap(array $keys, array $values): array
    {
        $out = [];
        foreach ($keys as $i => $k) {
            if ($k === null || $k === '') {
                continue;
            }
            $out[(string) $k] = is_scalar($values[$i] ?? null) ? (string) ($values[$i] ?? '') : '';
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $state
     * @return array<string, string>|null
     */
    private function repairFlatDottedKeyValuePairs(array $state): ?array
    {
        if ($state === []) {
            return null;
        }

        foreach (array_keys($state) as $k) {
            if (! is_string($k) || ! preg_match('/^(key|value)\.\d+$/', $k)) {
                return null;
            }
        }

        $out = [];
        foreach (array_keys($state) as $k) {
            if (preg_match('/^key\.(\d+)$/', $k, $m)) {
                $i = $m[1];
                $vk = 'value.' . $i;
                if (! isset($state[$vk])) {
                    continue;
                }
                $translationKey = $state[$k];
                if (! is_scalar($translationKey) || (string) $translationKey === '') {
                    continue;
                }
                $out[(string) $translationKey] = is_scalar($state[$vk]) ? (string) $state[$vk] : '';
            }
        }

        return $out !== [] ? $out : null;
    }

    private function resolvePath(string $locale, string $fileKey): ?string
    {
        if (! $this->isValidLocaleCode($locale)) {
            return null;
        }

        $realLang = realpath($this->langPath);
        if (! $realLang) {
            return null;
        }

        if ($fileKey === 'json') {
            $path = $this->langPath . DIRECTORY_SEPARATOR . $locale . '.json';
        } else {
            $base = basename($fileKey);
            if ($base !== $fileKey || preg_match('/[^a-zA-Z0-9_-]/', $fileKey)) {
                return null;
            }
            $path = $this->langPath . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $fileKey . '.php';
        }

        $resolved = realpath(dirname($path));
        if ($resolved === false || ! str_starts_with($resolved, $realLang)) {
            return null;
        }

        return $path;
    }

    /**
     * Export array to PHP string (nested arrays; scalars via var_export for correct escaping).
     */
    private function exportArray(array $data): string
    {
        $parts = [];
        foreach ($data as $k => $v) {
            $key = is_string($k) ? "'" . addslashes($k) . "'" : (string) $k;
            $val = is_array($v) ? $this->exportArray($v) : var_export($v, true);
            $parts[] = $key . ' => ' . $val;
        }

        return '[' . implode(', ', $parts) . ']';
    }
}
