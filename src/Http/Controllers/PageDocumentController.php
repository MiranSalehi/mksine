<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Miran\Mksine\Core\PageBuilder\PageDocument;
use Miran\Mksine\Models\Page;

final class PageDocumentController
{
    public function __construct(private PageDocument $documents) {}

    public function index(): JsonResponse
    {
        $pages = Page::query()
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
        $page = Page::query()->published()->where('slug', $slug)->first();

        if (! $page instanceof Page) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($this->documents->forPage($page));
    }

    public function catalog(): JsonResponse
    {
        return response()->json($this->documents->catalog());
    }
}
