<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Support;

final class ThemeZipIdentity
{
    /**
     * @param  array<string, mixed>  $themeJson
     */
    public static function matches(string $extractedRoot, string $stagingDir, array $themeJson, string $targetIdentifier): bool
    {
        foreach (self::candidates($extractedRoot, $stagingDir, $themeJson) as $candidate) {
            if ($candidate === $targetIdentifier) {
                return true;
            }
        }

        $folder = basename($extractedRoot);
        if ($folder !== basename($stagingDir) && self::folderMatchesTarget($folder, $targetIdentifier)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $themeJson
     * @return list<string>
     */
    public static function candidates(string $extractedRoot, string $stagingDir, array $themeJson): array
    {
        $candidates = [];

        if (isset($themeJson['identifier']) && is_string($themeJson['identifier']) && $themeJson['identifier'] !== '') {
            $candidates[] = $themeJson['identifier'];
        }

        if (isset($themeJson['name']) && is_string($themeJson['name']) && $themeJson['name'] !== '') {
            $candidates[] = self::slug((string) $themeJson['name']);
        }

        $folder = basename($extractedRoot);
        if ($folder !== basename($stagingDir)) {
            $candidates[] = $folder;
        }

        return array_values(array_unique($candidates));
    }

    public static function slug(string $name): string
    {
        return strtolower(str_replace([' ', '_'], '-', $name));
    }

    public static function folderMatchesTarget(string $folder, string $targetIdentifier): bool
    {
        if ($folder === $targetIdentifier) {
            return true;
        }

        return preg_match('/^'.preg_quote($targetIdentifier, '/').'[-_].+/', $folder) === 1;
    }
}
