<?php

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mks:make-theme
                            {name : The name of the theme}
                            {--identifier= : Custom identifier (defaults to slugified name)}
                            {--author= : Theme author name}
                            {--description= : Theme description}
                            {--force : Overwrite existing theme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new MKSine theme scaffold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $identifier = $this->option('identifier') ?: Str::slug($name);
        $author = $this->option('author') ?: 'Your Name';
        $description = $this->option('description') ?: 'A custom theme for MKSine';
        $force = $this->option('force');

        $themePath = resource_path("views/themes/{$identifier}");

        // Check if theme already exists
        if (File::isDirectory($themePath) && ! $force) {
            $this->error("Theme '{$identifier}' already exists at {$themePath}");
            $this->info('Use --force to overwrite the existing theme.');

            return self::FAILURE;
        }

        $this->info("Creating theme '{$name}'...");
        $this->newLine();

        // Create directory structure (matches default theme)
        $directories = [
            $themePath,
            "{$themePath}/layouts",
            "{$themePath}/partials",
            "{$themePath}/src/css",
            "{$themePath}/src/js",
            "{$themePath}/src/assets",
            "{$themePath}/src/fonts",
            "{$themePath}/dist",
            "{$themePath}/images",
            "{$themePath}/resources/lang/en",
        ];

        foreach ($directories as $dir) {
            File::ensureDirectoryExists($dir);
            $this->line("  <fg=green>Created:</> {$dir}");
        }

        File::put("{$themePath}/src/assets/.gitkeep", '');
        File::put("{$themePath}/src/fonts/.gitkeep", '');

        // Generate theme.json
        $themeJson = $this->generateThemeJson($name, $author, $description);
        File::put("{$themePath}/theme.json", $themeJson);
        $this->line("  <fg=green>Created:</> {$themePath}/theme.json");

        // Generate layout (header/footer inline, like default theme)
        $layout = $this->generateLayout($identifier);
        File::put("{$themePath}/layouts/index.blade.php", $layout);
        $this->line("  <fg=green>Created:</> {$themePath}/layouts/index.blade.php");

        // Generate templates (content-only, no x-dynamic-component wrapper)
        File::put("{$themePath}/home.blade.php", $this->generateHomeTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/home.blade.php");

        File::put("{$themePath}/single.blade.php", $this->generateSingleTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/single.blade.php");

        File::put("{$themePath}/category.blade.php", $this->generateCategoryTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/category.blade.php");

        File::put("{$themePath}/categories.blade.php", $this->generateCategoriesTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/categories.blade.php");

        File::put("{$themePath}/page.blade.php", $this->generatePageTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/page.blade.php");

        File::put("{$themePath}/author.blade.php", $this->generateAuthorTemplate());
        $this->line("  <fg=green>Created:</> {$themePath}/author.blade.php");

        // Generate source CSS (matches default theme: dark mode, @source, @plugin)
        File::put("{$themePath}/src/css/app.css", $this->generateSourceCss());
        $this->line("  <fg=green>Created:</> {$themePath}/src/css/app.css");

        // Generate source JS (matches default: dark mode, direction toggle, Alpine)
        File::put("{$themePath}/src/js/app.js", $this->generateSourceJs());
        $this->line("  <fg=green>Created:</> {$themePath}/src/js/app.js");

        // Generate placeholder dist files
        File::put("{$themePath}/dist/app.css", $this->generateDistCss());
        $this->line("  <fg=green>Created:</> {$themePath}/dist/app.css");

        File::put("{$themePath}/dist/app.js", $this->generateDistJs());
        $this->line("  <fg=green>Created:</> {$themePath}/dist/app.js");

        // Default custom CSS/JS (editable from Theme Manager)
        File::put("{$themePath}/dist/custom.css", $this->generateCustomCss());
        $this->line("  <fg=green>Created:</> {$themePath}/dist/custom.css");
        File::put("{$themePath}/dist/custom.js", $this->generateCustomJs());
        $this->line("  <fg=green>Created:</> {$themePath}/dist/custom.js");

        // Sample translation file (use __('theme-{$identifier}::theme.welcome') in views)
        File::put("{$themePath}/resources/lang/en/theme.php", $this->generateThemeLangStub());
        $this->line("  <fg=green>Created:</> {$themePath}/resources/lang/en/theme.php");

        // Generate partials (comment-item, post-comments; no separate header/footer)
        File::put("{$themePath}/partials/comment-item.blade.php", $this->generateCommentItemPartial($identifier));
        $this->line("  <fg=green>Created:</> {$themePath}/partials/comment-item.blade.php");

        File::put("{$themePath}/partials/post-comments.blade.php", $this->generatePostCommentsPartial($identifier));
        $this->line("  <fg=green>Created:</> {$themePath}/partials/post-comments.blade.php");

        // Generate package.json for development (build/dev run theme-publish)
        File::put("{$themePath}/package.json", $this->generatePackageJson($name, $identifier));
        $this->line("  <fg=green>Created:</> {$themePath}/package.json");

        // Copy assets script: src/assets + src/fonts → dist/assets (for fonts, images, etc.)
        File::put("{$themePath}/copy-assets.cjs", $this->generateCopyAssetsScript());
        $this->line("  <fg=green>Created:</> {$themePath}/copy-assets.cjs");

        // Script to run mks:theme-publish from theme dir (finds Laravel root by walking up; .cjs for type:module)
        File::put("{$themePath}/theme-publish.cjs", $this->generateThemePublishScript($identifier));
        $this->line("  <fg=green>Created:</> {$themePath}/theme-publish.cjs");

        // Generate tailwind.config.js
        File::put("{$themePath}/tailwind.config.js", $this->generateTailwindConfig());
        $this->line("  <fg=green>Created:</> {$themePath}/tailwind.config.js");

        // Generate .gitignore
        File::put("{$themePath}/.gitignore", $this->generateGitignore());
        $this->line("  <fg=green>Created:</> {$themePath}/.gitignore");

        // Generate BUILD.md instructions
        File::put("{$themePath}/BUILD.md", $this->generateBuildInstructions($identifier));
        $this->line("  <fg=green>Created:</> {$themePath}/BUILD.md");

        // Optional: theme.php + php/ for overrides and custom routes
        if ($this->option('force') ? true : $this->confirm('Enable theme overrides and custom routes (theme.php + php/)?', true)) {
            $studly = Str::studly(str_replace('-', ' ', $identifier));
            File::put("{$themePath}/theme.php", $this->generateThemePhpStub($studly));
            $this->line("  <fg=green>Created:</> {$themePath}/theme.php");
            File::ensureDirectoryExists("{$themePath}/php/Livewire");
            $this->line("  <fg=green>Created:</> {$themePath}/php/Livewire/");
            File::put("{$themePath}/php/Livewire/.gitkeep", '');
            $this->line("  <fg=green>Created:</> {$themePath}/php/Livewire/.gitkeep");
        }

        $this->newLine();
        $this->info("✓ Theme '{$name}' created successfully!");
        $this->newLine();

        // Display next steps
        $this->components->bulletList([
            "Edit source files in: <fg=cyan>{$themePath}/src/</>",
            'Build assets: <fg=cyan>cd ' . $themePath . ' && npm install && npm run build</>',
            'Publish to public: <fg=cyan>php artisan mks:theme-publish ' . $identifier . '</>',
            'Activate in admin panel: <fg=cyan>Appearance → Themes</>',
        ]);

        $this->newLine();
        $this->line('<fg=gray>See BUILD.md in your theme folder for detailed instructions.</>');

        return self::SUCCESS;
    }

    /**
     * Generate theme.json content.
     */
    protected function generateThemeJson(string $name, string $author, string $description): string
    {
        $data = [
            'name' => $name,
            'version' => '1.0.0',
            'author' => $author,
            'description' => $description,
            'screenshot' => 'screenshot.png',
            'assets' => [
                'css' => ['dist/app.css', 'dist/custom.css'],
                'js' => ['dist/app.js', 'dist/custom.js'],
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate layout template (matches default theme: header/footer inline, theme toggle).
     */
    protected function generateLayout(string $identifier): string
    {
        return <<<BLADE
<!DOCTYPE html>
@php
    \$locale = app()->getLocale();
    \$textDirection = in_array(\$locale, ['fa', 'ar', 'ku', 'he'], true) ? 'rtl' : 'ltr';
@endphp
<html lang="{{ str_replace('_', '-', \$locale) }}" dir="{{ \$textDirection }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \$title ?? config('app.name') }}</title>

    @themeAssets
</head>
<body class="{{ \$textDirection === 'rtl' ? 'rtl' : 'ltr' }}">
    @themeDoAction('layout.body_start')
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="container mx-auto max-w-6xl px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800">{{ config('app.name') }}</a>
                </div>

                <nav class="hidden md:flex gap-6">
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-blue-500 transition">{{ __('Home') }}</a>
                    <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-blue-500 transition">{{ __('Categories') }}</a>
                </nav>

                <div class="flex items-center gap-4">
                    <button class="theme-toggle" data-theme-toggle title="{{ __('Toggle Dark/Light') }}">
                        <svg class="sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="moon-icon hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <button class="md:hidden" data-mobile-menu aria-label="{{ __('Menu') }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        <nav data-mobile-nav class="hidden md:hidden border-t border-gray-200 py-4 px-4">
            <a href="{{ route('home') }}" class="block py-2 text-gray-600 hover:text-blue-500">{{ __('Home') }}</a>
            <a href="{{ route('categories.index') }}" class="block py-2 text-gray-600 hover:text-blue-500">{{ __('Categories') }}</a>
        </nav>
    </header>

    {!! \$slot !!}

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <div class="border-t border-gray-700 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
BLADE;
    }

    /**
     * Generate home template (content-only; matches default theme structure).
     */
    protected function generateHomeTemplate(): string
    {
        return <<<'BLADE'
<div>
    @themeDoAction('home.before_hero')

    <section class="mx-auto max-w-4xl px-6 py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-violet-600">{{ __('mksine::frontend.home_placeholder_eyebrow') }}</p>
        <h1 class="mt-4 text-4xl font-bold text-slate-900 dark:text-white">{{ __('mksine::frontend.home_placeholder_title', ['site' => config('app.name')]) }}</h1>
        <p class="mt-4 text-slate-600 dark:text-slate-300">{{ __('mksine::frontend.home_placeholder_lead') }}</p>
        <a href="{{ url('/admin') }}" class="mt-8 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white">{{ __('mksine::frontend.home_placeholder_admin_cta') }}</a>
    </section>

    @themeDoAction('home.after_hero')
</div>
BLADE;
    }

    /**
     * Generate single post template (content-only; matches default theme).
     */
    protected function generateSingleTemplate(): string
    {
        return <<<'BLADE'
<div>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('Home') }}</a>
                <span class="text-gray-400 dark:text-gray-500">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $post->title }}</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 py-12">
        <article class="lg:col-span-2">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $post->title }}</h1>
                <div class="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-400">
                    @if($post->author ?? null)
                        <p><a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500 font-semibold text-gray-800 dark:text-gray-100">{{ $post->author->name }}</a></p>
                    @endif
                    <p>{{ $post->published_at?->format('M d, Y') }}</p>
                </div>
            </div>

            @if($post->featuredImage?->url ?? $post->featured_image ?? null)
                <div class="mb-8">
                    <img src="{{ asset($post->featuredImage->url ?? $post->featured_image) }}" alt="{{ $post->title }}" class="w-full rounded-lg shadow-lg">
                </div>
            @endif

            <div class="prose prose-lg max-w-none dark:prose-invert mb-8">
                {!! mks_render_content($post->content) !!}
            </div>

            @if(($post->categories ?? collect())->isNotEmpty())
                <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold mb-2">{{ __('Categories') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($post->categories as $category)
                            <a href="{{ $category->getUrl() ?? '#' }}" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            @livewire('mksine::frontend.post-comments', ['postId' => $post->id])
        </article>
    </div>
</div>
BLADE;
    }

    /**
     * Generate category template (content-only; matches default theme).
     */
    protected function generateCategoryTemplate(): string
    {
        return <<<'BLADE'
<div>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('Home') }}</a>
                <span class="mx-2">/</span>
                <a href="{{ route('categories.index') }}" class="text-blue-500 hover:text-blue-600">{{ __('Categories') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $category->name }}</span>
            </div>
        </div>
    </div>

    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl font-bold mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-blue-100">{{ $category->description }}</p>
            @endif
            <p class="text-sm text-blue-100 mt-4">{{ $posts->total() }} {{ __('Articles') }}</p>
        </div>
    </section>

    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($posts as $post)
                <article class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                    <a href="{{ route('posts.show', $post->slug) }}">
                        <div class="h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                            @if($post->featuredImage?->url ?? $post->featured_image ?? null)
                                <img src="{{ asset($post->featuredImage->url ?? $post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 hover:text-blue-500 transition">{{ $post->title }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                            @if($post->author ?? null)
                                <p class="text-xs text-gray-500"><a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500">{{ $post->author->name }}</a></p>
                            @endif
                        </div>
                    </a>
                </article>
            @empty
                <p class="text-gray-500 dark:text-gray-400 col-span-full">{{ __('No articles in this category yet.') }}</p>
            @endforelse
        </div>

        @if($posts->hasPages())
            <div class="mt-12 flex justify-center">
                {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
            </div>
        @endif
    </div>
</div>
BLADE;
    }

    /**
     * Generate categories list template (content-only; matches default theme).
     */
    protected function generateCategoriesTemplate(): string
    {
        return <<<'BLADE'
<div>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('Home') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ __('Categories') }}</span>
            </div>
        </div>
    </div>

    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ __('All Categories') }}</h1>
            <p class="text-blue-100">{{ __('Browse all content by category') }}</p>
        </div>
    </section>

    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="space-y-12">
            @forelse($categories as $category)
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <a href="{{ $category->getUrl() }}" class="hover:text-blue-500 transition">{{ $category->name }}</a>
                        @if(isset($category->posts_count) && $category->posts_count > 0)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $category->posts_count }} {{ __('Articles') }})</span>
                        @endif
                    </h2>
                    @if($category->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl">{{ $category->description }}</p>
                    @endif
                    <a href="{{ $category->getUrl() }}" class="inline-block bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 rounded-lg px-6 py-4 font-semibold text-gray-800 dark:text-gray-200 transition">{{ __('View articles') }} →</a>
                </section>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-12">{{ __('No categories yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
BLADE;
    }

    /**
     * Generate page template (content-only; matches default theme, supports page builder).
     */
    protected function generatePageTemplate(): string
    {
        return <<<'BLADE'
<div>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('Home') }}</a>
                <span class="text-gray-400 dark:text-gray-500">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $page->title }}</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 py-12">
        <article>
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $page->title }}</h1>
                @if($page->published_at ?? null)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $page->published_at->format('M d, Y') }}</p>
                @endif
            </header>

            @if($page->usesBuilder() && !empty($page->builder_payload))
                <div class="builder-content space-y-0">
                    @foreach($page->builder_payload as $block)
                        @include('mksine::page-builder.render.block', ['block' => $block])
                    @endforeach
                </div>
            @else
                <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-gray-800 dark:prose-headings:text-gray-100 prose-p:text-gray-600 dark:prose-p:text-gray-300">
                    {!! mks_render_content($page->content) !!}
                </div>
            @endif
        </article>
    </div>
