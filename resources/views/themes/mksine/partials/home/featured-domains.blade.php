{{-- Featured TLD grid: violet palette (matches services-trio); logos in theme images/home-featured-tld-*.png --}}
@php
    $featuredTlds = [
        [
            'tld' => '.es',
            'slug' => 'es',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_es'),
            'price' => '€6.95',
            'original' => null,
            'discount' => null,
        ],
        [
            'tld' => '.com',
            'slug' => 'com',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_com'),
            'price' => '€15.95',
            'original' => null,
            'discount' => null,
        ],
        [
            'tld' => '.net',
            'slug' => 'net',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_net'),
            'price' => '€4.20',
            'original' => '€14.95',
            'discount' => 71,
        ],
        [
            'tld' => '.online',
            'slug' => 'online',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_online'),
            'price' => '€2.95',
            'original' => '€30.95',
            'discount' => 90,
        ],
        [
            'tld' => '.site',
            'slug' => 'site',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_site'),
            'price' => '€37.45',
            'original' => null,
            'discount' => null,
        ],
        [
            'tld' => '.org',
            'slug' => 'org',
            'href' => '#',
            'category' => __('mksine::frontend.home_featured_cat_org'),
            'price' => '€8.45',
            'original' => '€12.95',
            'discount' => 34,
        ],
    ];
@endphp
<section
    class="home-featured-domains border-t border-violet-200/80 bg-violet-50 py-10 md:py-14 lg:py-16 xl:py-20 dark:border-violet-900/50 dark:bg-violet-950/40"
    aria-labelledby="home-featured-domains-heading"
>
    <div class="mx-auto w-[90%] max-w-[87.5rem]">
        <div class="mb-6 flex flex-col items-center justify-center gap-4 md:flex-row md:items-baseline md:justify-between">
            <h2 id="home-featured-domains-heading" class="text-center text-xl font-bold tracking-tight text-gray-900 md:text-left md:text-2xl dark:text-gray-50">
                {{ __('mksine::frontend.home_featured_domains_title') }}
            </h2>
            <a
                href="#"
                class="hidden font-semibold text-violet-600 transition hover:text-violet-800 md:inline-flex md:items-center dark:text-violet-400 dark:hover:text-violet-300"
            >
                {{ __('mksine::frontend.home_featured_domains_view_all') }}
                <span class="ms-1" aria-hidden="true">→</span>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 md:gap-5 lg:grid-cols-6 lg:gap-6">
            @foreach ($featuredTlds as $row)
                <a
                    href="{{ $row['href'] }}"
                    title="{{ $row['tld'] }}"
                    class="home-featured-domains__card group relative flex flex-col items-center overflow-hidden rounded-xl border border-violet-100 bg-white p-4 text-center shadow-md shadow-violet-900/10 transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-900/15 dark:border-violet-900/60 dark:bg-slate-900 dark:shadow-black/25 dark:hover:border-violet-600"
                >
                    @if (! empty($row['discount']))
                        <span
                            class="absolute end-2 top-2 rounded px-1.5 py-0.5 text-[0.65rem] font-bold leading-none text-white bg-emerald-700 dark:bg-emerald-600"
                            title="{{ __('mksine::frontend.home_featured_domains_discount_title') }}"
                        >
                            -{{ $row['discount'] }}%
                        </span>
                    @endif
                    <img
                        src="{{ theme_asset('images/home-featured-tld-'.$row['slug'].'.png') }}"
                        alt="{{ __('mksine::frontend.home_featured_domains_logo_alt', ['tld' => $row['tld']]) }}"
                        width="120"
                        height="84"
                        loading="lazy"
                        decoding="async"
                        class="mb-3 h-14 w-auto max-w-full object-contain sm:h-16"
                    />
                    <span class="mb-3 line-clamp-2 min-h-8 text-xs text-gray-600 dark:text-gray-400 sm:text-sm">
                        {{ $row['category'] }}
                    </span>
                    <div class="mt-auto flex flex-col items-center gap-0.5">
                        <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $row['price'] }}
                        </span>
                        @if (! empty($row['original']))
                            <del class="text-sm text-gray-400 dark:text-gray-500">
                                {{ $row['original'] }}
                            </del>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6 text-center md:hidden">
            <a href="#" class="inline-flex items-center font-semibold text-violet-600 dark:text-violet-400">
                {{ __('mksine::frontend.home_featured_domains_view_all') }}
                <span class="ms-1" aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</section>
