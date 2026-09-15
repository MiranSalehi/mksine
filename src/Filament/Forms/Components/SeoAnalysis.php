<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Miran\Mksine\Seo\SeoAnalyzer;
use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoResult;

/**
 * Advisory Yoast-like SEO panel. Never dehydrates; never fails validation.
 *
 * SeoAnalysis::make('seo_analysis')
 *     ->titleField('name')
 *     ->slugField('slug')
 *     ->metaTitleField('meta_title')
 *     ->metaDescriptionField('meta_description')
 *     ->bodyField('description')
 *     ->focusKeyphraseField('focus_keyphrase');
 */
class SeoAnalysis extends Field
{
    protected string $view = 'mksine::filament.forms.components.seo-analysis';

    protected string | Closure $titleField = 'title';

    protected string | Closure $slugField = 'slug';

    protected string | Closure $metaTitleField = 'meta_title';

    protected string | Closure $metaDescriptionField = 'meta_description';

    protected string | Closure $bodyField = 'content';

    protected string | Closure $focusKeyphraseField = 'focus_keyphrase';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->hiddenLabel();
    }

    public function titleField(string | Closure $field): static
    {
        $this->titleField = $field;

        return $this;
    }

    public function slugField(string | Closure $field): static
    {
        $this->slugField = $field;

        return $this;
    }

    public function metaTitleField(string | Closure $field): static
    {
        $this->metaTitleField = $field;

        return $this;
    }

    public function metaDescriptionField(string | Closure $field): static
    {
        $this->metaDescriptionField = $field;

        return $this;
    }

    public function bodyField(string | Closure $field): static
    {
        $this->bodyField = $field;

        return $this;
    }

    public function focusKeyphraseField(string | Closure $field): static
    {
        $this->focusKeyphraseField = $field;

        return $this;
    }

    public function getTitleField(): string
    {
        return (string) $this->evaluate($this->titleField);
    }

    public function getSlugField(): string
    {
        return (string) $this->evaluate($this->slugField);
    }

    public function getMetaTitleField(): string
    {
        return (string) $this->evaluate($this->metaTitleField);
    }

    public function getMetaDescriptionField(): string
    {
        return (string) $this->evaluate($this->metaDescriptionField);
    }

    public function getBodyField(): string
    {
        return (string) $this->evaluate($this->bodyField);
    }

    public function getFocusKeyphraseField(): string
    {
        return (string) $this->evaluate($this->focusKeyphraseField);
    }

    public function getSeoResult(): SeoResult
    {
        return $this->evaluate(function (Get $get): SeoResult {
            return SeoAnalyzer::analyze($this->contextFromGet($get));
        });
    }

    protected function contextFromGet(Get $get): SeoContext
    {
        return new SeoContext(
            title: (string) ($get($this->getTitleField()) ?? ''),
            slug: (string) ($get($this->getSlugField()) ?? ''),
            metaTitle: (string) ($get($this->getMetaTitleField()) ?? ''),
            metaDescription: (string) ($get($this->getMetaDescriptionField()) ?? ''),
            html: (string) ($get($this->getBodyField()) ?? ''),
            locale: (string) app()->getLocale(),
            focusKeyphrase: (string) ($get($this->getFocusKeyphraseField()) ?? ''),
        );
    }
}
