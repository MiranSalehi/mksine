<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

class ContactPageTemplate
{
    public static function config(): array
    {
        return [
            'name' => 'Contact Us',
            'description' => 'Simple contact page with information sections',
            'category' => 'General',
            'thumbnail' => null,
            'blocks' => [
                [
                    'id' => 'tpl-1',
                    'type' => 'heading',
                    'data' => [
                        'text' => 'Get In Touch',
                        'level' => 'h1',
                        'alignment' => 'center',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-2',
                    'type' => 'text',
                    'data' => [
                        'content' => '<p class="text-center">We\'d love to hear from you. Reach out to us through any of the channels below.</p>',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-3',
                    'type' => 'columns',
                    'data' => [
                        'columns' => 3,
                        'layout' => 'equal',
                        'gap' => 'lg',
                    ],
                    'children' => [
                        [
                            'id' => 'col-1',
                            'items' => [
                                [
                                    'id' => 'tpl-3a',
                                    'type' => 'text',
                                    'data' => [
                                        'content' => '<div class="text-center"><div class="text-4xl mb-2">📧</div><h3>Email</h3><p>support@example.com</p></div>',
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
                                    'type' => 'text',
                                    'data' => [
                                        'content' => '<div class="text-center"><div class="text-4xl mb-2">📞</div><h3>Phone</h3><p>+1 (555) 123-4567</p></div>',
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
                                    'type' => 'text',
                                    'data' => [
                                        'content' => '<div class="text-center"><div class="text-4xl mb-2">📍</div><h3>Address</h3><p>123 Main St, City, Country</p></div>',
                                    ],
                                    'children' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'tpl-4',
                    'type' => 'cta',
                    'data' => [
                        'title' => 'Visit Our Office',
                        'description' => 'We\'re open Monday - Friday, 9 AM - 5 PM',
                        'button_text' => 'Get Directions',
                        'button_url' => '#',
                        'background_color' => '#10b981',
                        'text_color' => '#ffffff',
                    ],
                    'children' => null,
                ],
            ],
        ];
    }
}
