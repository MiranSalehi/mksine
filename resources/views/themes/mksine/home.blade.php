<div>
    @themeDoAction('home.before_hero')

    @themeDoAction('home.before_finance_showcase')
    @include('mksine::themes.mksine.partials.home.finance-showcase')
    @themeDoAction('home.after_finance_showcase')

    @include('mksine::themes.mksine.partials.home.hero-domain')

    @themeDoAction('home.after_hero')

    @themeDoAction('home.before_services')
    @include('mksine::themes.mksine.partials.home.services-trio')
    @themeDoAction('home.after_services')

    @themeDoAction('home.before_featured_domains')
    @include('mksine::themes.mksine.partials.home.featured-domains')
    @themeDoAction('home.after_featured_domains')

    @themeDoAction('home.before_clinic_features')
    @include('mksine::themes.mksine.partials.home.clinic-features-grid')
    @themeDoAction('home.after_clinic_features')

    @themeDoAction('home.before_testimonials')
    @include('mksine::themes.mksine.partials.home.testimonials-grid')
    @themeDoAction('home.after_testimonials')

    @themeDoAction('home.before_pricing')
    @include('mksine::themes.mksine.partials.home.pricing-plans')
    @themeDoAction('home.after_pricing')

    @themeDoAction('home.before_section_latest')
    @themeDoAction('home.after_section_latest')

    @themeDoAction('home.before_section_categories')
    @themeDoAction('home.after_section_categories')
</div>