</div>
BLADE;
    }

    /**
     * Generate author template (content-only; matches default theme).
     */
    protected function generateAuthorTemplate(): string
    {
        return <<<'BLADE'
<div>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('Home') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ __('Author') }}: {{ $author->name }}</span>
            </div>
        </div>
    </div>

    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="flex-shrink-0">
                    @if($author->avatar_url ?? null)
                        <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="w-40 h-40 rounded-full border-4 border-white shadow-lg object-cover">
                    @else
                        <div class="w-40 h-40 rounded-full border-4 border-white shadow-lg bg-white/20 flex items-center justify-center text-5xl font-bold">
                            {{ $author->initials() ?? strtoupper(mb_substr($author->name, 0, 2)) }}
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-4xl font-bold mb-2">{{ $author->name }}</h1>
                    @if($author->bio ?? null)
                        <p class="text-blue-100 max-w-2xl mb-6">{{ $author->bio }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto max-w-6xl px-4 py-12">
        <section>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">{{ $author->name }}'s {{ __('Articles') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @forelse($posts as $post)
                    <article class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                        <a href="{{ route('posts.show', $post->slug) }}">
                            <div class="h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                @if($post->featuredImage?->url ?? $post->featured_image ?? null)
                                    <img src="{{ asset($post->featuredImage->url ?? $post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 hover:text-blue-500 transition">{{ $post->title }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                                <p class="text-xs text-gray-500">{{ $post->published_at?->format('M d, Y') }}</p>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 col-span-full">{{ __('No articles yet.') }}</p>
                @endforelse
            </div>

            @if($posts->hasPages())
                <div class="mt-8">
                    {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
                </div>
            @endif
        </section>
    </div>
</div>
BLADE;
    }

    /**
     * Generate source CSS (matches default theme: dark mode, @plugin forms, theme-only @source).
     */
    protected function generateSourceCss(): string
    {
        return <<<'CSS'
@import 'tailwindcss';
@plugin "@tailwindcss/forms";

/* Class-based dark mode (.dark on html) */
@custom-variant dark (&:where(.dark, .dark *));

/* Scan theme blade files only */
@source "../../*.blade.php";
@source "../../**/*.blade.php";

/*
|--------------------------------------------------------------------------
| Theme Source CSS
|--------------------------------------------------------------------------
| Run `npm run build` to compile to dist/app.css
*/

:root {
    --color-primary: #ec4899;
    --color-secondary: #f43f5e;
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --border-color: #e9ecef;
}

html.dark {
    --bg-primary: #1e293b;
    --bg-secondary: #0f172a;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
}

@layer base {
    body {
        @apply antialiased;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        transition: background-color 0.3s ease, color 0.3s ease;
    }
}

@layer components {
    .btn-primary {
        @apply px-6 py-2 rounded font-semibold transition-colors;
        background-color: var(--color-primary);
        color: white;
    }
    .btn-primary:hover { background-color: #db2777; }
    .theme-toggle {
        @apply relative inline-flex items-center justify-center w-10 h-10 cursor-pointer rounded-md bg-transparent transition-all;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    .theme-toggle:hover {
        background-color: var(--bg-secondary);
        border-color: var(--color-primary);
    }
}

@layer utilities {
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
}
CSS;
    }

    /**
     * Generate source JS (for development).
     */
    protected function generateSourceJs(): string
    {
        return <<<'JS'
/**
 * Theme Source JavaScript
 *
 * This is your development JS file. Add your custom scripts here.
 * Run `npm run build` to compile to dist/app.js
 */

import Alpine from 'alpinejs';
window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
    initDarkMode();
    initMobileMenu();
    initScrollToTop();
});

const SITE_THEME_KEY = 'site-theme';

function initDarkMode() {
    const currentTheme = localStorage.getItem(SITE_THEME_KEY) || 'light';
    const html = document.documentElement;
    if (currentTheme === 'dark') html.classList.add('dark');
    else html.classList.remove('dark');
    const themeToggle = document.querySelector('[data-theme-toggle]');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', !isDark);
            localStorage.setItem(SITE_THEME_KEY, isDark ? 'light' : 'dark');
        });
    }
}

