<?php

namespace Miran\Mksine\Core\PageBuilder;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Miran\Mksine\Core\Theme\ThemeManager;

class PagePreviewController extends Controller
{
    /**
     * Preview page builder content.
     */
    public function preview(Request $request)
    {
        $blocks = $request->input('blocks', []);
        $title = $request->input('title', 'Preview');

        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true) ?? [];
        }

        $themeManager = app(ThemeManager::class);
        $theme = $themeManager->getActive();

        // Try to use theme's page-builder-preview view, fallback to default
        $view = 'mksine::page-builder.preview';

        if ($theme) {
            $themePreviewView = $themeManager->getViewNamespace() . '.page-builder-preview';
            if (view()->exists($themePreviewView)) {
                $view = $themePreviewView;
            }
        }

        return view($view, [
            'blocks' => $blocks,
            'title' => $title,
            'theme' => $theme,
        ]);
    }
}
