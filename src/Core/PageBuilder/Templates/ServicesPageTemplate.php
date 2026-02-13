<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

class ServicesPageTemplate
{
    public static function config(): array
    {
        return [
            'name' => 'Services',
            'description' => 'Showcase your services with features and pricing',
            'category' => 'Business',
            'thumbnail' => null,
            'blocks' => [
                [
                    'id' => 'tpl-1',
                    'type' => 'hero',
                    'data' => [
                        'title' => 'Our Services',
                        'subtitle' => 'Comprehensive solutions tailored to your needs',
                        'background_color' => '#8b5cf6',
                        'text_color' => '#ffffff',
                        'button_text' => 'Explore Services',
                        'button_url' => '#services',
                        'button_style' => 'primary',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-2',
                    'type' => 'features',
                    'data' => [
                        'heading' => 'What We Offer',
                        'subheading' => '',
                        'columns' => 3,
                        'style' => 'simple',
                        'icon_style' => 'circle',
                        'features' => [
                            [
                                'icon' => 'heroicon-o-paint-brush',
                                'title' => 'Design',
                                'description' => 'Beautiful, user-friendly interfaces',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-code-bracket',
                                'title' => 'Development',
                                'description' => 'Robust, scalable applications',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-chart-bar',
                                'title' => 'Analytics',
                                'description' => 'Data-driven insights',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-rocket-launch',
                                'title' => 'Marketing',
                                'description' => 'Grow your audience',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-shield-check',
                                'title' => 'Security',
                                'description' => 'Protect your business',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-cog-6-tooth',
                                'title' => 'Support',
                                'description' => '24/7 dedicated assistance',
                                'link' => '',
                            ],
                        ],
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-3',
                    'type' => 'tabs',
                    'data' => [
                        'style' => 'underline',
                        'alignment' => 'left',
                        'orientation' => 'horizontal',
                        'tabs' => [
                            [
                                'title' => 'Basic',
                                'icon' => '',
                                'content' => '<h3>Starter Package</h3><p>Perfect for individuals and small projects</p><ul><li>Feature 1</li><li>Feature 2</li><li>Feature 3</li></ul>',
                            ],
                            [
                                'title' => 'Professional',
                                'icon' => '',
                                'content' => '<h3>Pro Package</h3><p>Ideal for growing businesses</p><ul><li>Everything in Basic</li><li>Advanced Feature 1</li><li>Advanced Feature 2</li></ul>',
                            ],
                            [
                                'title' => 'Enterprise',
                                'icon' => '',
                                'content' => '<h3>Enterprise Package</h3><p>For large organizations with custom needs</p><ul><li>Everything in Pro</li><li>Custom integrations</li><li>Dedicated support</li></ul>',
                            ],
                        ],
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-4',
                    'type' => 'cta',
                    'data' => [
                        'title' => 'Ready to Start Your Project?',
                        'description' => 'Let\'s discuss how we can help you succeed',
                        'button_text' => 'Request a Quote',
                        'button_url' => '/contact',
                        'background_color' => '#8b5cf6',
                        'text_color' => '#ffffff',
                    ],
                    'children' => null,
                ],
            ],
        ];
    }
}
