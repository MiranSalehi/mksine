<?php

declare(strict_types=1);

use Miran\Mksine\Core\PageBuilder\Components\ColumnsComponent;

test('columns validate strips legacy grid-only keys', function (): void {
    $data = ColumnsComponent::validate([
        'columns' => 2,
        'width_mode' => 'custom',
        'column_spans' => [['span' => 6]],
        'layout' => 'equal',
    ]);

    expect($data)->not->toHaveKey('width_mode')
        ->not->toHaveKey('column_spans')
        ->and($data['columns'])->toBe(2);
});
