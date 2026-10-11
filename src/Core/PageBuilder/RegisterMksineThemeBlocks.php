<?php

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Core\PageBuilder\Components\MksineClinicFeaturesGridComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFeaturedDomainsComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineHeroDomainComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksinePostCommentsFeedComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineServicesTrioComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineTestimonialsGridComponent;
use Miran\Mksine\Core\PageBuilder\Templates\MksineDefaultHomeTemplate;

/**
 * Landing blocks and the default-home template belong to the bundled MKSine theme.
 * Call this from that theme's theme.php. The core registry does not register them.
 */
final class RegisterMksineThemeBlocks
{
    public static function register(): void
    {
        app(ComponentRegistry::class)->registerMany([
            MksineFinanceShowcaseComponent::class,
            MksineHeroDomainComponent::class,
            MksineServicesTrioComponent::class,
            MksineFeaturedDomainsComponent::class,
            MksineClinicFeaturesGridComponent::class,
            MksineTestimonialsGridComponent::class,
            MksinePostCommentsFeedComponent::class,
        ]);

        app(TemplateRegistry::class)->register(
            'mksine-default-home',
            MksineDefaultHomeTemplate::config(),
        );
    }
}
