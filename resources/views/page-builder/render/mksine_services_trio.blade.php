@php
    $d = $data ?? [];
    $cards = $d['cards'] ?? [];
    $fallbacks = ['images/home-services-hosting.svg', 'images/home-services-email.svg', 'images/home-services-ssl.svg'];
@endphp
<section class="home-services-trio bg-violet-50 py-10 md:py-14 lg:py-16 xl:py-20 dark:bg-violet-950/40" aria-labelledby="home-services-trio-heading">
    <div class="mx-auto w-[90%] max-w-[87.5rem]">
        <h2 id="home-services-trio-heading" class="mx-auto mb-10 max-w-4xl text-balance text-center text-2xl font-bold tracking-tight text-gray-900 md:text-3xl lg:mb-14 dark:text-gray-50">
            {{ $d['section_title'] ?? '' }}
        </h2>
        <div class="grid grid-cols-1 gap-6 md:gap-8 lg:grid-cols-3 lg:gap-10 xl:mt-8">
            @foreach ($cards as $idx => $card)
                @php
                    $fb = $fallbacks[$idx] ?? $fallbacks[0];
                    $src = mksine_pb_media_url($card['image'] ?? null, $fb);
                @endphp
                <a
                    href="{{ $card['url'] ?? '#' }}"
                    class="group flex flex-col items-center gap-6 rounded-none bg-white p-8 text-center shadow-lg shadow-violet-900/10 transition duration-200 ease-out hover:-translate-y-2 hover:shadow-xl dark:bg-slate-800 dark:shadow-black/20"
                >
                    <img
                        src="{{ $src }}"
                        alt="{{ $card['image_alt'] ?? '' }}"
                        width="198"
                        height="205"
                        loading="lazy"
                        decoding="async"
                        class="w-40 shrink-0 lg:-mt-8 xl:-mt-12 xl:w-44"
                    />
                    <div class="flex flex-1 flex-col items-center gap-6">
                        <h3 class="text-lg font-semibold text-violet-600 md:text-xl dark:text-violet-400">
                            {{ $card['title'] ?? '' }}
                        </h3>
                        <p class="max-w-sm text-pretty text-base text-gray-600 md:text-lg dark:text-gray-300">
                            {{ $card['body'] ?? '' }}
                        </p>
                        <span class="mt-auto inline-flex rounded-md bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white transition group-hover:bg-violet-700 dark:bg-violet-500 dark:group-hover:bg-violet-600">
                            {{ $card['cta_label'] ?? '' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
