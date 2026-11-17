<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\PageCache\PageCache;

class PageCacheClearCommand extends Command
{
    protected $signature = 'mksine:page-cache-clear';

    protected $description = 'Invalidate every page stored by the guest page cache';

    public function handle(PageCache $pageCache): int
    {
        $pageCache->flush();

        $this->components->info('Page cache cleared.');

        return self::SUCCESS;
    }
}
