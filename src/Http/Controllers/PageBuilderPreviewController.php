<?php

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Miran\Mksine\Core\PageBuilder\PageBuilderPreviewStore;

class PageBuilderPreviewController
{
    public function __invoke(Request $request, string $token, PageBuilderPreviewStore $store): Response
    {
        $userId = $request->user()?->getAuthIdentifier();

        abort_unless(is_int($userId) || (is_string($userId) && ctype_digit($userId)), 403);

        $payload = $store->get((int) $userId, $token);

        abort_if($payload === null, 404);

        return response()->view('mksine::page-builder.preview.document', [
            'payload' => $payload,
        ]);
    }
}
