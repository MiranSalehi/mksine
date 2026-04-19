@php
    $clinicFeatureCards = [
        ['slug' => 'electronic_file', 'gradient' => 'from-blue-500 to-blue-600', 'icon' => 'users'],
        ['slug' => 'scheduling', 'gradient' => 'from-emerald-500 to-emerald-600', 'icon' => 'calendar'],
        ['slug' => 'comprehensive_reports', 'gradient' => 'from-violet-500 to-violet-600', 'icon' => 'chart-column'],
        ['slug' => 'inventory', 'gradient' => 'from-yellow-500 to-yellow-600', 'icon' => 'clipboard-list'],
        ['slug' => 'accounting', 'gradient' => 'from-purple-500 to-purple-600', 'icon' => 'credit-card'],
        ['slug' => 'festival_discount', 'gradient' => 'from-pink-500 to-pink-600', 'icon' => 'settings'],
        ['slug' => 'survey', 'gradient' => 'from-indigo-500 to-indigo-600', 'icon' => 'message-square'],
        ['slug' => 'smart_sms', 'gradient' => 'from-slate-500 to-slate-600', 'icon' => 'shield'],
        ['slug' => 'online_consult', 'gradient' => 'from-cyan-500 to-cyan-600', 'icon' => 'settings'],
        ['slug' => 'patient_club', 'gradient' => 'from-rose-500 to-rose-600', 'icon' => 'users'],
        ['slug' => 'staff_management', 'gradient' => 'from-orange-500 to-orange-600', 'icon' => 'shield'],
        ['slug' => 'per_case_commission', 'gradient' => 'from-teal-500 to-teal-600', 'icon' => 'credit-card'],
        ['slug' => 'clinic_analytics_revenue', 'gradient' => 'from-amber-500 to-amber-600', 'icon' => 'chart-column'],
        ['slug' => 'call_center', 'gradient' => 'from-lime-500 to-lime-600', 'icon' => 'headphones'],
        ['slug' => 'custom_website', 'gradient' => 'from-red-500 to-red-600', 'icon' => 'sparkles'],
        ['slug' => 'card_reader', 'gradient' => 'from-green-500 to-green-600', 'icon' => 'credit-card'],
        ['slug' => 'smart_pen', 'gradient' => 'from-sky-500 to-sky-600', 'icon' => 'pen'],
        ['slug' => 'touch_kiosk', 'gradient' => 'from-fuchsia-500 to-fuchsia-600', 'icon' => 'monitor-smartphone'],
        ['slug' => 'live_support', 'gradient' => 'from-indigo-500 to-indigo-600', 'icon' => 'headphones'],
        ['slug' => 'fast_secure', 'gradient' => 'from-red-500 to-red-600', 'icon' => 'shield-check'],
    ];
@endphp

<section
    class="home-clinic-features relative overflow-hidden bg-stone-50 py-12 sm:py-16 dark:bg-slate-950"
    dir="rtl"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="home-clinic-features-heading"
    aria-describedby="home-clinic-features-subheading"
>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mb-12 text-center lg:mb-16">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/60 px-4 py-2 text-sm font-medium text-indigo-700 shadow-lg backdrop-blur-xl lg:mb-8 lg:px-5 lg:py-2.5 dark:border-slate-600/50 dark:bg-slate-800/80 dark:text-indigo-300 dark:shadow-black/25"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                    aria-hidden="true"
                >
                    <path
                        d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"
                    ></path>
                    <path d="M20 3v4"></path>
                    <path d="M22 5h-4"></path>
                    <path d="M4 17v2"></path>
                    <path d="M5 18H3"></path>
                </svg>
                {{ __('mksine::frontend.home_clinic_features_badge') }}
            </div>
            <h2
                id="home-clinic-features-heading"
                class="mb-6 text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:mb-8 lg:text-5xl xl:text-6xl dark:text-gray-50"
            >
                {{ __('mksine::frontend.home_clinic_features_heading_prefix') }}<span
                    class="bg-gradient-to-r from-indigo-600 to-indigo-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-purple-400"
                >{{ __('mksine::frontend.home_clinic_features_heading_accent') }}</span>
            </h2>
            <p
                id="home-clinic-features-subheading"
                class="mx-auto max-w-3xl text-pretty text-lg leading-relaxed text-gray-600 lg:text-xl dark:text-gray-400"
            >
                {{ __('mksine::frontend.home_clinic_features_subheading') }}
            </p>
        </header>
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($clinicFeatureCards as $index => $card)
                <article
                    class="group relative cursor-pointer overflow-hidden rounded-2xl border border-transparent bg-white/90 p-6 shadow-lg shadow-gray-900/5 backdrop-blur-xl transition-all duration-700 hover:shadow-xl hover:shadow-gray-900/10 transform-gpu dark:border-slate-700/50 dark:bg-slate-800 dark:shadow-black/30 dark:hover:shadow-black/40"
                    style="animation-delay: {{ $index * 100 }}ms"
                >
                    <div
                        class="absolute inset-0 rounded-2xl bg-gradient-to-br p-px opacity-0 transition-all duration-300 group-hover:opacity-100 {{ $card['gradient'] }}"
                    >
                        <div class="h-full w-full rounded-2xl bg-white dark:bg-slate-800"></div>
                    </div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="mb-4">
                            <div
                                class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 {{ $card['gradient'] }}"
                            >
                                @include('mksine::themes.mksine.partials.home.clinic-feature-icon', ['name' => $card['icon']])
                            </div>
                        </div>
                        <h3
                            class="mb-3 text-lg font-bold text-gray-900 transition-all duration-300 group-hover:bg-gradient-to-r group-hover:from-indigo-600 group-hover:to-purple-600 group-hover:bg-clip-text group-hover:text-transparent dark:text-gray-50 dark:group-hover:from-indigo-400 dark:group-hover:to-purple-400"
                        >
                            {{ __('mksine::frontend.home_clinic_feature_'.$card['slug'].'_title') }}
                        </h3>
                        <p class="text-pretty text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            {{ __('mksine::frontend.home_clinic_feature_'.$card['slug'].'_body') }}
                        </p>
                        <div
                            class="mt-4 h-0.5 w-0 rounded-full bg-gradient-to-r transition-all duration-500 group-hover:w-full {{ $card['gradient'] }}"
                        ></div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
