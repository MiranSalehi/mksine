<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ __('Preview') }}</title>

    {{-- Theme CSS only (for styling) --}}
    @if($theme)
        @foreach($theme->getCssAssets() as $css)
            <link rel="stylesheet" href="{{ $theme->isProjectTheme() ? asset("themes/{$theme->identifier}/{$css}") : asset("vendor/mksine/themes/{$theme->identifier}/{$css}") }}">
        @endforeach
    @endif
    
    {{-- Alpine.js from theme assets (has better compatibility) --}}
    @if($theme)
        @foreach($theme->getJsAssets() as $js)
            <script src="{{ $theme->isProjectTheme() ? asset("themes/{$theme->identifier}/{$js}") : asset("vendor/mksine/themes/{$theme->identifier}/{$js}") }}"></script>
        @endforeach
    @endif

    <style>
        .prose { max-width: 65ch; }
        .prose p { margin-top: 1.25em; margin-bottom: 1.25em; }
        .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
            margin-top: 2em;
            margin-bottom: 1em;
            font-weight: 700;
            line-height: 1.3;
        }
        .prose h1 { font-size: 2.25em; }
        .prose h2 { font-size: 1.875em; }
        .prose h3 { font-size: 1.5em; }
        .prose h4 { font-size: 1.25em; }
        .prose ul, .prose ol { padding-left: 1.5em; margin-top: 1.25em; margin-bottom: 1.25em; }
        .prose li { margin-top: 0.5em; margin-bottom: 0.5em; }
        .prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; font-style: italic; color: #6b7280; }
        .prose a { color: #ec4899; text-decoration: underline; }
        .prose code { background: #f3f4f6; padding: 0.2em 0.4em; border-radius: 0.25em; font-size: 0.875em; }
        
        /* Hide Alpine elements until Alpine is ready */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div id="preview-app"
         x-data="{
             device: 'desktop',
             devices: {
                 desktop: { label: '🖥️ Desktop', width: '100%', maxWidth: 'none', icon: '🖥️' },
                 tablet: { label: '📱 Tablet', width: '768px', maxWidth: '768px', icon: '📱' },
                 mobile: { label: '📱 Mobile', width: '375px', maxWidth: '375px', icon: '📱' }
             }
         }">
    {{-- Preview Controls Bar --}}
    <div class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700 shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                {{-- Preview Label --}}
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 text-yellow-800 dark:text-yellow-200 font-semibold rounded-xl border-2 border-yellow-300 dark:border-yellow-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <span class="text-sm font-bold">{{ __('Preview Mode') }}</span>
                    </div>
                    <span class="hidden md:block text-sm text-gray-600 dark:text-gray-400 font-medium">
                        {{ __('This is how your page will look') }}
                    </span>
                </div>

                {{-- Device Switcher --}}
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1.5 shadow-inner">
                        <button
                            @click="device = 'desktop'"
                            :class="device === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-md scale-105' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                            class="group px-4 py-2.5 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 transition-all duration-200 flex items-center gap-2"
                            title="Desktop (Full Width)"
                            :aria-label="'Switch to Desktop view'"
                        >
                            <span class="text-lg">🖥️</span>
                            <span class="hidden sm:inline">Desktop</span>
                        </button>
                        <button
                            @click="device = 'tablet'"
                            :class="device === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-md scale-105' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                            class="group px-4 py-2.5 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 transition-all duration-200 flex items-center gap-2"
                            title="Tablet (768px)"
                            :aria-label="'Switch to Tablet view'"
                        >
                            <span class="text-lg">💻</span>
                            <span class="hidden sm:inline">Tablet</span>
                        </button>
                        <button
                            @click="device = 'mobile'"
                            :class="device === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-md scale-105' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                            class="group px-4 py-2.5 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 transition-all duration-200 flex items-center gap-2"
                            title="Mobile (375px)"
                            :aria-label="'Switch to Mobile view'"
                        >
                            <span class="text-lg">📱</span>
                            <span class="hidden sm:inline">Mobile</span>
                        </button>
                    </div>

                    {{-- Current Width Display --}}
                    <div class="hidden lg:flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-purple-600 dark:text-purple-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                        <span class="text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="devices[device].width"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview Content with Responsive Wrapper --}}
    <div class="py-8 px-4">
        <div class="mx-auto transition-all duration-300 ease-in-out bg-white dark:bg-gray-800 shadow-2xl rounded-lg overflow-hidden"
             :style="`max-width: ${devices[device].maxWidth}; width: ${devices[device].width};`">
            
            {{-- Actual Page Content --}}
            <div class="preview-content">
                @if(empty($blocks))
                    <div class="text-center py-20 px-4">
                        <div class="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">{{ __('No content yet') }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('Add some components to see the preview.') }}</p>
                    </div>
                @else
                    @foreach($blocks as $block)
                        @include('mksine::page-builder.render.block', ['block' => $block])
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Device Info Footer --}}
    <div class="fixed bottom-4 right-4 z-40"
         x-data="{ width: 0 }"
         x-init="setInterval(() => { const el = document.querySelector('.preview-content'); width = el ? el.offsetWidth : 0; }, 100)"
         x-init="setInterval(() => { const el = document.querySelector('.preview-content'); if(el) width = el.offsetWidth; }, 100)">
        <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-4 py-2 rounded-xl shadow-2xl border border-gray-700 dark:border-gray-600">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                </svg>
                <span class="font-semibold">Viewport:</span>
                <span class="font-mono text-green-400" x-text="width + 'px'"></span>
            </div>
        </div>
    </div>

    </div>{{-- /#preview-app --}}

    {{-- Debug and ensure Alpine.js works --}}
    <script>
        // Wait for Alpine to be fully loaded
        window.addEventListener('alpine:init', () => {
            console.log('✅ Alpine.js initialized successfully!');
        });
        
        // Fallback check
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (typeof window.Alpine === 'undefined') {
                    console.error('❌ Alpine.js not loaded! Device switcher will not work.');
                } else {
                    console.log('✅ Alpine.js is loaded:', window.Alpine);
                }
            }, 500);
        });
    </script>
</body>
</html>
