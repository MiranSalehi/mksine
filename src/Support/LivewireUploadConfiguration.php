<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Align Livewire temporary upload limits with MKSine ZIP uploaders (plugins, themes, core).
 *
 * Livewire validates uploads globally before Filament FileUpload::maxSize(). The framework
 * default is 12 MB, which makes larger plugin ZIPs fail with a generic
 * "mountedActions.*.plugin_file.* failed to upload" message in the admin UI.
 */
final class LivewireUploadConfiguration
{
    public const DEFAULT_MAX_UPLOAD_TIME_MINUTES = 30;

    /**
     * Minimum KB required for MKSine ZIP uploads (plugin install + updater UI).
     */
    public static function requiredMaxUploadKb(): int
    {
        return max(UploadLimits::maxSizeKb(), UploadLimits::updaterMaxZipKb());
    }

    /**
     * @return list<string>
     */
    public static function zipAcceptedMimeTypes(): array
    {
        return [
            'application/zip',
            'application/x-zip-compressed',
            'application/octet-stream', // Windows often reports ZIP files this way.
        ];
    }

    public static function apply(): void
    {
        self::applyMaxUploadRules();
        self::applyPreviewMimes();
        self::applyMaxUploadTime();
        self::ensureTemporaryDirectoryExists();
    }

    private static function applyMaxUploadRules(): void
    {
        $requiredMaxKb = self::requiredMaxUploadKb();
        $currentMaxKb = self::extractMaxKbFromRules(config('livewire.temporary_file_upload.rules'));

        if ($currentMaxKb === null || $currentMaxKb < $requiredMaxKb) {
            config(['livewire.temporary_file_upload.rules' => ['file', 'max:'.$requiredMaxKb]]);
        }
    }

    private static function applyPreviewMimes(): void
    {
        $previewMimes = config('livewire.temporary_file_upload.preview_mimes', []);

        if (! is_array($previewMimes)) {
            return;
        }

        $zipMimes = ['zip', 'x-zip-compressed'];

        if (array_diff($zipMimes, $previewMimes) === []) {
            return;
        }

        config([
            'livewire.temporary_file_upload.preview_mimes' => array_values(array_unique([
                ...$previewMimes,
                ...$zipMimes,
            ])),
        ]);
    }

    private static function applyMaxUploadTime(): void
    {
        $current = (int) config('livewire.temporary_file_upload.max_upload_time', 5);

        if ($current >= self::DEFAULT_MAX_UPLOAD_TIME_MINUTES) {
            return;
        }

        config(['livewire.temporary_file_upload.max_upload_time' => self::DEFAULT_MAX_UPLOAD_TIME_MINUTES]);
    }

    private static function ensureTemporaryDirectoryExists(): void
    {
        $diskName = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');

        if (! is_string($diskName) || $diskName === '') {
            return;
        }

        $directory = config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp';

        if (! is_string($directory) || $directory === '') {
            return;
        }

        try {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }
        } catch (\Throwable) {
            // Host disk misconfiguration should not break package boot.
        }
    }

    /**
     * @return int|null Kilobytes from a max: rule, or null when no max rule is present.
     */
    public static function extractMaxKbFromRules(mixed $rules): ?int
    {
        if ($rules === null) {
            return 12288;
        }

        $list = is_array($rules) ? $rules : explode('|', (string) $rules);

        foreach ($list as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'max:')) {
                return (int) substr($rule, 4);
            }
        }

        return null;
    }
}
