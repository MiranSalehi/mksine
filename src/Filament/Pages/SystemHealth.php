<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Core\Install\Health\HealthCheck;
use Miran\Mksine\Core\Install\Health\SystemHealthReport;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;

/**
 * Super-admin overview of whether the site is healthy after installation:
 * cron, queue, server requirements, environment and risky settings.
 */
class SystemHealth extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedHeart;

    protected string $view = 'mksine::filament.pages.system-health';

    protected static ?string $slug = 'system-health';

    protected static ?int $navigationSort = 9998;

    public const BADGE_CACHE_KEY = 'mksine:system-health:problem-count';

    /** Captured on the first request; Livewire refreshes do not carry the web server's DOCUMENT_ROOT. */
    public ?string $documentRoot = null;

    public function mount(): void
    {
        $root = request()->server('DOCUMENT_ROOT');
        $this->documentRoot = is_string($root) && $root !== '' ? $root : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('mksine::system_health.navigation_label');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return AdminNavigationGroup::System;
    }

    public static function getNavigationBadge(): ?string
    {
        // Rendered on every admin page, so the count is cached briefly.
        $problems = (int) Cache::remember(self::BADGE_CACHE_KEY, 60, fn (): int => SystemHealthReport::problemCount(app(SystemHealthReport::class)->build()));

        return $problems > 0 ? (string) $problems : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return SuperAdminGate::check();
    }

    public function getTitle(): string
    {
        return __('mksine::system_health.title');
    }

    public function getSubheading(): ?string
    {
        return __('mksine::system_health.subheading');
    }

    /**
     * @return array<string, list<HealthCheck>>
     */
    public function getSections(): array
    {
        return app(SystemHealthReport::class)->build($this->documentRoot);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('mksine::system_health.refresh'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn () => Cache::forget(self::BADGE_CACHE_KEY)),
        ];
    }
}
