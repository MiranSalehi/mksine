<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

class AboutPageTemplate
{
    public static function config(): array
    {
        return [
            'name' => 'About Us',
            'description' => 'Professional about page with team highlights',
            'category' => 'General',
            'thumbnail' => null,
            'blocks' => [
                [
                    'id' => 'tpl-1',
                    'type' => 'heading',
                    'data' => [
                        'text' => 'About Our Company',
                        'level' => 'h1',
                        'alignment' => 'center',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-2',
                    'type' => 'text',
                    'data' => [
                        'content' => '<p>We are a passionate team dedicated to creating exceptional experiences. Founded in 2020, our mission is to make technology accessible to everyone.</p>',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-3',
                    'type' => 'columns',
                    'data' => [
                        'columns' => 3,
                        'layout' => 'equal',
                        'gap' => 'md',
                    ],
                    'children' => [
                        [
                            'id' => 'col-1',
                            'items' => [
                                [
                                    'id' => 'tpl-3a',
                                    'type' => 'heading',
                                    'data' => [
                                        'text' => 'Our Vision',
                                        'level' => 'h3',
                                        'alignment' => 'left',
                                    ],
                                    'children' => null,
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-2',
                            'items' => [
                                [
                                    'id' => 'tpl-3b',
                                    'type' => 'heading',
                                    'data' => [
                                        'text' => 'Our Mission',
                                        'level' => 'h3',
                                        'alignment' => 'left',
                                    ],
                                    'children' => null,
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-3',
                            'items' => [
                                [
                                    'id' => 'tpl-3c',
                                    'type' => 'heading',
                                    'data' => [
                                        'text' => 'Our Values',
                                        'level' => 'h3',
                                        'alignment' => 'left',
                                    ],
                                    'children' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'tpl-4',
                    'type' => 'accordion',
                    'data' => [
                        'heading' => '',
                        'style' => 'bordered',
                        'allow_multiple' => false,
                        'first_open' => true,
                        'icon_position' => 'right',
                        'items' => [
                            [
                                'question' => 'What we do',
                                'answer' => '<p>We create innovative solutions that solve real problems.</p>',
                            ],
                            [
                                'question' => 'How we work',
                                'answer' => '<p>Collaboration, transparency, and excellence in everything.</p>',
                            ],
                            [
                                'question' => 'Why choose us',
                                'answer' => '<p>10+ years of experience and 1000+ happy clients.</p>',
                            ],
                        ],
                    ],
                    'children' => null,
                ],
            ],
        ];
    }
}
