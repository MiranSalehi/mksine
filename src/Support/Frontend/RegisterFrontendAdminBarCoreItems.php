<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Gate;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Filament\Resources\Posts\PostResource;

final class RegisterFrontendAdminBarCoreItems
{
    public function register(): void
    {
        Hooks::addFilter(FrontendAdminBar::HOOK_ITEMS, $this->registerItems(...), priority: 10);
    }

    /**
     * @param  list<FrontendAdminBarItem>  $items
     * @return list<FrontendAdminBarItem>
     */
    private function registerItems(array $items, FrontendAdminBarContext $context, Panel $panel): array
    {
        $items[] = new FrontendAdminBarItem(
            id: 'mksine.dashboard',
            label: __('mksine::frontend_admin_bar.dashboard'),
            url: $panel->getUrl(),
            priority: 10,
        );

        if ($context->page !== null && $this->canManage('update', $context->page)) {
            $items[] = new FrontendAdminBarItem(
                id: 'mksine.edit_page',
                label: __('mksine::frontend_admin_bar.edit_page', ['title' => $context->page->title]),
                url: PageResource::getUrl('edit', ['record' => $context->page], panel: $panel->getId()),
                priority: 20,
            );
        }

        if ($context->post !== null && $this->canManage('update', $context->post)) {
            $items[] = new FrontendAdminBarItem(
                id: 'mksine.edit_post',
                label: __('mksine::frontend_admin_bar.edit_post', ['title' => $context->post->title]),
                url: PostResource::getUrl('edit', ['record' => $context->post], panel: $panel->getId()),
                priority: 20,
            );
        }

        if ($context->category !== null && $this->canManage('update', $context->category)) {
            $items[] = new FrontendAdminBarItem(
                id: 'mksine.edit_category',
                label: __('mksine::frontend_admin_bar.edit_category', ['name' => $context->category->name]),
                url: CategoryResource::getUrl('edit', ['record' => $context->category], panel: $panel->getId()),
                priority: 20,
            );
        }

        if ($context->routeName === 'posts.index' && $this->canManage('viewAny', PostResource::getModel())) {
            $items[] = new FrontendAdminBarItem(
                id: 'mksine.manage_posts',
                label: __('mksine::frontend_admin_bar.manage_posts'),
                url: PostResource::getUrl(panel: $panel->getId()),
                priority: 30,
            );
        }

        if ($context->routeName === 'categories.index' && $this->canManage('viewAny', CategoryResource::getModel())) {
            $items[] = new FrontendAdminBarItem(
                id: 'mksine.manage_categories',
                label: __('mksine::frontend_admin_bar.manage_categories'),
                url: CategoryResource::getUrl(panel: $panel->getId()),
                priority: 30,
            );
        }

        if ($context->routeName === 'pages.show' || ($context->routeName === 'home' && $context->page === null)) {
            if ($this->canManage('viewAny', PageResource::getModel())) {
                $items[] = new FrontendAdminBarItem(
                    id: 'mksine.manage_pages',
                    label: __('mksine::frontend_admin_bar.manage_pages'),
                    url: PageResource::getUrl(panel: $panel->getId()),
                    priority: 30,
                );
            }
        }

        return $items;
    }

    private function canManage(string $ability, object|string $target): bool
    {
        if (SuperAdminGate::check()) {
            return true;
        }

        return Gate::check($ability, $target);
    }
}
