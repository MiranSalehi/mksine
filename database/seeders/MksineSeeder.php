<?php

namespace Miran\Mksine\Database\Seeders;

use Illuminate\Database\Seeder;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Setting;

class MksineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Optional env overrides (integers): MKSINE_SEED_CATEGORIES, MKSINE_SEED_PAGES, MKSINE_SEED_POSTS.
     */
    public function run(): void
    {
        $userClass = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userClass::first();

        if (! $user) {
            $this->command?->warn('No user found. Run User factory or DatabaseSeeder first.');

            return;
        }

        $userId = $user->id;

        $categoryCount = max(5, (int) env('MKSINE_SEED_CATEGORIES', 30));
        $pageCount = max(8, (int) env('MKSINE_SEED_PAGES', 35));
        $postCount = max(8, (int) env('MKSINE_SEED_POSTS', 40));

        $allCategories = Category::factory()->count($categoryCount)->create();
        $this->assignCategoryParents($allCategories);

        $this->seedPages($pageCount, $userId);
        $this->seedPosts($postCount, $userId);

        $allPosts = Post::all();
        foreach ($allPosts as $post) {
            $post->categories()->attach(
                $allCategories->random(random_int(1, min(5, $allCategories->count())))->pluck('id'),
                ['sort_order' => 0]
            );
        }

        $this->seedComments($userId);
        $this->seedDemoSettings($userId);
        $this->seedMenus();

        $this->command?->info(sprintf(
            'MKSine demo data: %d categories, %d pages, %d posts, %d comments, menus + settings.',
            Category::count(),
            Page::count(),
            Post::count(),
            Comment::count(),
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $categories
     */
    protected function assignCategoryParents($categories): void
    {
        if ($categories->count() < 6) {
            return;
        }

        $rootCount = max(3, (int) ceil($categories->count() * 0.2));
        $roots = $categories->take($rootCount);
        $rootIds = $roots->pluck('id')->all();
        $remainder = $categories->slice($rootCount);

        foreach ($remainder as $category) {
            if (fake()->boolean(45)) {
                $category->update([
                    'parent_id' => fake()->randomElement($rootIds),
                ]);
            }
        }
    }

    protected function seedPages(int $total, int $userId): void
    {
        $nPublishedSimple = max(1, (int) round($total * 0.45));
        $nDraft = max(1, (int) round($total * 0.25));
        $nBuilder = max(1, (int) round($total * 0.20));
        $nScheduled = max(0, $total - $nPublishedSimple - $nDraft - $nBuilder);

        Page::factory()->count($nPublishedSimple)->simple()->published()->forUser($userId)->create();
        Page::factory()->count($nDraft)->simple()->draft()->forUser($userId)->create();
        Page::factory()->count($nBuilder)->builder()->published()->forUser($userId)->create();
        if ($nScheduled > 0) {
            Page::factory()->count($nScheduled)->scheduled()->forUser($userId)->create();
        }
    }

    protected function seedPosts(int $total, int $userId): void
    {
        $nPublished = max(1, (int) round($total * 0.70));
        $nDraft = max(1, (int) round($total * 0.20));
        $nArchived = max(0, $total - $nPublished - $nDraft);

        Post::factory()->count($nPublished)->published()->forAuthor($userId)->create();
        Post::factory()->count($nDraft)->draft()->forAuthor($userId)->create();
        if ($nArchived > 0) {
            Post::factory()->count($nArchived)->archived()->forAuthor($userId)->create();
        }
    }

    protected function seedComments(int $userId): void
    {
        $posts = Post::query()->where('status', 'published')->get();
        if ($posts->isEmpty()) {
            return;
        }

        foreach ($posts as $post) {
            $count = random_int(0, 4);
            for ($i = 0; $i < $count; $i++) {
                $status = fake()->randomElement([
                    Comment::STATUS_APPROVED,
                    Comment::STATUS_APPROVED,
                    Comment::STATUS_PENDING,
                    Comment::STATUS_SPAM,
                ]);

                $factory = Comment::factory()
                    ->forPost($post)
                    ->state(['status' => $status]);

                if (fake()->boolean(35)) {
                    $factory = $factory->forUser($userId);
                }

                $factory->create();
            }
        }

        $roots = Comment::query()->whereNull('parent_id')->inRandomOrder()->limit(20)->get();
        foreach ($roots as $root) {
            if (fake()->boolean(30)) {
                Comment::factory()
                    ->forPost($root->post_id)
                    ->replyTo($root)
                    ->approved()
                    ->create();
            }
        }
    }

    protected function seedDemoSettings(int $userId): void
    {
        $siteName = config('app.name', 'MKSine');

        Setting::query()->updateOrCreate(
            ['key' => 'site_name'],
            ['value' => $siteName]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'short_site_name'],
            ['value' => (string) str($siteName)->limit(16)]
        );

        $useBuilderHome = filter_var(env('MKSINE_SEED_BUILDER_HOME', false), FILTER_VALIDATE_BOOLEAN);

        if ($useBuilderHome && config('mksine.features.page_builder', false)) {
            $registry = app(TemplateRegistry::class);
            $blocks = $registry->getBlocks('mksine-default-home');

            $landing = Page::query()->firstOrCreate(
                ['slug' => 'mksine-default-landing'],
                [
                    'title' => 'Home',
                    'type' => 'builder',
                    'status' => 'published',
                    'content' => null,
                    'builder_payload' => $blocks,
                    'show_page_header' => false,
                    'builder_content_width' => 'full',
                    'meta_title' => null,
                    'meta_description' => null,
                    'published_at' => now(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );

            if ($landing->wasRecentlyCreated === false
                && empty($landing->builder_payload)
                && $landing->type === 'builder') {
                $landing->forceFill([
                    'builder_payload' => $blocks,
                    'show_page_header' => false,
                    'builder_content_width' => 'full',
                    'updated_by' => $userId,
                ])->save();
            }

            Setting::query()->updateOrCreate(
                ['key' => 'front_page_id'],
                ['value' => (string) $landing->id]
            );
        } else {
            $front = Page::published()->where('type', 'simple')->orderBy('id')->first();
            if ($front) {
                Setting::query()->updateOrCreate(
                    ['key' => 'front_page_id'],
                    ['value' => (string) $front->id]
                );
            }
        }
    }

    protected function seedMenus(): void
    {
        MenuLocation::registerDefaults([
            'header_primary' => 'Header (primary)',
            'footer_links' => 'Footer links',
        ]);

        $headerMenu = Menu::query()->firstOrCreate(
            ['slug' => 'main-header'],
            [
                'name' => 'Main header',
                'description' => 'Demo menu (MksineSeeder)',
            ]
        );

        $headerLoc = MenuLocation::findByKey('header_primary');

        $headerLoc?->assignMenu($headerMenu);

        foreach (['footer_column_domains', 'footer_column_services', 'footer_column_resellers', 'footer_column_about', 'footer_columns'] as $legacyFooterKey) {
            MenuLocation::findByKey($legacyFooterKey)?->unassignMenu();
        }

        Menu::query()->whereIn('slug', ['footer-domains', 'footer-services', 'footer-resellers', 'footer-about', 'footer-columns'])->delete();

        $this->seedHeaderPrimaryMegaMenu($headerMenu);

        $demoHash = '#';
        $footerColumnGroups = [
            [
                'heading' => 'DOMAINS',
                'links' => [
                    'Buy domain' => $demoHash,
                    'Whois' => $demoHash,
                    'Rates' => $demoHash,
                    'Accreditations' => $demoHash,
                ],
            ],
            [
                'heading' => 'SERVICES',
                'links' => [
                    'Web Hosting' => $demoHash,
                    'Email' => $demoHash,
                    'SSL Certificates' => $demoHash,
                    'WordPress' => $demoHash,
                ],
            ],
            [
                'heading' => 'RESELLERS',
                'links' => [
                    'Volume discounts' => $demoHash,
                    'API for developers' => $demoHash,
                    'WHMCS Plugin' => $demoHash,
                    'White Label Panel' => $demoHash,
                ],
            ],
            [
                'heading' => 'ABOUT US',
                'links' => [
                    'Contact us' => $demoHash,
                    'Customer area' => $demoHash,
                    'The company' => $demoHash,
                    'Blog' => url(Permalink::getUri('posts_url')),
                ],
            ],
        ];

        $footerMenu = Menu::query()->firstOrCreate(
            ['slug' => 'footer'],
            [
                'name' => 'Footer',
                'description' => 'Multi-column footer: each top-level item is a heading; children are links (MksineSeeder). Assigned to footer_links.',
            ]
        );

        MenuLocation::findByKey('footer_links')?->assignMenu($footerMenu);

        MenuItem::query()->where('menu_id', $footerMenu->id)->delete();

        $rootOrder = 0;
        foreach ($footerColumnGroups as $group) {
            $parent = MenuItem::query()->create([
                'menu_id' => $footerMenu->id,
                'parent_id' => null,
                'type' => MenuItem::TYPE_CUSTOM_LINK,
                'label' => $group['heading'],
                'url' => '#',
                'reference_id' => null,
                'order' => $rootOrder++,
                'target' => '_self',
            ]);

            $childOrder = 0;
            foreach ($group['links'] as $label => $itemUrl) {
                MenuItem::query()->create([
                    'menu_id' => $footerMenu->id,
                    'parent_id' => $parent->id,
                    'type' => MenuItem::TYPE_CUSTOM_LINK,
                    'label' => $label,
                    'url' => $itemUrl,
                    'reference_id' => null,
                    'order' => $childOrder++,
                    'target' => '_self',
                ]);
            }
        }
    }

    /**
     * Header (primary): Home, mega-menu roots (nested columns), Blog. Structure is editable in Menu Builder.
     *
     * @param  array<string, array<string, array<string, string>>>  $megaDefinition
     */
    protected function seedHeaderPrimaryMegaMenu(Menu $headerMenu, ?array $megaDefinition = null): void
    {
        MenuItem::query()->where('menu_id', $headerMenu->id)->delete();

        $placeholder = '#';
        $homeUrl = url(Permalink::getUri('home_page_url'));
        $blogUrl = url(Permalink::getUri('posts_url'));
        $firstPage = Page::published()->orderBy('title')->first();
        $samplePageUrl = $firstPage ? route('pages.show', $firstPage->slug) : $placeholder;

        $megaDefinition ??= [
            'Domains' => [
                'Tools' => [
                    'Smart search' => $placeholder,
                    'Bulk search' => $placeholder,
                    'Check Whois' => $placeholder,
                ],
                'Included with your domain' => [
                    'Redirect & Parking' => $placeholder,
                    'External access' => $placeholder,
                ],
                'Domain extensions' => [
                    'Generic domains' => $placeholder,
                    'New domains' => $placeholder,
                    'All extensions' => $placeholder,
                ],
                'Country code domains' => [
                    'Europe' => $placeholder,
                    'America' => $placeholder,
                    'Asia' => $placeholder,
                ],
                'Other' => [
                    'Rates' => $placeholder,
                    'Volume discounts' => $placeholder,
                    'Management area' => $placeholder,
                    'Domain accreditations' => $placeholder,
                    'API for developers' => $placeholder,
                ],
            ],
            'Hosting and email' => [
                'Plans' => [
                    'Web hosting overview' => $samplePageUrl,
                    'Email plans' => $placeholder,
                ],
                'Included services' => [
                    'Webmail' => $placeholder,
                    'WordPress' => $placeholder,
                    'DNS acceleration' => $placeholder,
                ],
            ],
            'SSL' => [
                'Certificates' => [
                    'Single domain' => $placeholder,
                    'Multidomain' => $placeholder,
                    'Wildcard' => $placeholder,
                ],
                'Brands' => [
                    'Sectigo' => $placeholder,
                    'GeoTrust' => $placeholder,
                    'All brands' => $placeholder,
                ],
            ],
            'Other services' => [
                'Resellers' => [
                    'Reseller program' => $placeholder,
                    'White label panel' => $placeholder,
                ],
                'Company' => [
                    'About us' => $samplePageUrl,
                    'Contact' => $placeholder,
                ],
            ],
            'Help' => [
                'Support' => [
                    'Help center' => $placeholder,
                    'Contact' => $placeholder,
                ],
                'Legal' => [
                    'Privacy policy' => $placeholder,
                    'Registrant rights' => $placeholder,
                ],
            ],
        ];

        $order = 0;

        MenuItem::query()->create([
            'menu_id' => $headerMenu->id,
            'parent_id' => null,
            'type' => MenuItem::TYPE_CUSTOM_LINK,
            'label' => 'Home',
            'url' => $homeUrl,
            'reference_id' => null,
            'order' => $order++,
            'target' => '_self',
        ]);

        foreach ($megaDefinition as $rootLabel => $columns) {
            $root = MenuItem::query()->create([
                'menu_id' => $headerMenu->id,
                'parent_id' => null,
                'type' => MenuItem::TYPE_CUSTOM_LINK,
                'label' => $rootLabel,
                'url' => $placeholder,
                'reference_id' => null,
                'order' => $order++,
                'target' => '_self',
            ]);

            $columnOrder = 0;
            foreach ($columns as $columnHeading => $links) {
                $column = MenuItem::query()->create([
                    'menu_id' => $headerMenu->id,
                    'parent_id' => $root->id,
                    'type' => MenuItem::TYPE_CUSTOM_LINK,
                    'label' => $columnHeading,
                    'url' => $placeholder,
                    'reference_id' => null,
                    'order' => $columnOrder++,
                    'target' => '_self',
                ]);

                $linkOrder = 0;
                foreach ($links as $linkLabel => $linkUrl) {
                    MenuItem::query()->create([
                        'menu_id' => $headerMenu->id,
                        'parent_id' => $column->id,
                        'type' => MenuItem::TYPE_CUSTOM_LINK,
                        'label' => $linkLabel,
                        'url' => $linkUrl,
                        'reference_id' => null,
                        'order' => $linkOrder++,
                        'target' => '_self',
                    ]);
                }
            }
        }

        MenuItem::query()->create([
            'menu_id' => $headerMenu->id,
            'parent_id' => null,
            'type' => MenuItem::TYPE_CUSTOM_LINK,
            'label' => 'Blog',
            'url' => $blogUrl,
            'reference_id' => null,
            'order' => $order++,
            'target' => '_self',
        ]);
    }
}
