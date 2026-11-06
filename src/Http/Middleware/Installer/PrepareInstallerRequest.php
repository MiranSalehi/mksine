<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Middleware\Installer;

use Closure;
use Illuminate\Http\Request;
use Miran\Mksine\Core\Install\Web\InstallerOwnership;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the installer language and keeps every page after the language choice closed
 * to sessions that do not own the installation.
 */
class PrepareInstallerRequest
{
    /** @var list<string> */
    public const LOCALES = ['fa', 'en'];

    public const LOCALE_SESSION_KEY = 'mksine_installer.locale';

    /** @var list<string> */
    public const OPEN_ROUTES = ['mksine.installer.welcome', 'mksine.installer.language', 'mksine.installer.verify', 'mksine.installer.verify.store', 'mksine.installer.handoff'];

    public function __construct(private readonly InstallerOwnership $ownership) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::LOCALE_SESSION_KEY);

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::LOCALES) ?? 'en';
        }

        app()->setLocale((string) $locale);

        if ($request->routeIs(...self::OPEN_ROUTES) || $this->ownership->isOwner($request->session())) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('mksine::install.web.token_required')], Response::HTTP_FORBIDDEN);
        }

        return $this->ownership->hasOwner()
            ? redirect()->route('mksine.installer.verify')
            : redirect()->route('mksine.installer.welcome');
    }
}
