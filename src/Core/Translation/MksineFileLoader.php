<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Translation;

use Illuminate\Translation\FileLoader;

/**
 * Loads `mksine::` groups from the package, then merges `lang/{locale}/{group}.php`
 * so new package keys stay available while the Languages admin and publishes keep editing project files.
 */
final class MksineFileLoader extends FileLoader
{
    protected function loadNamespaced($locale, $group, $namespace)
    {
        if ($namespace !== 'mksine') {
            return parent::loadNamespaced($locale, $group, $namespace);
        }

        if (! isset($this->hints['mksine'])) {
            return [];
        }

        $lines = [];
        $packageFile = $this->hints['mksine'].DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';
        if ($this->files->exists($packageFile)) {
            $lines = $this->files->getRequire($packageFile);
        }
        if (! is_array($lines)) {
            $lines = [];
        }

        foreach ($this->paths as $path) {
            $projectFile = $path.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';
            if ($this->files->exists($projectFile)) {
                $override = $this->files->getRequire($projectFile);
                if (is_array($override)) {
                    $lines = array_replace_recursive($lines, $override);
                }
            }
        }

        return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
    }
}
