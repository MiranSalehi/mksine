<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Models\Post;

class MksinePostCommentsFeedComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'mksine_post_comments_feed';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_mksine_post_comments_feed');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_mksine_post_comments_feed');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('post_id')
                ->label(__('mksine::page_builder.mksine_fields.comments_source_post'))
                ->helperText(__('mksine::page_builder.mksine_fields.comments_source_post_help'))
                ->placeholder(__('mksine::page_builder.mksine_fields.comments_source_post_placeholder'))
                ->options(fn (): array => Post::query()
                    ->where('status', 'published')
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->native(false),
            TextInput::make('max_root_comments')
                ->label(__('mksine::page_builder.mksine_fields.comments_max_root'))
                ->numeric()
                ->minValue(1)
                ->maxValue(50)
                ->default(12)
                ->required(),
            Select::make('text_direction')
                ->label(__('mksine::page_builder.mksine_fields.text_direction'))
                ->options([
                    'auto' => __('mksine::page_builder.mksine_fields.text_direction_auto'),
                    'ltr' => __('mksine::page_builder.mksine_fields.direction_ltr'),
                    'rtl' => __('mksine::page_builder.mksine_fields.direction_rtl'),
                ])
                ->default('auto')
                ->native(false),
            TextInput::make('badge')
                ->label(__('mksine::page_builder.mksine_fields.field_badge'))
                ->maxLength(120)
                ->columnSpanFull(),
            TextInput::make('title_prefix')
                ->label(__('mksine::page_builder.mksine_fields.comments_title_prefix'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('title_accent')
                ->label(__('mksine::page_builder.mksine_fields.comments_title_accent'))
                ->maxLength(120)
                ->columnSpanFull(),
            TextInput::make('subheading')
                ->label(__('mksine::page_builder.mksine_fields.comments_subheading'))
                ->maxLength(500)
                ->columnSpanFull(),
            Textarea::make('aside')
                ->label(__('mksine::page_builder.mksine_fields.comments_aside'))
                ->helperText(__('mksine::page_builder.mksine_fields.comments_aside_help'))
                ->rows(2)
                ->maxLength(400)
                ->columnSpanFull(),
            TextInput::make('quote_max_chars')
                ->label(__('mksine::page_builder.mksine_fields.comments_quote_max_chars'))
                ->helperText(__('mksine::page_builder.mksine_fields.comments_quote_max_chars_help'))
                ->numeric()
                ->minValue(80)
                ->maxValue(500)
                ->default(220)
                ->required(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'post_id' => null,
            'max_root_comments' => 12,
            'text_direction' => 'auto',
            'badge' => 'From the community',
            'title_prefix' => 'What readers ',
            'title_accent' => 'are saying',
            'subheading' => 'Approved comments from your blog. Pick a post to focus on one article, or leave empty for the latest across all posts.',
            'aside' => 'Average response time under 2 hours — we are here when you need us.',
            'quote_max_chars' => 220,
        ];
    }
}
