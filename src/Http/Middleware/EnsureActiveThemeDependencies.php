<?php

namespace Miran\Mksine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Miran\Mksine\Core\Theme\ThemeDependencyChecker;
use Miran\Mksine\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks storefront rendering when the active theme declares plugin dependencies
 * that are not currently active, and shows a friendly warning page instead.
 */
class EnsureActiveThemeDependencies
{
    public function __construct(
        private ThemeDependencyChecker $dependencies,
        private ThemeManager $themes,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('mksine.theme.screenshot', 'mksine.theme.custom.asset')) {
            return $next($request);
        }

        $missingPlugins = $this->dependencies->missingPlugins();

        if ($missingPlugins === []) {
            return $next($request);
        }

        $theme = $this->themes->getActive();

        return response()->view('mksine::frontend.theme-dependencies-missing', [
            'theme' => $theme,
            'missingPlugins' => $missingPlugins,
            'missingPluginLabels' => $this->dependencies->missingPluginLabels($theme),
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
