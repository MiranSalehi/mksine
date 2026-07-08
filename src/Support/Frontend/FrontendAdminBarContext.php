<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

final readonly class FrontendAdminBarContext
{
    public function __construct(
        public string $routeName,
        public ?Page $page = null,
        public ?Post $post = null,
        public ?Category $category = null,
    ) {}
}
