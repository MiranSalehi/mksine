@php
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $style = $data['style'] ?? 'gradient';
    $alignment = $data['alignment'] ?? 'center';
    $buttonText = $data['button_text'] ?? '';
    $buttonUrl = \Miran\Mksine\Core\PageBuilder\BuilderUrl::href($data['button_url'] ?? '');
    $showSecondary = $data['show_secondary_button'] ?? false;
    $secondaryText = $data['secondary_button_text'] ?? '';
    $secondaryUrl = \Miran\Mksine\Core\PageBuilder\BuilderUrl::href($data['secondary_button_url'] ?? '');

    $containerClasses = match ($style) {
        'simple' => 'bg-slate-50 ring-1 ring-slate-900/5 dark:bg-slate-900/40 dark:ring-white/10',
        'boxed' => 'bg-white shadow-lg shadow-slate-900/10 ring-1 ring-slate-900/5 dark:bg-slate-900/60 dark:shadow-black/40 dark:ring-white/10',
        'gradient' => 'bg-zinc-900 text-white shadow-xl ring-1 ring-white/10',
        'dark' => 'bg-zinc-900 text-white shadow-xl ring-1 ring-white/10',
        default => 'bg-zinc-900 text-white shadow-xl ring-1 ring-white/10',
    };

    $textColorPrimary = in_array($style, ['gradient', 'dark']) ? 'text-white' : 'text-slate-900 dark:text-slate-50';
    $textColorSecondary = in_array($style, ['gradient', 'dark']) ? 'text-white/85' : 'text-slate-600 dark:text-slate-400';

    $buttonClasses = in_array($style, ['gradient', 'dark'])
        ? '!bg-white !text-zinc-900 shadow-md ring-1 ring-zinc-900/10 hover:!bg-zinc-50 hover:!text-zinc-900'
        : 'bg-violet-700 text-white shadow-md hover:bg-violet-600';

    $secondaryButtonClasses = in_array($style, ['gradient', 'dark'])
        ? '!border-2 !border-white/80 !bg-transparent !text-white hover:!bg-white/15'
        : 'border-2 border-slate-300 bg-transparent text-slate-800 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';
@endphp

<section {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!} class="relative mb-10 overflow-hidden rounded-2xl p-6 sm:p-8 md:p-12 {{ $containerClasses }}">
    @if ($style === 'gradient')
        <div class="pointer-events-none absolute -end-10 -top-16 h-56 w-56 rounded-full bg-violet-400/15 blur-3xl" aria-hidden="true"></div>
    @endif

    <div class="relative flex flex-col gap-8 {{ $alignment === 'between' ? 'md:flex-row md:items-center md:justify-between' : '' }} {{ $alignment === 'center' ? 'items-center text-center' : 'text-start' }}">
        <div class="{{ $alignment === 'between' ? 'md:max-w-2xl' : '' }}">
            @if ($title)
                <h2 {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('title', (bool) ($preview ?? false)) !!} class="mb-3 text-2xl font-bold tracking-tight sm:text-3xl md:text-4xl {{ $textColorPrimary }}">
                    {{ $title }}
                </h2>
            @endif

            @if ($description)
                <p {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('description', (bool) ($preview ?? false)) !!} class="max-w-2xl text-base leading-relaxed sm:text-lg {{ $textColorSecondary }}">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($buttonText || ($showSecondary && $secondaryText))
            <div class="flex flex-wrap gap-3 {{ $alignment === 'center' ? 'justify-center' : '' }}">
                @if ($buttonText && $buttonUrl)
                    <a {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('button', (bool) ($preview ?? false)) !!} href="{{ $buttonUrl }}" class="inline-flex items-center rounded-xl px-6 py-3 text-sm font-semibold no-underline transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 {{ $buttonClasses }} {{ in_array($style, ['gradient', 'dark']) ? 'focus-visible:ring-offset-transparent' : 'focus-visible:ring-violet-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900' }}">
                        {{ $buttonText }}
                    </a>
                @endif

                @if ($showSecondary && $secondaryText && $secondaryUrl)
                    <a {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('secondary', (bool) ($preview ?? false)) !!} href="{{ $secondaryUrl }}" class="inline-flex items-center rounded-xl px-6 py-3 text-sm font-semibold no-underline transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $secondaryButtonClasses }} {{ in_array($style, ['gradient', 'dark']) ? 'focus-visible:ring-white/80 focus-visible:ring-offset-transparent' : 'focus-visible:ring-violet-600 dark:focus-visible:ring-offset-slate-900' }}">
                        {{ $secondaryText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
