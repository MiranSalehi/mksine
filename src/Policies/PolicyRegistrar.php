<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Illuminate\Support\Facades\Gate;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Entry;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderSection;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registers a policy for every model behind a core admin resource.
 *
 * `App\Policies\{Model}Policy` (what `shield:generate` writes) wins when it exists; otherwise the
 * package default from this namespace is used. A model without any policy would be fully open in
 * Filament, which is what happened on hosts where `app/Policies` was never deployed.
 */
final class PolicyRegistrar
{
    public const string APP_POLICY_NAMESPACE = 'App\\Policies\\';

    /**
     * Model => package default policy.
     *
     * @return array<class-string, class-string>
     */
    public static function defaults(): array
    {
        $defaults = [
            Category::class => CategoryPolicy::class,
            Tag::class => TagPolicy::class,
            Comment::class => CommentPolicy::class,
            Media::class => MediaPolicy::class,
            Menu::class => MenuPolicy::class,
            MenuLocation::class => MenuLocationPolicy::class,
            Page::class => PagePolicy::class,
            Post::class => PostPolicy::class,
            Entry::class => EntryPolicy::class,
            Role::class => RolePolicy::class,
            GeoCountry::class => GeoCountryPolicy::class,
            GeoState::class => GeoStatePolicy::class,
            GeoCity::class => GeoCityPolicy::class,
        ];

        $roleModel = app(PermissionRegistrar::class)->getRoleClass();

        if (is_string($roleModel) && $roleModel !== Role::class && class_exists($roleModel)) {
            $defaults[$roleModel] = RolePolicy::class;
        }

        return $defaults;
    }

    /**
     * Policy to register for a model: the app's own one when it exists, else the package default.
     *
     * @param  class-string  $defaultPolicy
     * @return class-string
     */
    public static function resolve(string $defaultPolicy, string $appNamespace = self::APP_POLICY_NAMESPACE): string
    {
        $appPolicy = $appNamespace . class_basename($defaultPolicy);

        return class_exists($appPolicy) ? $appPolicy : $defaultPolicy;
    }

    public static function register(string $appNamespace = self::APP_POLICY_NAMESPACE): void
    {
        foreach (self::defaults() as $model => $defaultPolicy) {
            Gate::policy($model, self::resolve($defaultPolicy, $appNamespace));
        }

        // Page builder sections follow the page permissions and have no app-level variant.
        Gate::policy(PageBuilderSection::class, PageBuilderSectionPolicy::class);

        self::registerUserPolicy($appNamespace);
    }

    /**
     * The model behind the user resource.
     *
     * @return class-string
     */
    public static function userModel(): string
    {
        $model = config('mksine.user_model', 'App\\Models\\User');

        return is_string($model) && $model !== '' ? $model : 'App\\Models\\User';
    }

    /**
     * The user model is the app's own, so an existing policy for it wins: `App\Policies\{Model}Policy`,
     * one Laravel discovers by convention, or one a plugin registered (ecom's `CustomerPolicy`).
     */
    private static function registerUserPolicy(string $appNamespace): void
    {
        $userModel = self::userModel();

        if (! class_exists($userModel)) {
            return;
        }

        $appPolicy = $appNamespace . class_basename($userModel) . 'Policy';

        if (class_exists($appPolicy)) {
            Gate::policy($userModel, $appPolicy);

            return;
        }

        if (Gate::getPolicyFor($userModel) !== null) {
            return;
        }

        Gate::policy($userModel, UserPolicy::class);
    }
}
