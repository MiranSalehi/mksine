<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

/**
 * Wraps nested blocks with horizontal inset (padding-inline) and optional max width.
 */
final class ContainerInsetComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'container_inset';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_container_inset');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-pointing-in';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_container_inset');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('padding_inline')
                ->label(__('mksine::page_builder.component_labels.container_inset_padding_inline'))
                ->options([
                    'none' => __('mksine::page_builder.component_labels.container_inset_padding_none'),
                    'xs' => __('mksine::page_builder.component_labels.container_inset_padding_xs'),
                    'sm' => __('mksine::page_builder.component_labels.container_inset_padding_sm'),
                    'md' => __('mksine::page_builder.component_labels.container_inset_padding_md'),
                    'lg' => __('mksine::page_builder.component_labels.container_inset_padding_lg'),
                    'xl' => __('mksine::page_builder.component_labels.container_inset_padding_xl'),
                    '2xl' => __('mksine::page_builder.component_labels.container_inset_padding_2xl'),
                ])
                ->default('md')
                ->required()
                ->native(false),
            Select::make('max_width')
                ->label(__('mksine::page_builder.component_labels.container_inset_max_width'))
                ->options([
                    'full' => __('mksine::page_builder.component_labels.container_inset_width_full'),
                    'prose' => __('mksine::page_builder.component_labels.container_inset_width_prose'),
                    '3xl' => __('mksine::page_builder.component_labels.container_inset_width_3xl'),
                    '5xl' => __('mksine::page_builder.component_labels.container_inset_width_5xl'),
                    '6xl' => __('mksine::page_builder.component_labels.container_inset_width_6xl'),
                    '7xl' => __('mksine::page_builder.component_labels.container_inset_width_7xl'),
                ])
                ->default('full')
                ->required()
                ->native(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'padding_inline' => 'md',
            'max_width' => 'full',
        ];
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return 1;
    }

    public static function getBuilderChildRegionLabel(int $columnIndex, int $columnCount): string
    {
        return __('mksine::page_builder.container_inset_nested_label');
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $instance['children'] = [
            [
                'id' => uniqid('col_'),
                'items' => [],
            ],
        ];

        return $instance;
    }

    public static function validate(array $data): array
    {
        $allowedPadding = ['none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl'];
        $p = $data['padding_inline'] ?? 'md';
        $data['padding_inline'] = in_array($p, $allowedPadding, true) ? $p : 'md';

        $allowedMax = ['full', 'prose', '3xl', '5xl', '6xl', '7xl'];
        $m = $data['max_width'] ?? 'full';
        $data['max_width'] = in_array($m, $allowedMax, true) ? $m : 'full';

        return $data;
    }
}
