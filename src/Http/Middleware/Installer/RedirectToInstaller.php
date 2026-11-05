<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Middleware\Installer;

use Closure;
use Illuminate\Http\Request;
use Miran\Mksine\Core\Install\InstallationState;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: until MKSine is installed, every web request goes to the installer.
 */
class RedirectToInstaller
{
    public function __construct(private readonly InstallationState $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('mksine.installer.enabled', true) || $this->installation->isInstalled()) {
            return $next($request);
        }

        $path = self::installerPath();

        if ($request->is($path, $path . '/*', 'up')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'MKSine is not installed yet.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return redirect()->to('/' . $path);
    }

    public static function installerPath(): string
    {
        return trim((string) config('mksine.installer.path', 'install'), '/') ?: 'install';
    }
}
