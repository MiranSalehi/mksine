<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Responses;

use Miran\Mksine\Support\SvgSafety;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a theme or plugin screenshot straight from the package directory.
 *
 * Screenshots come out of uploaded archives and are rendered inside the admin panel on
 * the site's own origin, so an SVG screenshot is an XSS vector against the very users who
 * manage the site. Scriptable SVGs are refused, and everything is served with headers
 * that stop the browser from treating the file as an active document.
 */
final class ScreenshotResponse
{
    public static function make(string $path, string $mime): BinaryFileResponse
    {
        if (SvgSafety::isSvgMime($mime) && ! SvgSafety::fileIsSafe($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox",
        ]);
    }
}
