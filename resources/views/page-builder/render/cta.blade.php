@php
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $style = $data['style'] ?? 'gradient';
    $alignment = $data['alignment'] ?? 'center';
    $buttonText = $data['button_text'] ?? '';
    $buttonUrl = $data['button_url'] ?? '';
    $showSecondary = $data['show_secondary_button'] ?? false;
    $secondaryText = $data['secondary_button_text'] ?? '';
    $secondaryUrl = $data['secondary_button_url'] ?? '';

    $containerClasses = match ($style) {
        'simple' => 'bg-zinc-50 ring-1 ring-zinc-900/5 dark:bg-zinc-900/40 dark:ring-white/10',
        'boxed' => 'bg-white shadow-lg shadow-zinc-900/10 ring-1 ring-zinc-900/5 dark:bg-zinc-900/60 dark:shadow-black/40 dark:ring-white/10',
        'gradient' => 'bg-gradient-to-br from-amber-500 via-orange-500 to-rose-600 text-white shadow-xl shadow-amber-900/25 dark:shadow-black/40',
        'dark' => 'bg-zinc-900 text-white shadow-xl ring-1 ring-white/10 dark:bg-zinc-950',
        default => 'bg-gradient-to-br from-amber-500 via-orange-500 to-rose-600 text-white shadow-xl shadow-amber-900/25',
    };

    $textColorPrimary = in_array($style, ['gradient', 'dark']) ? 'text-white' : 'text-zinc-900 dark:text-zinc-50';
    $textColorSecondary = in_array($style, ['gradient', 'dark']) ? 'text-white/85' : 'text-zinc-600 dark:text-zinc-400';

    $buttonClasses = in_array($style, ['gradient', 'dark'])
        ? '!bg-white !text-zinc-950 shadow-md ring-1 ring-zinc-950/10 hover:!bg-zinc-50 hover:!text-zinc-950'
        : 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md hover:from-amber-600 hover:to-orange-700';

    $secondaryButtonClasses = in_array($style, ['gradient', 'dark'])
        ? '!border-2 !border-white/80 !bg-transparent !text-white hover:!bg-white/15'
        : 'border-2 border-zinc-300 bg-transparent text-zinc-800 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800';
@endphp

<section class="relative mb-10 overflow-hidden rounded-2xl p-6 sm:p-8 md:p-12 {{ $containerClasses }}">
    @if ($style === 'gradient')
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_100%_-20%,rgba(255,255,255,0.22),transparent)]" aria-hidden="true"></div>
    @endif

    <div class="relative flex flex-col gap-8 {{ $alignment === 'between' ? 'md:flex-row md:items-center md:justify-between' : '' }} {{ $alignment === 'center' ? 'items-center text-center' : 'text-start' }}">
        <div class="{{ $alignment === 'between' ? 'md:max-w-2xl' : '' }}">
            @if ($title)
                <h2 class="mb-3 text-2xl font-bold tracking-tight sm:text-3xl md:text-4xl {{ $textColorPrimary }}">
                    {{ $title }}
                </h2>
            @endif

            @if ($description)
                <p class="max-w-2xl text-base leading-relaxed sm:text-lg {{ $textColorSecondary }}">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($buttonText || ($showSecondary && $secondaryText))
            <div class="flex flex-wrap gap-3 {{ $alignment === 'center' ? 'justify-center' : '' }}">
                @if ($buttonText && $buttonUrl)
                    <a href="{{ $buttonUrl }}" class="inline-flex items-center rounded-xl px-6 py-3 text-sm font-semibold no-underline transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 {{ $buttonClasses }} {{ in_array($style, ['gradient', 'dark']) ? 'focus-visible:ring-offset-transparent' : 'focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900' }}">
                        {{ $buttonText }}
                    </a>
                @endif

                @if ($showSecondary && $secondaryText && $secondaryUrl)
                    <a href="{{ $secondaryUrl }}" class="inline-flex items-center rounded-xl px-6 py-3 text-sm font-semibold no-underline transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $secondaryButtonClasses }} {{ in_array($style, ['gradient', 'dark']) ? 'focus-visible:ring-white/80 focus-visible:ring-offset-transparent' : 'focus-visible:ring-amber-500 dark:focus-visible:ring-offset-zinc-900' }}">
                        {{ $secondaryText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