function initMobileMenu() {
    const btn = document.querySelector('[data-mobile-menu]');
    const nav = document.querySelector('[data-mobile-nav]');
    if (btn && nav) btn.addEventListener('click', () => nav.classList.toggle('hidden'));
}

/**
 * Scroll to top button
 */
function initScrollToTop() {
    const btn = document.querySelector('[data-scroll-top]');
    if (!btn) return;

    window.addEventListener('scroll', () => {
        btn.classList.toggle('hidden', window.scrollY < 300);
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}
JS;
    }

    /**
     * Generate placeholder dist CSS.
     */
    protected function generateDistCss(): string
    {
        return <<<'CSS'
/*
|--------------------------------------------------------------------------
| Compiled Theme CSS
|--------------------------------------------------------------------------
|
| This file should be generated by running: npm run build
| Do not edit this file directly - edit src/css/app.css instead.
|
| IMPORTANT: Run `npm install && npm run build` to generate this file
| with Tailwind CSS compiled.
|
*/

/* Placeholder - will be replaced by compiled Tailwind CSS */
body { font-family: system-ui, sans-serif; }
CSS;
    }

    /**
     * Generate placeholder dist JS.
     */
    protected function generateDistJs(): string
    {
        return <<<'JS'
/**
 * Compiled Theme JavaScript
 *
 * Run: npm install && npm run build
 * Then: php artisan mks:theme-publish <identifier>
 * Do not edit this file directly - edit src/js/app.js instead.
 */
JS;
    }

    /**
     * Generate empty custom CSS (editable from Theme Manager).
     */
    protected function generateCustomCss(): string
    {
        return "/* Custom CSS – editable in Appearance → Themes → Custom CSS/JS */\n";
    }

    /**
     * Generate empty custom JS (editable from Theme Manager).
     */
    protected function generateThemeLangStub(): string
    {
        return <<<'PHP'
<?php

return [
    'welcome' => 'Welcome',
];
PHP;
    }

    protected function generateCustomJs(): string
    {
        return "/* Custom JS – editable in Appearance → Themes → Custom CSS/JS */\n";
    }

    /**
     * Generate package.json for theme development (matches default: @tailwindcss/forms).
     */
    protected function generatePackageJson(string $name, string $identifier): string
    {
        $data = [
            'name' => $identifier . '-theme',
            'version' => '1.0.0',
            'description' => "Theme: {$name}",
            'private' => true,
            'type' => 'module',
            'scripts' => [
                'dev' => "npm run dev:css & npm run dev:js & php ../../../../artisan mks:theme-publish {$identifier} && node ../../../../packages/mksine/bin/filament-assets.js",
                'dev:css' => 'npx @tailwindcss/cli -i src/css/app.css -o dist/app.css --watch',
                'dev:js' => 'npx esbuild src/js/app.js --bundle --outfile=dist/app.js --watch',
                'build' => "npm run build:css && npm run build:js && npm run copy:assets && php ../../../../artisan mks:theme-publish {$identifier} && node ../../../../packages/mksine/bin/filament-assets.js",
                'build:css' => 'npx @tailwindcss/cli -i src/css/app.css -o dist/app.css --minify',
                'build:js' => 'npx esbuild src/js/app.js --bundle --minify --outfile=dist/app.js',
                'copy:assets' => 'node copy-assets.cjs',
                'publish' => 'node theme-publish.cjs',
            ],
            'dependencies' => [
                '@tailwindcss/forms' => '^0.5.11',
                'alpinejs' => '^3.14.0',
            ],
            'devDependencies' => [
                '@tailwindcss/cli' => '^4.0.0',
                'esbuild' => '^0.24.0',
                'tailwindcss' => '^4.0.0',
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate copy-assets.cjs: copies src/assets and src/fonts to dist/assets.
     * Fonts in src/fonts go to dist/assets/fonts (url: ./assets/fonts/ in CSS).
     */
    protected function generateCopyAssetsScript(): string
    {
        return <<<'JS'
const fs = require('fs');
const path = require('path');

const root = __dirname;

// Copy src/assets → dist/assets
const srcAssets = path.join(root, 'src', 'assets');
const destAssets = path.join(root, 'dist', 'assets');
if (fs.existsSync(srcAssets)) {
  fs.mkdirSync(destAssets, { recursive: true });
  copyRecursive(srcAssets, destAssets);
  console.log('Copied src/assets to dist/assets');
}

// Copy src/fonts → dist/assets/fonts (fonts live in src/fonts, not src/assets)
const srcFonts = path.join(root, 'src', 'fonts');
const destFonts = path.join(root, 'dist', 'assets', 'fonts');
if (fs.existsSync(srcFonts)) {
  fs.mkdirSync(destFonts, { recursive: true });
  copyRecursive(srcFonts, destFonts);
  console.log('Copied src/fonts to dist/assets/fonts');
}

function copyRecursive(a, b) {
  fs.readdirSync(a, { withFileTypes: true }).forEach((x) => {
    const s = path.join(a, x.name);
    const d = path.join(b, x.name);
    if (x.isDirectory()) {
      fs.mkdirSync(d, { recursive: true });
      copyRecursive(s, d);
    } else {
      fs.copyFileSync(s, d);
    }
  });
}
JS;
    }

    /**
     * Generate theme-publish.js: finds Laravel root and runs mks:theme-publish.
     */
    protected function generateThemePublishScript(string $identifier): string
    {
        $id = addslashes($identifier);

        return <<<JS
#!/usr/bin/env node
/**
 * Run php artisan mks:theme-publish {$identifier} from Laravel app root.
 * Used by npm run build / npm run dev. Finds app root by walking up from cwd.
 */
const { execSync } = require('child_process');
const { existsSync } = require('fs');
const { join } = require('path');

let dir = process.cwd();
for (;;) {
    if (existsSync(join(dir, 'artisan'))) break;
    const parent = join(dir, '..');
    if (parent === dir) {
        dir = null;
        break;
    }
    dir = parent;
}
if (!dir || !existsSync(join(dir, 'artisan'))) {
    console.error('mksine theme: Laravel root (artisan) not found above theme directory.');
    process.exit(1);
}
try {
    execSync('php artisan mks:theme-publish {$id} --force', { stdio: 'inherit', cwd: dir });
} catch (e) {
    process.exit(e.status ?? 1);
}
JS;
    }

    /**
     * Generate tailwind.config.js (theme folder only; matches default structure).
     */
    protected function generateTailwindConfig(): string
    {
        return <<<'JS'
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './**/*.blade.php',
        './src/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#ec4899',
                secondary: '#f43f5e',
            },
        },
    },
    plugins: [],
}
JS;
    }

    /**
     * Generate .gitignore for theme.
     */
    protected function generateGitignore(): string
    {
        return <<<'GITIGNORE'
# Dependencies
node_modules/

# Build artifacts are NOT ignored - they should be committed
# so the theme works without build tools on production

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
GITIGNORE;
    }

    /**
     * Generate BUILD.md instructions.
     */
    protected function generateBuildInstructions(string $identifier): string
    {
        return <<<MD
# Theme Build Instructions

## Directory Structure (matches default theme)

```
{$identifier}/
├── layouts/
│   └── index.blade.php    # Main layout (header/footer inline, theme + direction toggles)
├── partials/
│   ├── comment-item.blade.php
│   └── post-comments.blade.php
├── home.blade.php
├── single.blade.php
├── category.blade.php
├── categories.blade.php
├── page.blade.php
├── author.blade.php
├── src/
│   ├── css/app.css       # Tailwind + dark mode + @tailwindcss/forms
│   ├── js/app.js         # Alpine, dark mode, RTL/LTR toggle
│   ├── assets/           # Images, etc. → dist/assets/
│   └── fonts/            # Custom fonts → dist/assets/fonts/ (url: ./assets/fonts/ in CSS)
├── dist/                  # Compiled assets (commit these!)
├── images/
├── theme.json
├── package.json
├── copy-assets.cjs        # Copies src/assets + src/fonts → dist/assets
└── tailwind.config.js
```

## Included Dependencies

- **Tailwind CSS 4** - with @tailwindcss/forms and class-based dark mode
- **Alpine.js 3** - for Livewire compatibility
- **esbuild** - JavaScript bundler

## Development Workflow

### 1. Install Dependencies

```bash
cd resources/views/themes/{$identifier}
npm install
```

### 2. Development Mode (watch for changes)

```bash
npm run dev
```

This watches both CSS and JS files and rebuilds on changes.

### 3. Production Build

```bash
npm run build
```

This creates minified CSS and JS in `dist/` with:
- Tailwind CSS fully compiled
- Alpine.js bundled into the JS file
- Assets copied from `src/assets` and `src/fonts` to `dist/assets`

### 4. Publish to Public Directory

```bash
php artisan mks:theme-publish {$identifier}
```

This copies `dist/` to `public/themes/{$identifier}/`.

### 5. Activate Theme

Go to **Admin Panel → Appearance → Themes** and click "Activate".

## Important Notes

- **Commit `dist/` folder**: Unlike typical JS projects, you SHOULD commit the `dist/` folder. This allows the theme to work on servers without Node.js.

- **No Node.js on production**: The server only needs the compiled CSS/JS files. No build step required.

- **Alpine.js is bundled**: Alpine.js is imported in `src/js/app.js` and bundled into `dist/app.js`. No CDN needed.

## Custom Fonts & Assets

- Put font files in `src/fonts/` (e.g. `src/fonts/iranyekan/woff2/*.woff2`)
- Reference in CSS as `url('./assets/fonts/...')` (relative to dist/app.css)
- Escape parentheses in filenames: `(fanum)` → `%28fanum%29` in url()
- Other assets go in `src/assets/` → `dist/assets/`

## Customization

- Edit `src/css/app.css` for styles (uses Tailwind directives)
- Edit `src/js/app.js` for JavaScript (Alpine.js is already imported)
- Edit `tailwind.config.js` for Tailwind configuration
- Edit Blade templates as needed

After any changes to source files, run `npm run build` and `php artisan mks:theme-publish {$identifier}`.
MD;
    }

    /**
     * Generate theme.php stub for override and route registration.
     * Namespace in stub must match ThemeBootstrap autoload: Themes\{StudlyIdentifier}\.
     */
    protected function generateThemePhpStub(string $studly): string
    {
        $namespace = "Themes\\{$studly}\\Livewire";

        return <<<PHP
<?php

/**
 * Theme bootstrap: register page overrides and/or custom routes.
 * Classes under php/ are autoloaded with namespace Themes\\{$studly}\\
 *
 * Override a frontend page (replace default component):
 *   \$register_override('home', \\{$namespace}\\\\Home::class);
 * Page keys: home, category-list, category-show, post-list, post-show, page-show, author-show
 *
 * Add custom routes (use Route:: inside the callback):
 *   \$register_routes(function () {
 *       \\Illuminate\\Support\\Facades\\Route::get('/gallery', \\{$namespace}\\\\Gallery::class)->name('gallery');
 *   });
 */

// Example: override home page with your own Livewire component
// \$register_override('home', \\{$namespace}\\\\Home::class);

// Example: add a custom route
// \$register_routes(function () {
//     \\Illuminate\\Support\\Facades\\Route::get('/gallery', \\{$namespace}\\\\Gallery::class)->name('gallery');
// });

PHP;
    }

    /**
     * Generate comment-item partial (uses $__theme_namespace for recursive include when set by package).
     */
    protected function generateCommentItemPartial(string $identifier): string
    {
        $namespace = 'mksine::themes.' . $identifier;
        return <<<BLADE
@props(['comment', 'isReply' => false])
@php \$ns = \$__theme_namespace ?? '{$namespace}'; @endphp
<div class="{{ \$isReply ? 'ml-6 sm:ml-10 mt-4 pl-4 border-l-2 border-gray-200 dark:border-gray-600' : '' }} border-b border-gray-200 dark:border-gray-700 pb-6 mb-6 last:border-b-0 last:pb-0 last:mb-0">
    <div class="flex gap-4 mb-3">
        @php
            \$user = \$comment->user ?? null;
            \$avatarUrl = \$user && method_exists(\$user, 'avatar_url') ? \$user->avatar_url : null;
            \$initials = \$user && method_exists(\$user, 'initials') ? \$user->initials() : strtoupper(mb_substr(\$comment->author_display_name ?? '', 0, 2));
        @endphp
        @if(\$avatarUrl)
            <img src="{{ \$avatarUrl }}" alt="{{ \$comment->author_display_name }}" class="w-10 h-10 rounded-full object-cover">
        @else
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-sm font-bold text-blue-600 dark:text-blue-400 shrink-0">{{ \$initials }}</div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ \$comment->author_display_name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \$comment->created_at->diffForHumans() }}</p>
        </div>
        @if(!\$isReply)
            <button type="button" wire:click="setReply({{ \$comment->id }})" class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400">{{ __('Reply') }}</button>
        @endif
    </div>
    @if(\$comment->hasRating() ?? false)
        <div class="flex gap-0.5 mb-2" aria-label="{{ \$comment->rating }} {{ __('stars') }}">
            @for(\$i = 1; \$i <= 5; \$i++)
                <span class="text-lg {{ \$i <= \$comment->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">★</span>
            @endfor
        </div>
    @endif
    <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ \$comment->content }}</p>
    @if((\$comment->replies ?? collect())->isNotEmpty())
        <div class="mt-4 space-y-0">
            @foreach(\$comment->replies as \$reply)
                @include(\$ns . '.partials.comment-item', ['comment' => \$reply, 'isReply' => true])
            @endforeach
        </div>
    @endif
