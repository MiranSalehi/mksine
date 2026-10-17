<?php

declare(strict_types=1);

it('defines mksine_fields labels for en, fa, and ku page builder locales', function (): void {
    foreach (['en', 'fa', 'ku'] as $locale) {
        app()->setLocale($locale);
        $plans = __('mksine::page_builder.mksine_fields.pr_plans');
        $hero = __('mksine::page_builder.mksine_fields.hero_heading_line1');

        expect($plans)->not->toBe('mksine::page_builder.mksine_fields.pr_plans')
            ->and($hero)->not->toBe('mksine::page_builder.mksine_fields.hero_heading_line1')
            ->and($plans)->not->toBeEmpty()
            ->and($hero)->not->toBeEmpty();
    }
});
