<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Core\PageBuilder\PageDocument;
use Miran\Mksine\Models\Page;

final class PageDocumentController
{
    public function __construct(private PageDocument $documents) {}

    public function index(): JsonResponse
    {
        $pages = ContentVisibility::constrain(Page::query(), 'page')
            ->published()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'type', 'updated_at'])
            ->map(fn (Page $page): array => [
                'id' => $page->getKey(),
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => $page->type,
                'updated_at' => $page->updated_at?->toAtomString(),
            ])
            ->values();

        return response()->json([
            'schema_version' => PageDocument::SCHEMA_VERSION,
            'data' => $pages,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = ContentVisibility::constrain(Page::query(), 'page')->published()->where('slug', $slug)->first();

        if (! $page instanceof Page) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! ContentVisibility::isVisible($page, 'page')) {
            return auth()->check()
                ? response()->json(['message' => 'Forbidden'], 403)
                : response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json($this->documents->forPage($page));
    }

    public function catalog(): JsonResponse
    {
        return response()->json($this->documents->catalog());
    }
}
