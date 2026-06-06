<?php

namespace Miran\Mksine\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

class CKEditor extends Field
{
    protected string $view = 'mksine::filament.forms.components.ckeditor';

    protected string|Closure $content = '';

    protected string $name = 'ckeditor';

    protected int $minLength = 0;

    protected string|Closure|null $uploadUrl = null;

    protected bool $uploadUrlExplicitlySet = false;

    protected string $placeholder = 'Type or paste your content here...';

    protected string|Closure $height = '300px';

    /**
     * ISO 639-1 primary language code for CKEditor UI (toolbar labels, dialogs). Null = use config or app locale.
     */
    protected string|Closure|null $editorUiLanguage = null;

    /**
     * ISO 639-1 primary language for editable content (text direction, BiDi). Null = use config or same as UI.
     * Set explicitly to {@code fa} when the panel is English but authors write Persian.
     */
    protected string|Closure|null $editorContentLanguage = null;

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

    public function content(string|Closure $content): self
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

    /**
     * @param  string | Closure | null  $language  Two-letter ISO 639-1 code (e.g. {@code fa}) or null to auto-detect from config / app locale.
     */
    public function editorUiLanguage(string|Closure|null $language): static
    {
        $this->editorUiLanguage = $language;

        return $this;
    }

    /**
     * @param  string | Closure | null  $language  Two-letter ISO 639-1 code for the editing surface (RTL for Persian, Arabic, …).
     */
    public function editorContentLanguage(string|Closure|null $language): static
    {
        $this->editorContentLanguage = $language;

        return $this;
    }

    public function height(string|Closure $height): static
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

    /**
     * Two-letter language code for CKEditor {@code language.ui} (defaults from config then app locale).
     */
    public function getEditorUiLanguage(): string
    {
        $explicit = $this->evaluate($this->editorUiLanguage);
        if (is_string($explicit) && trim($explicit) !== '') {
            return self::normalizeLanguageCode($explicit);
        }

        $fromConfig = config('mksine.ckeditor.ui_language');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return self::normalizeLanguageCode($fromConfig);
        }

        return self::normalizeLanguageCode(self::resolveApplicationLocaleString());
    }

    /**
     * Two-letter language code for CKEditor {@code language.content} (BiDi / caret / lists).
     */
    public function getEditorContentLanguage(): string
    {
        $explicit = $this->evaluate($this->editorContentLanguage);
        if (is_string($explicit) && trim($explicit) !== '') {
            return self::normalizeLanguageCode($explicit);
        }

        $fromConfig = config('mksine.ckeditor.content_language');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return self::normalizeLanguageCode($fromConfig);
        }

        return $this->getEditorUiLanguage();
    }

    public function isEditorContentRtl(): bool
    {
        return self::isRtlLanguageCode($this->getEditorContentLanguage());
    }

    public function getUploadUrl(): ?string
    {
        $url = $this->evaluate($this->uploadUrl);

        return is_string($url) ? $url : null;
    }

    private static function resolveApplicationLocaleString(): string
    {
        return (string) app()->getLocale();
    }

    private static function normalizeLanguageCode(string $raw): string
    {
        $trimmed = strtolower(trim(str_replace('_', '-', $raw)));
        if ($trimmed === '') {
            return 'en';
        }

        $primary = strtolower((string) Str::before(Str::before($trimmed, '@'), '-'));

        return strlen($primary) >= 2 ? substr($primary, 0, 2) : 'en';
    }

    /**
     * Aligns with CKEditor’s {@code getLanguageDirection} RTL list (ISO 639-1 subset used in admin).
     */
    private static function isRtlLanguageCode(string $twoLetter): bool
    {
        return in_array(strtolower($twoLetter), [
            'ar', 'dv', 'fa', 'ha', 'he', 'ku', 'ps', 'ug', 'ur', 'yi',
        ], true);
    }
}
