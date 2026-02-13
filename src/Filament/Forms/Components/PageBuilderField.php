<?php

namespace Miran\Mksine\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class PageBuilderField extends Field
{
    protected string $view = 'mksine::filament.forms.page-builder-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (PageBuilderField $component, $state) {
            if (is_string($state)) {
                $component->state(json_decode($state, true) ?? []);
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            return is_array($state) ? $state : [];
        });
    }
}
