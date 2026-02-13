<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

class BlankTemplate
{
    public static function config(): array
    {
        return [
            'name' => 'Blank Canvas',
            'description' => 'Start from scratch with an empty page',
            'category' => 'General',
            'thumbnail' => null,
            'blocks' => [],
        ];
    }
}
