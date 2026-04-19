<?php

namespace Miran\Mksine\Core\PageBuilder\Templates;

use Miran\Mksine\Core\PageBuilder\Components\MksineClinicFeaturesGridComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFeaturedDomainsComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineHeroDomainComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineServicesTrioComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineTestimonialsGridComponent;

class MksineDefaultHomeTemplate
{
    public static function config(): array
    {
        return [
            'name' => __('mksine::page_builder.templates.mksine_default_home.name'),
            'description' => __('mksine::page_builder.templates.mksine_default_home.description'),
            'category' => __('mksine::page_builder.templates.categories.marketing'),
            'thumbnail' => null,
            'blocks' => [
                [
                    'id' => 'tpl-m1',
                    'type' => MksineFinanceShowcaseComponent::getType(),
                    'data' => MksineFinanceShowcaseComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m2',
                    'type' => MksineHeroDomainComponent::getType(),
                    'data' => MksineHeroDomainComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m3',
                    'type' => MksineServicesTrioComponent::getType(),
                    'data' => MksineServicesTrioComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m4',
                    'type' => MksineFeaturedDomainsComponent::getType(),
                    'data' => MksineFeaturedDomainsComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m5',
                    'type' => MksineClinicFeaturesGridComponent::getType(),
                    'data' => MksineClinicFeaturesGridComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m6',
                    'type' => MksineTestimonialsGridComponent::getType(),
                    'data' => MksineTestimonialsGridComponent::getDefaultData(),
                    'children' => null,
                ],
                [
                    'id' => 'tpl-m7',
                    'type' => 'mksine_pricing_plans',
                    'data' => self::pricingPlansPresetData(),
                    'children' => null,
                ],
            ],
        ];
    }

    /**
     * Default payload for the pricing-plans block (Modireshop plugin).
     *
     * Kept inline so the core package does not depend on the plugin class.
     *
     * @return array<string, mixed>
     */
    private static function pricingPlansPresetData(): array
    {
        return [
            'plans_source' => 'modireshop_latest',
            'plans_limit' => 6,
            'modireshop_sort' => 'created_at_desc',
            'plan_ids' => [],
            'db_suggest_badge_text' => 'Suggested',
            'text_direction' => 'auto',
            'badge' => 'Pricing',
            'title_prefix' => 'Plans that ',
            'title_accent' => 'scale with you',
            'subtitle' => 'Start free, upgrade when you need SMS, storage, and advanced workflows.',
            'currency_label' => 'EUR',
        ];
    }
}
