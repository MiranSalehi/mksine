<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Health Blog' }}</title>

    @themeAssets
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="container mx-auto max-w-6xl px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        S
                    </div>
                    <span class="text-xl font-bold text-gray-800">Health Blog</span>
                </div>

                <nav class="hidden md:flex gap-6">
                    <x-mksine::menu location="header_primary" class="hidden md:flex gap-6" />
                </nav>

                <div class="flex items-center gap-4">
                    <!-- Direction Toggle (RTL/LTR) -->
                    <button class="direction-toggle" data-direction-toggle title="Toggle RTL/LTR">
                        <!-- Align Right Icon (RTL) -->
                        <svg class="rtl-icon hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10H3M21 6H7M21 14H7M21 18H3"></path>
                        </svg>
                        <!-- Align Left Icon (LTR) -->
                        <svg class="ltr-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 10H21M17 6H3M17 14H3M3 18H21"></path>
                        </svg>
                    </button>

                    <!-- Dark/Light Mode Toggle -->
                    <button class="theme-toggle" data-theme-toggle title="Toggle Dark/Light">
                        <svg class="sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg class="moon-icon hidden" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    <!-- Mobile Menu Toggle -->
                    <button class="md:hidden" @click="open = !open" x-data="{ open: false }">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    {!! $slot !!}

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h4 class="font-bold text-white mb-4">About</h4>
                    <p class="text-sm">Health Blog is a reliable source for health and wellness information</p>
                </div>
                <div class="md:col-span-2">
                    <x-mksine::menu location="footer_links" class="grid grid-cols-1 sm:grid-cols-2 gap-8" />
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Social Media</h4>
                    <div class="flex gap-3">
                        <a href="#" class="text-gray-400 hover:text-white transition">Facebook</a>
                        <a href="#" class="text-gray-400 hover:text-white transition">Twitter</a>
                        <a href="#" class="text-gray-400 hover:text-white transition">Instagram</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-8 text-center text-sm">
                <p>&copy; 2024 Health Blog. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
