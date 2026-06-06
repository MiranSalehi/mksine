<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Lang;

/**
 * Resolves translatable plugin manifest labels from resources/lang/{locale}/manifest.php
 * (namespace {@see PluginManifest::id()}::manifest.*), with fallback to plugin.php values.
 */
final class PluginManifestTranslator
{
    /** @var array<string, true> */
    private array $registeredNamespaces = [];

    public function __construct(
        private readonly Loader $loader,
    ) {}

    public function registerNamespace(PluginManifest $manifest): void
    {
        $id = $manifest->id();

        if (isset($this->registeredNamespaces[$id])) {
            return;
        }

        $path = $this->resolveTranslationPath($manifest);

        if ($path !== null) {
            $this->loader->addNamespace($id, $path);
        }

        $this->registeredNamespaces[$id] = true;
    }

    public function name(PluginManifest $manifest, ?string $locale = null): string
    {
        $this->registerNamespace($manifest);

        $key = $manifest->id().'::manifest.name';
        $translated = Lang::get($key, [], $locale);

        return is_string($translated) && $translated !== $key
            ? $translated
            : $manifest->name();
    }

    public function description(PluginManifest $manifest, ?string $locale = null): ?string
    {
        $fallback = $manifest->description();

        if ($fallback === null || $fallback === '') {
            return null;
        }

        $this->registerNamespace($manifest);

        $key = $manifest->id().'::manifest.description';
        $translated = Lang::get($key, [], $locale);

        return is_string($translated) && $translated !== $key
            ? $translated
            : $fallback;
    }

    private function resolveTranslationPath(PluginManifest $manifest): ?string
    {
        if (function_exists('lang_path')) {
            $published = lang_path('vendor/'.$manifest->id());

            if (is_dir($published)) {
                return $published;
            }
        }

        return $manifest->translationsPath();
    }
}
