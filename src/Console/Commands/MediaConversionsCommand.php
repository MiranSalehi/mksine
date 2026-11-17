<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Media\ImageConversions;
use Miran\Mksine\Models\Media;

/**
 * Builds responsive WebP copies for images uploaded before conversions existed.
 */
class MediaConversionsCommand extends Command
{
    protected $signature = 'mksine:media-conversions
                            {--force : Rebuild copies that already exist}
                            {--id=* : Only these media ids}';

    protected $description = 'Generate responsive WebP copies for existing images';

    public function handle(ImageConversions $conversions): int
    {
        if (! $conversions->isEnabled()) {
            $this->components->error('Image conversions are off (mksine.media.optimize_images) or GD lacks WebP support.');

            return self::FAILURE;
        }

        $query = Media::query()
            ->whereIn('mime_type', ImageConversions::CONVERTIBLE_MIME_TYPES)
            ->when($this->option('id') !== [], fn ($query) => $query->whereKey(array_map('intval', (array) $this->option('id'))))
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('conversions'));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->components->info('Every image already has its copies.');

            return self::SUCCESS;
        }

        $made = 0;
        $skipped = 0;

        $this->withProgressBar($query->lazyById(50), function (Media $media) use ($conversions, &$made, &$skipped): void {
            if ($this->option('force')) {
                $conversions->delete($media);
            }

            $conversions->generate($media) === [] ? $skipped++ : $made++;
        });

        $this->newLine(2);
        $this->components->info("{$made} image(s) converted, {$skipped} skipped (too large for memory_limit, missing or unreadable).");

        return self::SUCCESS;
    }
}
