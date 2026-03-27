<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ __('mksine::page_builder.preview') }}</title>

    @if($theme)
        @foreach($theme->getCssAssets() as $css)
            <link rel="stylesheet" href="{{ $theme->isProjectTheme() ? asset("themes/{$theme->identifier}/{$css}") : asset("vendor/mksine/themes/{$theme->identifier}/{$css}") }}">
        @endforeach
    @endif

    @if($theme)
        @foreach($theme->getJsAssets() as $js)
            <script src="{{ $theme->isProjectTheme() ? asset("themes/{$theme->identifier}/{$js}") : asset("vendor/mksine/themes/{$theme->identifier}/{$js}") }}"></script>
        @endforeach
    @endif

    <style>
        .prose { max-width: 65ch; }
        .prose p { margin-top: 1.25em; margin-bottom: 1.25em; }
        .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 { margin-top: 2em; margin-bottom: 1em; font-weight: 700; line-height: 1.3; }
        .prose h1 { font-size: 2.25em; } .prose h2 { font-size: 1.875em; } .prose h3 { font-size: 1.5em; } .prose h4 { font-size: 1.25em; }
        .prose ul, .prose ol { padding-left: 1.5em; margin-top: 1.25em; margin-bottom: 1.25em; }
        .prose li { margin-top: 0.5em; margin-bottom: 0.5em; }
        .prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; font-style: italic; color: #6b7280; }
        .prose a { color: #6366f1; text-decoration: underline; }
        .prose code { background: #f1f5f9; padding: 0.2em 0.4em; border-radius: 0.25em; font-size: 0.875em; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gray-100 dark:bg-gray-900 font-sans antialiased">
    <div id="preview-app" x-data="{ device: 'desktop' }">

        {{-- Slim floating preview bar - minimal, doesn't affect content layout --}}
        <div class="fixed top-0 inset-x-0 z-50 flex justify-center pt-3 pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-2 rounded-full border border-gray-200/80 bg-white/90 px-4 py-2 shadow-lg shadow-black/5 backdrop-blur-xl dark:border-gray-700/60 dark:bg-gray-800/90">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    </svg>
                </span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('mksine::page_builder.preview_mode') }}</span>
                <div class="h-4 w-px bg-gray-200 dark:bg-gray-600"></div>
                <div class="flex gap-0.5">
                    <button @click="device='desktop'" :class="device==='desktop'?'bg-gray-900 text-white dark:bg-white dark:text-gray-900':'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="rounded-md p-1.5 transition-colors" title="Desktop">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                    </button>
                    <button @click="device='tablet'" :class="device==='tablet'?'bg-gray-900 text-white dark:bg-white dark:text-gray-900':'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="rounded-md p-1.5 transition-colors" title="Tablet">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </button>
                    <button @click="device='mobile'" :class="device==='mobile'?'bg-gray-900 text-white dark:bg-white dark:text-gray-900':'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="rounded-md p-1.5 transition-colors" title="Mobile">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H10.5Z" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Viewport wrapper: desktop = full, tablet/mobile = frame --}}
        <div class="min-h-screen pt-16 transition-all duration-300"
             :style="device==='desktop' ? {} : (device==='tablet' ? { maxWidth: '768px', marginLeft: 'auto', marginRight: 'auto', boxShadow: '0 0 0 1px rgb(0 0 0 / 0.05)' } : { maxWidth: '375px', marginLeft: 'auto', marginRight: 'auto', boxShadow: '0 0 0 1px rgb(0 0 0 / 0.05)', minHeight: '667px' })">

            {{-- Breadcrumb: same as themes/mksine/page.blade.php --}}
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="container mx-auto max-w-6xl px-4 py-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="text-pink-500 dark:text-pink-400">{{ __('mksine::frontend.home') }}</span>
                        <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                        <span class="text-gray-800 dark:text-gray-200">{{ $title }}</span>
                    </div>
                </div>
            </div>

            {{-- Main content: exact same structure as themes/mksine/page.blade.php --}}
            <div class="container mx-auto max-w-4xl px-4 py-12">
                <article>
                    <header class="mb-8">
                        <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $title }}</h1>
                    </header>

                    @if(empty($blocks))
                        <div class="builder-content flex min-h-[40vh] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 py-16 text-center dark:border-gray-600">
                            <p class="text-gray-500 dark:text-gray-400">{{ __('mksine::page_builder.no_content_yet') }}</p>
                            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">{{ __('mksine::page_builder.add_components_to_see_preview') }}</p>
                        </div>
                    @else
                        <div class="builder-content space-y-8">
                            @foreach($blocks as $block)
                                @include('mksine::page-builder.render.block', ['block' => $block])
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </div>
    </div>
</body>
</html>
