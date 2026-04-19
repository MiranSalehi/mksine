<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

use Miran\Mksine\Core\PageBuilder\Components\MksineTestimonialsGridComponent;

class LandingPageTemplate
{
    public static function config(): array
    {
        return [
            'name' => __('mksine::page_builder.templates.landing_page.name'),
            'description' => __('mksine::page_builder.templates.landing_page.description'),
            'category' => __('mksine::page_builder.templates.categories.marketing'),
            'thumbnail' => null,
            'blocks' => [
                [
                    'id' => 'tpl-1',
                    'type' => 'hero',
                    'data' => [
                        'title' => 'Transform Your Business Today',
                        'subtitle' => 'Everything you need to succeed in one powerful platform',
                        'background_color' => '#3b82f6',
                        'text_color' => '#ffffff',
                        'button_text' => 'Get Started Free',
                        'button_url' => '#',
                        'button_style' => 'primary',
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-2',
                    'type' => 'features',
                    'data' => [
                        'heading' => 'Why Choose Us',
                        'subheading' => '',
                        'columns' => 3,
                        'style' => 'simple',
                        'icon_style' => 'circle',
                        'features' => [
                            [
                                'icon' => 'heroicon-o-bolt',
                                'title' => 'Lightning Fast',
                                'description' => 'Optimized for speed and performance',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-shield-check',
                                'title' => 'Secure & Reliable',
                                'description' => 'Enterprise-grade security standards',
                                'link' => '',
                            ],
                            [
                                'icon' => 'heroicon-o-device-phone-mobile',
                                'title' => 'Mobile First',
                                'description' => 'Beautiful on every device',
                                'link' => '',
                            ],
                        ],
                    ],
                    'children' => null,
                ],
                [
                    'id' => 'tpl-3',
                    'type' => MksineTestimonialsGridComponent::getType(),
                    'data' => MksineTestimonialsGridComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-4',
                    'type' => 'cta',
                    'data' => [
                        'title' => 'Ready to Get Started?',
                        'description' => 'Join thousands of satisfied customers today',
                        'button_text' => 'Start Free Trial',
                        'button_url' => '/signup',
                        'background_color' => '#6366f1',
                        'text_color' => '#ffffff',
                    ],
                    'children' => null,
                ],
            ],
        ];
    }
}
