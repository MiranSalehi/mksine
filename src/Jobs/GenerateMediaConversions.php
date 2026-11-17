<?php

declare(strict_types=1);

namespace Miran\Mksine\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Miran\Mksine\Core\Media\ImageConversions;
use Miran\Mksine\Models\Media;

/**
 * Builds the responsive WebP copies of one media item ({@see ImageConversions}).
 */
class GenerateMediaConversions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly int $mediaId) {}

    /**
     * Run after the response by default, so uploads stay fast on hosts without a queue worker;
     * `mksine.media.conversions_dispatch` = `queue` sends it to the queue, `sync` runs it inline.
     */
    public static function dispatchFor(Media $media): void
    {
        $job = new self((int) $media->getKey());

        match ((string) config('mksine.media.conversions_dispatch', 'after_response')) {
            'queue' => dispatch($job),
            'sync' => dispatch_sync($job),
            default => app()->runningInConsole() ? dispatch_sync($job) : dispatch($job)->afterResponse(),
        };
    }

    public function handle(ImageConversions $conversions): void
    {
        $media = Media::query()->find($this->mediaId);

        if ($media !== null) {
            $conversions->generate($media);
        }
    }
}
