<?php

namespace Miran\Mksine\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class CKEditor extends Field
{
    protected string $view = 'mksine::filament.forms.components.ckeditor';

    protected string | Closure $content = '';

    protected string $name = 'ckeditor';

    protected int $minLength = 0;

    protected string | Closure | null $uploadUrl = null;

    protected bool $uploadUrlExplicitlySet = false;

    protected string $placeholder = 'Type or paste your content here...';

    protected string | Closure $height = '300px';

    public static function make(?string $name = null): static
    {
        $field = app(static::class, [
            'name' => $name ?? 'ckeditor',
        ]);

        return $field;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Don't use dehydrated(false) - state should be sent to server
        // If you need custom dehydration logic, use dehydrateStateUsing() instead
    }

    public function content(string | Closure $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getContent(): string
    {
        $result = $this->evaluate($this->content);

        return is_string($result) ? $result : '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function height(string | Closure $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight(): string
    {
        $height = $this->evaluate($this->height);

        return (is_string($height) && preg_match('/^\d+(\.\d+)?(px|rem|em)$/', $height))
            ? $height
            : '300px';
    }

    public function getUploadUrl(): ?string
    {
        $url = $this->evaluate($this->uploadUrl);

        return is_string($url) ? $url : null;
    }
}
