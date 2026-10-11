@php
    $heading = $data['heading'] ?? '';
    $subheading = $data['subheading'] ?? '';
    $columns = $data['columns'] ?? 3;
    $style = $data['style'] ?? 'simple';
    $iconStyle = $data['icon_style'] ?? 'circle';
    $features = $data['features'] ?? [];

    $gridCols = match ($columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $cardClasses = match ($style) {
        'bordered' => 'rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40',
        'shadowed' => 'rounded-2xl bg-white p-6 shadow-md shadow-slate-900/5 ring-1 ring-slate-900/5 dark:bg-slate-900/50 dark:shadow-black/40 dark:ring-white/10',
        'filled' => 'rounded-2xl bg-slate-50 p-6 dark:bg-slate-900/50',
        default => 'py-2',
    };

    $iconBgClasses = match ($iconStyle) {
        'circle' => 'mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:ring-violet-500/20',
        'square' => 'mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:ring-violet-500/20',
        default => '',
    };
@endphp

<section {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!} class="mb-10 md:mb-14">
    @if ($heading || $subheading)
        <div class="mb-10 text-center sm:mb-12">
            @if ($heading)
                <h2 {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('heading', (bool) ($preview ?? false)) !!} class="mb-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-slate-50">
                    {{ $heading }}
                </h2>
            @endif

            @if ($subheading)
                <p {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('subheading', (bool) ($preview ?? false)) !!} class="mx-auto max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ $subheading }}
                </p>
            @endif
        </div>
    @endif

    @if (count($features) > 0)
        <div class="grid grid-cols-1 gap-6 sm:gap-8 {{ $gridCols }}">
            @foreach ($features as $index => $feature)
                @php
                    $icon = $feature['icon'] ?? 'heroicon-o-star';
                    $title = $feature['title'] ?? '';
                    $description = $feature['description'] ?? '';
                    $link = \Miran\Mksine\Core\PageBuilder\BuilderUrl::href($feature['link'] ?? '');
                @endphp

                <div {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('features', (bool) ($preview ?? false), $index) !!} class="{{ $cardClasses }} transition-colors duration-200 hover:border-violet-200 dark:hover:border-violet-500/30">
                    @if ($icon && $iconStyle !== 'none')
                        <div class="{{ $iconBgClasses }}">
                            <x-dynamic-component :component="$icon" class="h-6 w-6 text-violet-700 dark:text-violet-300" />
                        </div>
                    @endif

                    @if ($title)
                        @if ($link)
                            <a href="{{ $link }}" class="group/block">
                                <h3 class="mb-2 text-lg font-semibold tracking-tight text-slate-900 transition-colors group-hover/block:text-violet-700 dark:text-slate-50 dark:group-hover/block:text-violet-300">
                                    {{ $title }}
                                </h3>
                            </a>
                        @else
                            <h3 class="mb-2 text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                                {{ $title }}
                            </h3>
                        @endif
                    @endif

                    @if ($description)
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
