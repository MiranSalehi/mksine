@php
    use Miran\Mksine\Core\Theme\ThemeDependencyChecker;
    use Miran\Mksine\Core\Theme\ThemeManager;

    $checker = app(ThemeDependencyChecker::class);
    $activeTheme = app(ThemeManager::class)->getActive();
    $missingPlugins = $checker->missingPlugins($activeTheme);
@endphp

@if($activeTheme && $missingPlugins !== [])
    <div class="mksine-theme-dependency-banner border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-2 gap-y-1">
            <span class="font-semibold">{{ __('mksine::themes.admin_dependency_banner_title') }}</span>
            <span>
                {{ __('mksine::themes.admin_dependency_banner_body', [
                    'theme' => $activeTheme->name,
                    'plugins' => implode(', ', $checker->missingPluginLabels($activeTheme)),
                ]) }}
            </span>
            <a href="{{ url('/admin/themes') }}" class="font-semibold underline underline-offset-2">
                {{ __('mksine::themes.admin_dependency_banner_link') }}
            </a>
        </div>
    </div>
@endif
