<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Miran\Mksine\Core\Hooks\Hooks;

final class FrontendAdminBar
{
    public const HOOK_ITEMS = 'frontend_admin_bar.items';

    public function __construct(
        private readonly FrontendAdminBarContextResolver $contextResolver,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('mksine.features.frontend_admin_bar', true);
    }

    public function shouldShow(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $user = auth()->user();
        if (! $user instanceof Authenticatable) {
            return false;
        }

        $panel = $this->adminPanel();
        if ($panel === null) {
            return false;
        }

        if ($this->isAdminRequest($panel)) {
            return false;
        }

        if (! $user instanceof FilamentUser) {
            return false;
        }

        return $user->canAccessPanel($panel);
    }

    /**
     * @return list<FrontendAdminBarItem>
     */
    public function items(): array
    {
        $panel = $this->adminPanel();
        if ($panel === null) {
            return [];
        }

        $context = $this->contextResolver->resolve();

        /** @var list<FrontendAdminBarItem> $items */
        $items = Hooks::filter(self::HOOK_ITEMS, [], $context, $panel);

        return $this->normalizeItems($items);
    }

    public function render(): string
    {
        if (! $this->shouldShow()) {
            return '';
        }

        $user = auth()->user();
        $items = $this->items();

        if ($items === []) {
            return '';
        }

        return view('mksine::partials.frontend-admin-bar', [
            'items' => $items,
            'userName' => $user?->name ?? $user?->email ?? '',
        ])->render();
    }

    /**
     * @param  list<FrontendAdminBarItem>  $items
     * @return list<FrontendAdminBarItem>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! $item instanceof FrontendAdminBarItem) {
                continue;
            }

            if ($item->hasChildren()) {
                $children = $this->normalizeItems($item->children);

                if ($children === []) {
                    continue;
                }

                $normalized[] = $item->withChildren($children);

                continue;
            }

            if (! $item->isLink()) {
                continue;
            }

            $normalized[] = $item;
        }

        usort(
            $normalized,
            static fn (FrontendAdminBarItem $a, FrontendAdminBarItem $b): int => $a->priority <=> $b->priority,
        );

        return $normalized;
    }

    private function adminPanel(): ?Panel
    {
        if (Filament::getCurrentPanel() !== null) {
            return Filament::getCurrentPanel();
        }

        if (Filament::getDefaultPanel() !== null) {
            return Filament::getDefaultPanel();
        }

        return Filament::getPanel('admin');
    }

    private function isAdminRequest(Panel $panel): bool
    {
        $path = trim($panel->getPath(), '/');
        if ($path === '') {
            return false;
        }

        return request()->is($path) || request()->is($path.'/*');
    }
}
