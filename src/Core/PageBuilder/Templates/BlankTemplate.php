<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

class BlankTemplate
{
    public static function config(): array
    {
        return [
            'name' => __('mksine::page_builder.templates.blank.name'),
            'description' => __('mksine::page_builder.templates.blank.description'),
            'category' => __('mksine::page_builder.templates.categories.general'),
            'thumbnail' => null,
            'blocks' => [],
        ];
    }
}
