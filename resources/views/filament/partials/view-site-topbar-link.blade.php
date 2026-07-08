<div class="fi-topbar-view-site hidden sm:flex items-center me-2 shrink-0">
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white"
        title="{{ $label }}"
    >
        <x-filament::icon
            icon="heroicon-m-arrow-top-right-on-square"
            class="h-4 w-4 shrink-0 opacity-70"
        />
        <span class="max-w-[12rem] truncate">{{ $label }}</span>
    </a>
</div>
