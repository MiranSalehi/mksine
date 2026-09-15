<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Seo\Checks\ConsecutiveLongSentencesCheck;
use Miran\Mksine\Seo\Checks\ContentWordCountCheck;
use Miran\Mksine\Seo\Checks\FleschReadingEaseCheck;
use Miran\Mksine\Seo\Checks\HeadingPresenceCheck;
use Miran\Mksine\Seo\Checks\InternalOutboundLinksCheck;
use Miran\Mksine\Seo\Checks\KeyphraseInIntroCheck;
use Miran\Mksine\Seo\Checks\KeyphraseInMetaDescriptionCheck;
use Miran\Mksine\Seo\Checks\KeyphraseInMetaTitleCheck;
use Miran\Mksine\Seo\Checks\KeyphraseInSlugCheck;
use Miran\Mksine\Seo\Checks\KeyphraseInTitleCheck;
use Miran\Mksine\Seo\Checks\MetaDescriptionLengthCheck;
use Miran\Mksine\Seo\Checks\MetaTitleLengthCheck;
use Miran\Mksine\Seo\Checks\ParagraphLengthCheck;

/**
 * Advisory SEO / readability scorer. Never blocks save and never rewrites copy.
 *
 * Embed the Filament field on another resource (ecom products, etc.):
 *
 * SeoAnalysis::make('seo_analysis')
 *     ->titleField('name')
 *     ->slugField('slug')
 *     ->metaTitleField('meta_title')
 *     ->metaDescriptionField('meta_description')
 *     ->bodyField('description')
 *     ->focusKeyphraseField('focus_keyphrase');
 *
 * Plugins append checks with:
 *
 * Hooks::addFilter(SeoAnalyzer::FILTER_CHECKS, function (array $checks, SeoContext $context): array {
 *     $checks[] = new ProductSearchBoostCheck;
 *
 *     return $checks;
 * });
 */
final class SeoAnalyzer
{
    public const FILTER_CHECKS = 'mksine.seo.analysis_checks';

    public static function analyze(SeoContext $context): SeoResult
    {
        $checks = self::defaultChecks($context);
        $filtered = Hooks::filter(self::FILTER_CHECKS, $checks, $context);
        $checks = is_array($filtered) ? array_values($filtered) : $checks;

        $seo = [];
        $readability = [];
        $scores = [];

        foreach ($checks as $check) {
            if (! $check instanceof SeoCheck) {
                continue;
            }

            $finding = $check->analyze($context);
            $scores[] = $finding->score;

            if ($finding->panel === 'readability') {
                $readability[] = $finding;
            } else {
                $seo[] = $finding;
            }
        }

        $overall = $scores === [] ? 0 : (int) round(array_sum($scores) / count($scores));
        $plain = SeoText::stripHtml($context->html);

        if ($plain === '') {
            $overall = min($overall, 40);
        }

        $trafficLight = match (true) {
            $overall >= 80 => 'good',
            $overall >= 50 => 'ok',
            default => 'bad',
        };

        if ($plain === '') {
            $trafficLight = 'bad';
        }

        $snippetTitle = trim($context->metaTitle) !== '' ? $context->metaTitle : $context->title;
        $snippetDescription = trim($context->metaDescription);

        if (mb_strlen($snippetDescription) > 160) {
            $snippetDescription = rtrim(mb_substr($snippetDescription, 0, 157)).'…';
        }

        return new SeoResult(
            overall: $overall,
            trafficLight: $trafficLight,
            seo: $seo,
            readability: $readability,
            snippet: [
                'title' => $snippetTitle,
                'url' => ltrim($context->slug, '/'),
                'description' => $snippetDescription,
            ],
        );
    }

    /**
     * @return list<SeoCheck>
     */
    public static function defaultChecks(SeoContext $context): array
    {
        $checks = [
            new KeyphraseInTitleCheck,
            new KeyphraseInMetaTitleCheck,
            new KeyphraseInMetaDescriptionCheck,
            new KeyphraseInSlugCheck,
            new KeyphraseInIntroCheck,
            new MetaTitleLengthCheck,
            new MetaDescriptionLengthCheck,
            new ContentWordCountCheck,
            new InternalOutboundLinksCheck,
            new HeadingPresenceCheck,
            new ParagraphLengthCheck,
            new ConsecutiveLongSentencesCheck,
        ];

        if ($context->isEnglish()) {
            $checks[] = new FleschReadingEaseCheck;
        }

        return $checks;
    }
}
