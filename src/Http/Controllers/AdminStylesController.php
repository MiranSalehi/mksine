<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Miran\Mksine\Filament\Support\MksinePanelStyles;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AdminStylesController
{
    public function __invoke(): BinaryFileResponse
    {
        $path = MksinePanelStyles::cssPath();

        abort_unless(is_string($path) && is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
