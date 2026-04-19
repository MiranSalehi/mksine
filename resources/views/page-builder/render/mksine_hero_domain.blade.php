@php
    $d = $data ?? [];
    $imgUrl = mksine_pb_media_url($d['illustration'] ?? null, 'images/home-hero-domain.png');
@endphp
<section class="home-hero-domain bg-[#FFD180] text-gray-900 dark:bg-slate-900 dark:text-gray-100" aria-labelledby="home-hero-domain-heading">
    <div class="mx-auto w-[90%] max-w-[87.5rem]">
        <div class="py-12 md:py-14 lg:py-16 xl:py-20">
            <div class="grid grid-cols-1 items-center justify-items-center gap-8 text-center md:grid-cols-2 md:gap-10 md:text-start lg:grid-cols-5 lg:gap-12">
                <div class="w-full max-w-md px-6 md:order-2 md:max-w-none md:px-10 lg:col-span-2 lg:px-14">
                    <div class="relative mx-auto flex max-w-sm justify-center md:max-w-full">
                        <img
                            src="{{ $imgUrl }}"
                            alt="{{ $d['illustration_alt'] ?? '' }}"
                            class="h-auto w-full max-w-md object-contain drop-shadow-md md:max-w-lg"
                            width="1024"
                            height="881"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high"
                        />
                    </div>
                </div>
                <div class="w-full max-w-[min(100%,91.666667%)] md:order-1 md:max-w-full lg:col-span-3">
                    <h1 id="home-hero-domain-heading" class="mb-4 font-extrabold tracking-tight text-gray-900 lg:mb-8 dark:text-gray-50">
                        <div class="text-4xl leading-[1.08] md:text-5xl xl:text-6xl [&_span.home-hero-heading-accent]:text-violet-600 [&_span.home-hero-heading-accent]:dark:text-violet-400">
                            @if (trim((string) ($d['heading_line1'] ?? '')) !== '')
                                <span class="block">{{ $d['heading_line1'] }}</span>
                            @endif
                            <span class="block">
                                @if (trim((string) ($d['heading_line2_prefix'] ?? '')) !== '')
                                    <span>{{ $d['heading_line2_prefix'] }}</span>
                                @endif
                                <span class="home-hero-heading-accent">{{ $d['heading_accent'] ?? '' }}</span>
                                @if (trim((string) ($d['heading_after'] ?? '')) !== '')
                                    <span>{{ $d['heading_after'] }}</span>
                                @endif
                            </span>
                        </div>
                    </h1>
                    <p class="mb-6 text-balance text-base text-gray-900 md:text-lg lg:mb-10 xl:text-2xl xl:font-light dark:text-gray-100">
                        {{ $d['subheading'] ?? '' }}
                    </p>
                    <a
                        href="{{ $d['cta_url'] ?? '#' }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-4 font-semibold whitespace-nowrap text-white transition hover:bg-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                    >
                        <span>{{ $d['cta_label'] ?? '' }}</span>
                        <span class="home-hero-cta-arrow rtl:rotate-180" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
