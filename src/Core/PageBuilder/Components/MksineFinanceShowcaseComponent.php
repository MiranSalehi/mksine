<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Core\PageBuilder\BuilderUrl;

class MksineFinanceShowcaseComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_finance_showcase';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_finance_showcase');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_finance_showcase');
    }

    public static function getSchema(): array
    {
        return [
            Section::make(__('mksine::page_builder.mksine_fields.fin_group_copy'))
                ->schema([
                    TextInput::make('heading')
                        ->label(__('mksine::page_builder.mksine_fields.fin_heading'))
                        ->maxLength(400)
                        ->columnSpanFull(),
                    TextInput::make('heading_accent')
                        ->label(__('mksine::page_builder.mksine_fields.fin_heading_accent'))
                        ->helperText(__('mksine::page_builder.mksine_fields.fin_emphasis_help'))
                        ->maxLength(200)
                        ->columnSpanFull(),
                    Textarea::make('paragraph_1')
                        ->label(__('mksine::page_builder.mksine_fields.fin_paragraph_1'))
                        ->rows(2)
                        ->columnSpanFull(),
                    TextInput::make('paragraph_1_highlight')
                        ->label(__('mksine::page_builder.mksine_fields.fin_paragraph_1_highlight'))
                        ->helperText(__('mksine::page_builder.mksine_fields.fin_emphasis_help'))
                        ->maxLength(200)
                        ->columnSpanFull(),
                    Textarea::make('paragraph_2')
                        ->label(__('mksine::page_builder.mksine_fields.fin_paragraph_2'))
                        ->rows(2)
                        ->columnSpanFull(),
                    TextInput::make('paragraph_2_highlight')
                        ->label(__('mksine::page_builder.mksine_fields.fin_paragraph_2_highlight'))
                        ->helperText(__('mksine::page_builder.mksine_fields.fin_emphasis_help'))
                        ->maxLength(200)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('mksine::page_builder.mksine_fields.fin_group_actions'))
                ->schema([
                    TextInput::make('cta_primary_label')
                        ->label(__('mksine::page_builder.mksine_fields.fin_cta_primary_label'))
                        ->maxLength(120),
                    TextInput::make('cta_primary_url')
                        ->label(__('mksine::page_builder.mksine_fields.fin_cta_primary_url'))
                        ->maxLength(2048),
                    TextInput::make('cta_secondary_label')
                        ->label(__('mksine::page_builder.mksine_fields.fin_cta_secondary_label'))
                        ->maxLength(120),
                    TextInput::make('cta_secondary_url')
                        ->label(__('mksine::page_builder.mksine_fields.fin_cta_secondary_url'))
                        ->maxLength(2048),
                    Textarea::make('trust_line')
                        ->label(__('mksine::page_builder.mksine_fields.fin_trust_line'))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('mksine::page_builder.mksine_fields.fin_group_proof'))
                ->schema([
                    TextInput::make('social_caption')
                        ->label(__('mksine::page_builder.mksine_fields.fin_social_caption'))
                        ->columnSpanFull(),
                    TextInput::make('social_count')
                        ->label(__('mksine::page_builder.mksine_fields.fin_social_stat'))
                        ->maxLength(120),
                    TextInput::make('mock_chart_caption')
                        ->label(__('mksine::page_builder.mksine_fields.fin_mock_chart_caption'))
                        ->maxLength(120),
                    Repeater::make('avatars')
                        ->label(__('mksine::page_builder.mksine_fields.fin_avatar_stack'))
                        ->schema([
                            TextInput::make('letter')
                                ->label(__('mksine::page_builder.mksine_fields.fin_letter'))
                                ->maxLength(2)
                                ->required(),
                        ])
                        ->defaultItems(5)
                        ->reorderable()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading' => 'Run your clinic finances without the chaos.',
            'heading_accent' => 'without the chaos.',
            'paragraph_1' => 'Invoices, payouts, and taxes in one clear dashboard — built for busy teams.',
            'paragraph_1_highlight' => 'clear dashboard',
            'paragraph_2' => 'Automate reminders and get paid faster with smart follow-ups your patients actually notice.',
            'paragraph_2_highlight' => 'smart follow-ups',
            'social_caption' => 'Trusted by modern practices worldwide',
            'social_count' => '12,400+ teams',
            'trust_line' => 'No credit card required to explore the demo.',
            'cta_primary_label' => 'Start free trial',
            'cta_primary_url' => '#',
            'cta_secondary_label' => 'Talk to sales',
            'cta_secondary_url' => '#',
            'mock_chart_caption' => 'Monthly revenue',
            'avatars' => [
                ['letter' => 'A'],
                ['letter' => 'B'],
                ['letter' => 'C'],
                ['letter' => 'D'],
                ['letter' => 'E'],
            ],
        ];
    }

    public static function validate(array $data): array
    {
        if (! self::usesAuthorShape($data)) {
            return $data;
        }

        $heading = self::text($data['heading'] ?? '');
        $paragraph1 = self::text($data['paragraph_1'] ?? '');
        $paragraph2 = self::text($data['paragraph_2'] ?? '');

        $avatars = [];
        foreach (is_array($data['avatars'] ?? null) ? $data['avatars'] : [] as $avatar) {
            if (! is_array($avatar)) {
                continue;
            }

            $letter = mb_substr(self::text($avatar['letter'] ?? ''), 0, 2);
            if ($letter === '') {
                continue;
            }

            $avatars[] = ['letter' => $letter];
        }

        return [
            'heading' => $heading,
            'heading_accent' => self::emphasis($heading, $data['heading_accent'] ?? ''),
            'paragraph_1' => $paragraph1,
            'paragraph_1_highlight' => self::emphasis($paragraph1, $data['paragraph_1_highlight'] ?? ''),
            'paragraph_2' => $paragraph2,
            'paragraph_2_highlight' => self::emphasis($paragraph2, $data['paragraph_2_highlight'] ?? ''),
            'social_caption' => self::text($data['social_caption'] ?? ''),
            'social_count' => self::text($data['social_count'] ?? ''),
            'trust_line' => self::text($data['trust_line'] ?? ''),
            'cta_primary_label' => self::text($data['cta_primary_label'] ?? ''),
            'cta_primary_url' => BuilderUrl::href($data['cta_primary_url'] ?? ''),
            'cta_secondary_label' => self::text($data['cta_secondary_label'] ?? ''),
            'cta_secondary_url' => BuilderUrl::href($data['cta_secondary_url'] ?? ''),
            'mock_chart_caption' => self::text($data['mock_chart_caption'] ?? ''),
            'avatars' => $avatars,
        ];
    }

    /**
     * Author-facing fields. Legacy fragments are joined for the form and the renderer, and are not written back.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function forEditor(array $data): array
    {
        if (self::usesAuthorShape($data)) {
            $author = $data;
        } else {
            $author = [
                'heading' => self::join($data['heading_prefix'] ?? '', $data['heading_accent'] ?? '', $data['heading_suffix'] ?? ''),
                'heading_accent' => self::text($data['heading_accent'] ?? '', trim: false),
                'paragraph_1' => self::join($data['p1_before'] ?? '', $data['p1_highlight'] ?? '', $data['p1_after'] ?? ''),
                'paragraph_1_highlight' => self::text($data['p1_highlight'] ?? '', trim: false),
                'paragraph_2' => self::join($data['p2_before'] ?? '', $data['p2_highlight'] ?? '', $data['p2_after'] ?? ''),
                'paragraph_2_highlight' => self::text($data['p2_highlight'] ?? '', trim: false),
                'social_caption' => $data['social_caption'] ?? '',
                'social_count' => $data['social_count'] ?? '',
                'trust_line' => $data['trust_line'] ?? '',
                'cta_primary_label' => $data['cta_primary_label'] ?? '',
                'cta_primary_url' => $data['cta_primary_url'] ?? '',
                'cta_secondary_label' => $data['cta_secondary_label'] ?? '',
                'cta_secondary_url' => $data['cta_secondary_url'] ?? '',
                'mock_chart_caption' => $data['mock_chart_caption'] ?? '',
                'avatars' => $data['avatars'] ?? [],
            ];
        }

        $validated = self::validate($author);
        $validated['heading_accent'] = self::text($author['heading_accent'] ?? '', trim: false);
        $validated['paragraph_1_highlight'] = self::text($author['paragraph_1_highlight'] ?? '', trim: false);
        $validated['paragraph_2_highlight'] = self::text($author['paragraph_2_highlight'] ?? '', trim: false);

        return $validated;
    }

    /**
     * @return array{before: string, emphasis: string, after: string}
     */
    public static function splitEmphasis(string $text, string $phrase): array
    {
        $phrase = self::text($phrase, trim: false);
        if ($phrase === '' || mb_strpos($text, $phrase) === false) {
            return ['before' => $text, 'emphasis' => '', 'after' => ''];
        }

        $position = (int) mb_strpos($text, $phrase);

        return [
            'before' => mb_substr($text, 0, $position),
            'emphasis' => $phrase,
            'after' => mb_substr($text, $position + mb_strlen($phrase)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function usesAuthorShape(array $data): bool
    {
        return array_key_exists('heading', $data) || array_key_exists('paragraph_1', $data);
    }

    private static function emphasis(string $text, mixed $phrase): string
    {
        $phrase = self::text($phrase, trim: false);

        return ($phrase !== '' && mb_strpos($text, $phrase) !== false) ? $phrase : '';
    }

    private static function text(mixed $value, bool $trim = true): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = strip_tags($value);

        return $trim ? trim($value) : $value;
    }

    private static function join(mixed ...$parts): string
    {
        $joined = '';
        foreach ($parts as $part) {
            $joined .= self::text($part, trim: false);
        }

        return $joined;
    }
}
