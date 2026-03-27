<?php

namespace Miran\Mksine\Livewire\Frontend;

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
        'post-list' => PostList::class,
        'post-show' => PostShow::class,
        'page-show' => PageShow::class,
        'author-show' => AuthorShow::class,
    ];

    /** Map route name => page key (fallback when defaults are not available). */
    protected static array $routeNameToPage = [
        'home' => 'home',
        'categories.index' => 'category-list',
        'categories.show' => 'category-show',
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

        $componentClass = $this->resolveComponent();
        $params = $this->params;

        if ($this->pageKey === 'home') {
            $frontPageId = mks_setting('front_page_id');
            if ($frontPageId !== null && $frontPageId !== '') {
                $frontPage = Page::where('id', $frontPageId)->published()->first();
                if ($frontPage) {
                    $componentClass = PageShow::class;
                    $params = ['pageId' => (int) $frontPage->id];
                }
            }
        }

        $livewireKey = $this->pageKey . '-' . implode('-', array_map('strval', array_values($params)));
        $params = array_merge($params, ['skipLayout' => true]);

        return view('mksine::livewire.frontend-resolver', [
            'componentClass' => $componentClass,
            'childParams' => $params,
            'livewireKey' => $livewireKey,
        ])->layout(theme_layout());
    }

    protected function resolvePageFromRequest(): void
    {
        $route = request()->route();
        if (! $route) {
            $this->pageKey = 'home';
            $this->params = [];

            return;
        }

        $defaults = $route->getAction('defaults') ?? [];
        if (isset($defaults['page']) && $defaults['page'] !== '') {
            $this->pageKey = (string) $defaults['page'];
        } else {
            $name = $route->getName();
            $this->pageKey = (string) (self::$routeNameToPage[$name] ?? 'home');
        }
        $this->params = $route->parameters();
    }

    protected function resolveComponent(): string
    {
        $registry = app(ThemeRegistry::class);
        $override = $registry->getOverride($this->pageKey);

        if ($override && class_exists($override)) {
            return $override;
        }

        return self::$defaultComponents[$this->pageKey] ?? Home::class;
    }
}
