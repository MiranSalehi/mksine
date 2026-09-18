<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Miran\Mksine\Core\Hooks\Hooks;

/**
 * Storefront visibility for published content. Plugins filter query lists and single records.
 */
final class ContentVisibility
{
    public const string FILTER_QUERY = 'mksine.content.query';

    public const string FILTER_VISIBLE = 'mksine.content.visible';

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrain(Builder $query, string $type, ?Authenticatable $user = null): Builder
    {
        $user ??= auth()->user();
        $result = Hooks::filter(self::FILTER_QUERY, $query, $type, $user);

        return $result instanceof Builder ? $result : $query;
    }

    public static function assertVisible(Model $record, string $type, ?Authenticatable $user = null): void
    {
        $user ??= auth()->user();
        $ok = Hooks::filter(self::FILTER_VISIBLE, true, $record, $type, $user);

        if ($ok === true || $ok === 1 || $ok === '1') {
            return;
        }

        if ($user === null) {
            $login = Route::has('login') ? route('login') : url('/login');
            session()->put('url.intended', request()->fullUrl());

            throw new HttpResponseException(new RedirectResponse($login, 302));
        }

        abort(403);
    }
}
