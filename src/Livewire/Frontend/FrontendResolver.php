<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Routing\Route;
use Livewire\Component;
use Miran\Mksine\Core\Theme\ThemeRegistry;
use Miran\Mksine\Models\Page;

/**
 * Resolves the actual frontend component (theme override or default) and renders it.
 * Used by routes so themes can override pages without changing route definitions.
 */
class FrontendResolver extends Component
{
    /** Page key: home, post-show, category-show, etc. (named pageKey to avoid passing to child PageShow::$page). */
    public string $pageKey = 'home';

    /** Route parameters to pass to the resolved component (e.g. slug, path, id). */
    public array $params = [];

    protected static array $defaultComponents = [
        'home' => Home::class,
        'category-list' => CategoryList::class,
        'category-show' => CategoryShow::class,
        'tag-list' => TagList::class,
        'tag-show' => TagShow::class,
        'post-list' => PostList::class,
        'post-show' => PostShow::class,
        'page-show' => PageShow::class,
        'author-show' => AuthorShow::class,
        'entry-list' => EntryList::class,
        'entry-show' => EntryShow::class,
    ];

    /** Map route name => page key (fallback when defaults are not available). */
    protected static array $routeNameToPage = [
        'home' => 'home',
        'categories.index' => 'category-list',
        'categories.show' => 'category-show',
        'tags.index' => 'tag-list',
        'tags.show' => 'tag-show',
        'posts.index' => 'post-list',
        'posts.show' => 'post-show',
        'pages.show' => 'page-show',
        'authors.show' => 'author-show',
    ];

    public function mount(): void
    {
        $this->resolvePageFromRequest();
    }

    public function render()
    {
        $this->resolvePageFromRequest();

        [$componentClass, $params] = self::target($this->pageKey, $this->params);

        $livewireKey = $this->pageKey . '-' . implode('-', array_map('strval', array_values($params)));
        $params = array_merge($params, ['skipLayout' => true]);

        return view('mksine::livewire.frontend-resolver', [
            'componentClass' => $componentClass,
            'childParams' => $params,
            'livewireKey' => $livewireKey,
        ])->layout(theme_layout());
    }

    /**
     * Page key and pass-through parameters of a storefront route.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function pageFromRoute(?Route $route): array
    {
        if (! $route) {
            return ['home', []];
        }

        $defaults = $route->defaults ?? [];
        $pageKey = isset($defaults['page']) && $defaults['page'] !== ''
            ? (string) $defaults['page']
            : (string) (self::$routeNameToPage[$route->getName()] ?? 'home');

        $passThrough = $defaults;
        unset($passThrough['page']);
        $merged = array_merge($passThrough, $route->parameters());

        return [$pageKey, array_intersect_key($merged, array_flip(['slug', 'path', 'id', 'contentType', 'pageId']))];
    }

    /**
     * The theme's Livewire override for a page key, if it registered one.
     */
    public static function themeOverride(string $pageKey): ?string
    {
        $override = app(ThemeRegistry::class)->getOverride($pageKey);

        return $override && class_exists($override) ? $override : null;
    }

    /**
     * Component class and parameters that render a page key (theme override, core default,
     * and the static front page chosen in settings for "home").
     *
     * @param  array<string, mixed>  $params
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    public static function target(string $pageKey, array $params): array
    {
        $componentClass = self::themeOverride($pageKey) ?? (self::$defaultComponents[$pageKey] ?? Home::class);

        if ($pageKey === 'home') {
            $frontPageId = mks_setting('front_page_id');
            if ($frontPageId !== null && $frontPageId !== '') {
                $frontPage = Page::where('id', $frontPageId)->published()->first();
                if ($frontPage) {
                    return [PageShow::class, ['pageId' => (int) $frontPage->id]];
                }
            }
        }

        return [$componentClass, $params];
    }

    protected function resolvePageFromRequest(): void
    {
        [$this->pageKey, $this->params] = self::pageFromRoute(request()->route());
    }

    protected function resolveComponent(): string
    {
        return self::target($this->pageKey, $this->params)[0];
    }
}
