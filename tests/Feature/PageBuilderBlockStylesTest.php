<?php

it('ships default block styles with the shared renderer', function (): void {
    $html = view('mksine::page-builder.render.block', [
        'block' => [
            'id' => 'heading_1',
            'type' => 'heading',
            'data' => ['text' => 'Title', 'level' => 'h2', 'alignment' => 'left'],
        ],
    ])->render();

    $css = file_get_contents(base_path('packages/mksine/resources/dist/blocks.css'));

    expect($html)->toContain('class="mksine-block"')
        ->and($html)->toContain('data-mksine-blocks')
        ->and($html)->toContain('/css/miran/mksine/mksine-blocks.css?v=')
        ->and($css)->toContain('.mksine-block .text-slate-900')
        ->and($css)->toContain('.mksine-block .bg-violet-50')
        ->and($css)->not->toContain('prefers-color-scheme')
        ->and($css)->not->toContain(':root');
});
