<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Models\PageBuilderSection;

class SectionRefComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'section_ref';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.section_ref_name');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.section_ref_description');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('section_id')
                ->label(__('mksine::page_builder.section_ref_name'))
                ->options(fn (): array => PageBuilderSection::query()->orderBy('name')->pluck('name', 'id')->all())
                ->required()
                ->native(false)
                ->searchable(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'section_id' => null,
        ];
    }

    public static function validate(array $data): array
    {
        $sectionId = (int) ($data['section_id'] ?? 0);

        return [
            'section_id' => $sectionId > 0 ? $sectionId : 0,
        ];
    }
}
