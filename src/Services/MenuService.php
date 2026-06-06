<?php

declare(strict_types=1);

namespace Miran\Mksine\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

/**
 * Service for retrieving menus and menu tree.
 *
 * This is the public API for frontend developers to access menus
 * without needing to know the internal structure.
 */
class MenuService
{
    /**
     * Get the menu tree for a specific location.
     *
     * @return array|null The nested menu tree, or null if no menu assigned
     */
    public function forLocation(string $key): ?array
    {
        $menu = Menu::forLocation($key);

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Get a menu tree by its slug.
     *
     * @return array|null The nested menu tree, or null if menu not found
     */
    public function getMenuTree(string $slug): ?array
    {
        $menu = Menu::where('slug', $slug)->first();

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Get a menu tree by its ID.
     *
     * @return array|null The nested menu tree, or null if menu not found
     */
    public function getMenuById(int $id): ?array
    {
        $menu = Menu::find($id);

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Build the tree structure for a menu.
     */
    public function buildTree(Menu $menu): array
    {
        $items = $menu->getTree();

        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'items' => $this->resolveTreeItems($items),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function resolveTreeItems(array $items): array
    {
        if ($items === []) {
            return [];
        }

        [$pageIds, $postIds, $categoryIds] = $this->collectReferenceIds($items);

        $pages = $pageIds !== []
            ? Page::query()->published()->whereIn('id', $pageIds)->get()->keyBy('id')
            : collect();

        $posts = $postIds !== []
            ? Post::query()->where('status', 'published')->whereIn('id', $postIds)->get()->keyBy('id')
            : collect();

        $ecomCategories = $this->loadEcomCategoriesForMenu($categoryIds);
        $cmsCategories = $categoryIds !== []
            ? Category::query()->where('is_active', true)->whereIn('id', $categoryIds)->get()->keyBy('id')
            : collect();

        return array_map(
            fn (array $node): array => $this->resolveTreeNode($node, $pages, $posts, $ecomCategories, $cmsCategories),
            $items,
        );
    }

    /**
     * @param  list<int>  $categoryIds
     * @return Collection<int, object>
     */
    private function loadEcomCategoriesForMenu(array $categoryIds): Collection
    {
        $modelClass = 'Mksine\\Ecom\\Models\\EcomProductCategory';

        if ($categoryIds === [] || ! class_exists($modelClass) || ! Route::has('ecom.category')) {
            return collect();
        }

        return $modelClass::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: list<int>, 1: list<int>, 2: list<int>}
     */
    private function collectReferenceIds(array $items): array
    {
        $pageIds = [];
        $postIds = [];
        $categoryIds = [];

        $walk = function (array $nodes) use (&$walk, &$pageIds, &$postIds, &$categoryIds): void {
            foreach ($nodes as $node) {
                $refId = (int) ($node['reference_id'] ?? 0);
                if ($refId > 0) {
                    match ($node['type'] ?? '') {
                        MenuItem::TYPE_PAGE => $pageIds[] = $refId,
                        MenuItem::TYPE_POST => $postIds[] = $refId,
                        MenuItem::TYPE_CATEGORY, MenuItem::TYPE_CUSTOM_LINK => $categoryIds[] = $refId,
                        default => null,
                    };
                }

                $children = $node['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $walk($children);
                }
            }
        };

        $walk($items);

        return [
            array_values(array_unique($pageIds)),
            array_values(array_unique($postIds)),
            array_values(array_unique($categoryIds)),
        ];
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, object>  $ecomCategories
     * @param  Collection<int, Category>  $cmsCategories
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function resolveTreeNode(array $node, Collection $pages, Collection $posts, Collection $ecomCategories, Collection $cmsCategories): array
    {
        $refId = (int) ($node['reference_id'] ?? 0);

        if ($refId > 0) {
            if (($node['type'] ?? '') === MenuItem::TYPE_PAGE) {
                $page = $pages->get($refId);
                if ($page !== null) {
                    if ($this->shouldReplacePlaceholderLabel((string) ($node['label'] ?? ''))) {
                        $node['label'] = $page->title;
                    }
                    if (Route::has('pages.show')) {
                        $node['url'] = route('pages.show', $page->slug);
                    }
                }
            } elseif (($node['type'] ?? '') === MenuItem::TYPE_POST) {
                $post = $posts->get($refId);
                if ($post !== null) {
                    if ($this->shouldReplacePlaceholderLabel((string) ($node['label'] ?? ''))) {
                        $node['label'] = $post->title;
                    }
                    if (Route::has('posts.show')) {
                        $node['url'] = route('posts.show', $post->slug);
                    }
                }
            } elseif (($node['type'] ?? '') === MenuItem::TYPE_CATEGORY) {
                $ecomCategory = $ecomCategories->get($refId);
                if ($ecomCategory !== null && $this->shouldReplacePlaceholderLabel((string) ($node['label'] ?? ''))) {
                    $node['label'] = (string) $ecomCategory->name;
                } elseif ($cmsCategories->has($refId) && $this->shouldReplacePlaceholderLabel((string) ($node['label'] ?? ''))) {
                    $node['label'] = $cmsCategories->get($refId)->name;
                }
            }
        }

        $resolvedCategoryUrl = $this->resolveEcomCategoryUrlForMenuItem($node, $ecomCategories, $cmsCategories);
        if ($resolvedCategoryUrl !== null) {
            $node['url'] = $resolvedCategoryUrl;
        }

        $children = $node['children'] ?? [];
        if (is_array($children) && $children !== []) {
            $node['children'] = array_map(
                fn (array $child): array => $this->resolveTreeNode($child, $pages, $posts, $ecomCategories, $cmsCategories),
                $children,
            );
        }

        return $node;
    }

    /**
     * Prefer ecom storefront URLs (slug path) over stale CMS permalink snapshots in menu_items.url.
     *
     * @param  Collection<int, object>  $ecomCategories
     * @param  Collection<int, Category>  $cmsCategories
     */
    private function resolveCategoryMenuUrl(int $referenceId, Collection $ecomCategories, Collection $cmsCategories): ?string
    {
        $ecomCategory = $ecomCategories->get($referenceId);
        $urlResolverClass = 'Mksine\\Ecom\\Support\\StorefrontCategoryUrl';

        if ($ecomCategory !== null && class_exists($urlResolverClass) && method_exists($urlResolverClass, 'forCategory')) {
            /** @var string|null $url */
            $url = $urlResolverClass::forCategory($ecomCategory);

            if ($url !== null) {
                return $url;
            }
        }

        if (! Route::has('ecom.category')) {
            $cmsCategory = $cmsCategories->get($referenceId);
            if ($cmsCategory instanceof Category) {
                return $cmsCategory->getUrl();
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, object>  $ecomCategories
     * @param  Collection<int, Category>  $cmsCategories
     * @param  array<string, mixed>  $node
     */
    private function resolveEcomCategoryUrlForMenuItem(array $node, Collection $ecomCategories, Collection $cmsCategories): ?string
    {
        $type = (string) ($node['type'] ?? '');
        $refId = (int) ($node['reference_id'] ?? 0);

        if ($type === MenuItem::TYPE_CATEGORY && $refId > 0) {
            return $this->resolveCategoryMenuUrl($refId, $ecomCategories, $cmsCategories);
        }

        if ($type !== MenuItem::TYPE_CUSTOM_LINK) {
            return null;
        }

        if ($refId > 0 && $ecomCategories->has($refId) && ! $this->menuReferenceIsProduct($refId)) {
            $fromReference = $this->resolveCategoryMenuUrl($refId, $ecomCategories, collect());
            if ($fromReference !== null) {
                return $fromReference;
            }
        }

        $legacyResolver = 'Mksine\\Ecom\\Support\\StorefrontCategoryUrl';
        if (class_exists($legacyResolver) && method_exists($legacyResolver, 'fromLegacyMenuUrl')) {
            /** @var string|null $fromLegacy */
            $fromLegacy = $legacyResolver::fromLegacyMenuUrl(isset($node['url']) ? (string) $node['url'] : null);

            return $fromLegacy;
        }

        return null;
    }

    private function menuReferenceIsProduct(int $referenceId): bool
    {
        $productClass = 'Mksine\\Ecom\\Models\\Product';

        return class_exists($productClass)
            && $productClass::query()->whereKey($referenceId)->exists();
    }

    /**
     * Replace empty labels or Woo/import placeholders with the referenced entity title.
     */
    private function shouldReplacePlaceholderLabel(string $label): bool
    {
        $label = trim($label);

        if ($label === '') {
            return true;
        }

        $placeholders = [
            'صفحه',
            'Page',
            (string) __('mksine::pages.model_label'),
            'مطلب',
            'نوشته',
            'Post',
            (string) __('mksine::posts.model_label'),
            'دسته‌بندی',
            'دسته',
            'Category',
            (string) __('mksine::categories.plural_model_label'),
        ];

        return in_array($label, $placeholders, true);
    }

    /**
     * Get all menus.
     *
     * @return array<Menu>
     */
    public function getAllMenus(): array
    {
        return Menu::orderBy('name')->get()->toArray();
    }

    /**
     * Get all locations with their assigned menus.
     */
    public function getLocationsWithMenus(): array
    {
        return MenuLocation::with('menu')
            ->orderBy('label')
            ->get()
            ->map(fn (MenuLocation $location) => [
                'id' => $location->id,
                'key' => $location->key,
                'label' => $location->label,
                'menu' => $location->menu?->only(['id', 'name', 'slug']),
            ])
            ->toArray();
    }
}
