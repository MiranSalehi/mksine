<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

use Illuminate\Http\Request;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

final class FrontendAdminBarContextResolver
{
    public function resolve(?Request $request = null): FrontendAdminBarContext
    {
        $request ??= request();
        $route = $request->route();
        $routeName = is_string($route?->getName()) ? $route->getName() : '';

        return match ($routeName) {
            'pages.show' => $this->resolvePageContext($routeName, $route?->parameter('slug')),
            'posts.show' => $this->resolvePostContext($routeName, $route?->parameter('slug')),
            'categories.show' => $this->resolveCategoryContext($routeName, $route?->parameter('path')),
            'home' => $this->resolveHomeContext($routeName),
            default => new FrontendAdminBarContext(routeName: $routeName),
        };
    }

    private function resolvePageContext(string $routeName, mixed $slug): FrontendAdminBarContext
    {
        if (! is_string($slug) || $slug === '') {
            return new FrontendAdminBarContext(routeName: $routeName);
        }

        $page = Page::query()->where('slug', $slug)->first();

        return new FrontendAdminBarContext(routeName: $routeName, page: $page);
    }

    private function resolvePostContext(string $routeName, mixed $slug): FrontendAdminBarContext
    {
        if (! is_string($slug) || $slug === '') {
            return new FrontendAdminBarContext(routeName: $routeName);
        }

        $post = Post::query()->where('slug', $slug)->first();

        return new FrontendAdminBarContext(routeName: $routeName, post: $post);
    }

    private function resolveCategoryContext(string $routeName, mixed $path): FrontendAdminBarContext
    {
        $path = is_string($path) ? trim($path, '/') : '';
        if ($path === '') {
            return new FrontendAdminBarContext(routeName: $routeName);
        }

        $category = Category::findByFullPath($path);

        return new FrontendAdminBarContext(routeName: $routeName, category: $category);
    }

    private function resolveHomeContext(string $routeName): FrontendAdminBarContext
    {
        $frontPageId = mks_setting('front_page_id');
        if ($frontPageId === null || $frontPageId === '') {
            return new FrontendAdminBarContext(routeName: $routeName);
        }

        $page = Page::query()->find((int) $frontPageId);

        return new FrontendAdminBarContext(routeName: $routeName, page: $page);
    }
}