</div>
BLADE;
    }

    /**
     * Generate post-comments partial (Livewire; uses $__theme_namespace when set).
     */
    protected function generatePostCommentsPartial(string $identifier): string
    {
        $namespace = 'mksine::themes.' . $identifier;
        return <<<BLADE
@php \$ns = \$__theme_namespace ?? '{$namespace}'; @endphp
<div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 p-6 rounded-lg" id="comments-section" wire:ignore.self>
    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-6">
        {{ __('Post Comments') }}
        @if(\$comments->isNotEmpty())
            <span class="text-gray-500 dark:text-gray-400 font-normal">({{ \$comments->count() }})</span>
        @endif
    </h3>
    @if(session('comment_message'))
        <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">{{ session('comment_message') }}</div>
    @endif
    @forelse(\$comments as \$comment)
        @include(\$ns . '.partials.comment-item', ['comment' => \$comment, 'isReply' => false])
    @empty
        <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">{{ __('No comments yet. Be the first to comment!') }}</p>
    @endforelse
    <div id="comment-form" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-4">{{ __('Leave a Comment') }}</h4>
        @if(\$parentComment ?? null)
            <div class="mb-4 p-3 rounded bg-gray-100 dark:bg-gray-700/50 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Replying to') }}: {{ \$parentComment->author_display_name }}
                <button type="button" wire:click="cancelReply" class="ml-2 text-blue-500 hover:text-blue-600">{{ __('Cancel') }}</button>
            </div>
        @endif
        <form wire:submit="submitComment" class="space-y-4">
            @if(!auth()->check())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="comment_author_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="comment_author_name" wire:model.live="author_name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100" placeholder="{{ __('Your name') }}">
                        @error('author_name')<p class="mt-1 text-sm text-red-500">{{ \$message }}</p>@enderror
                    </div>
                    <div>
                        <label for="comment_author_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }} <span class="text-red-500">*</span></label>
                        <input type="email" id="comment_author_email" wire:model.live="author_email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100" placeholder="{{ __('Your email') }}">
                        @error('author_email')<p class="mt-1 text-sm text-red-500">{{ \$message }}</p>@enderror
                    </div>
                </div>
            @endif
            @if(!\$this->parent_id)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rating') }} ({{ __('optional') }})</label>
                <div class="flex gap-1">
                    @for(\$i = 1; \$i <= 5; \$i++)
                        <button type="button" wire:click="setRating({{ \$i }})" class="p-1 text-2xl leading-none transition {{ (\$rating ?? 0) >= \$i ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600 hover:text-amber-200' }}" aria-label="{{ \$i }} {{ __('stars') }}">★</button>
                    @endfor
                </div>
                @error('rating')<p class="mt-1 text-sm text-red-500">{{ \$message }}</p>@enderror
            </div>
            @endif
            <div>
                <label for="comment_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Comment') }} <span class="text-red-500">*</span></label>
                <textarea id="comment_content" wire:model.live="content" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100" placeholder="{{ __('Write your comment...') }}"></textarea>
                @error('content')<p class="mt-1 text-sm text-red-500">{{ \$message }}</p>@enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition disabled:opacity-50">
                <span wire:loading.remove wire:target="submitComment">{{ __('Submit Comment') }}</span>
                <span wire:loading wire:target="submitComment">{{ __('Sending...') }}</span>
            </button>
        </form>
    </div>
</div>
BLADE;
    }
}
