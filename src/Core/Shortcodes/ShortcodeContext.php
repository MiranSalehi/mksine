<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

final readonly class ShortcodeContext
{
    public function __construct(
        public ?Page $page = null,
        public ?Post $post = null,
        public ?Category $category = null,
    ) {}

    public static function make(
        ?Page $page = null,
        ?Post $post = null,
        ?Category $category = null,
    ): self {
        return new self(page: $page, post: $post, category: $category);
    }
}
