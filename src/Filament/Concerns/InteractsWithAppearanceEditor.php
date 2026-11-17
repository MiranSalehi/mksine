<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Concerns;

use Filament\Support\Colors\Color;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearancePresets;
use Miran\Mksine\Core\Appearance\AppearanceRenderer;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Core\Appearance\ColorScale;

/**
 * Shared state and actions of the look editor (Settings → Appearance and the "My appearance" window).
 *
 * Every change goes through {@see Appearance::fromArray()} (allow-lists) and is sent to the browser as
 * a preview, which the editor's Alpine component applies to this page only and undoes on leave.
 * Persisting is the host's job ({@see persistAppearance()}).
 */
trait InteractsWithAppearanceEditor
{
    /** @var array<string, string|null> */
    public array $look = [];

    /** @var array<string, string|null> The stored state, to tell unsaved changes apart. */
    public array $storedLook = [];

    public string $primaryInput = '';

    public bool $primaryInputInvalid = false;

    /**
     * Base the edited layer falls back to: nothing for the company look, the company look for a person.
     */
    abstract protected function appearanceBase(): Appearance;

    abstract protected function persistAppearance(Appearance $appearance): void;

    abstract public function isPersonalEditor(): bool;

    protected function fillAppearanceEditor(Appearance $appearance): void
    {
        $this->look = $appearance->toState();
        $this->storedLook = $this->look;
        $this->syncPrimaryInput();
    }

    public function editedAppearance(): Appearance
    {
        return Appearance::fromArray($this->look);
    }

    /**
     * What the page shows while editing: the edited layer over its base.
     */
    public function effectiveAppearance(): Appearance
    {
        return $this->editedAppearance()->over($this->appearanceBase());
    }

    public function setPart(string $part, ?string $value = null): void
    {
        if (! in_array($part, Appearance::PARTS, true) || $part === 'preset') {
            return;
        }

        $state = $this->editedAppearance()->toArray();
        unset($state[$part]);

        if ($value !== null && $value !== '') {
            $state[$part] = $value;
        }

        $this->applyLook(Appearance::fromArray($state));
    }

    public function setPrimary(?string $hex = null): void
    {
        $this->setPart('primary', $hex === null ? null : ColorScale::normalizeHex($hex));
    }

    public function updatedPrimaryInput(): void
    {
        $value = trim($this->primaryInput);

        if ($value === '') {
            $this->primaryInputInvalid = false;
            $this->setPart('primary');

            return;
        }

        $hex = ColorScale::normalizeHex(str_starts_with($value, '#') ? $value : '#' . $value);
        $this->primaryInputInvalid = $hex === null;

        if ($hex !== null) {
            $this->setPart('primary', $hex);
        }
    }

    public function choosePreset(string $key): void
    {
        if (! array_key_exists($key, AppearancePresets::PRESETS)) {
            return;
        }

        $this->applyLook(AppearancePresets::apply($this->editedAppearance(), AppearancePresets::preset($key), $key));
    }

    public function chooseSaved(string $name): void
    {
        $saved = app(AppearanceRepository::class)->findSaved($name);

        if ($saved === null) {
            return;
        }

        // A saved look is a whole look: it replaces every part.
        $this->applyLook($saved);
    }

    /**
     * Back to the last stored state.
     */
    public function revertAppearance(): void
    {
        $this->applyLook(Appearance::fromArray($this->storedLook));
    }

    public function hasUnsavedAppearance(): bool
    {
        return Appearance::fromArray($this->look)->toArray() !== Appearance::fromArray($this->storedLook)->toArray();
    }

    public function saveAppearance(): void
    {
        $this->persistAppearance($this->editedAppearance());

        // Reload so the saved look comes from the server (head variables, Filament palettes).
        $this->js('window.location.reload()');
    }

    /**
     * @return array{vars: array<string, string>, attrs: array<string, string>, mode: string|null, adjusted: bool, nearDanger: bool, used: string|null}
     */
    public function previewState(): array
    {
        return app(AppearanceRenderer::class)->previewState($this->effectiveAppearance());
    }

    /**
     * Cards for the ready looks and the company's saved looks.
     *
     * @return list<array{key: string, label: string, saved: bool, name: string|null, primary: string, brand: string, rail: string|null, radius: string|null, active: bool}>
     */
    public function appearanceCards(): array
    {
        $current = $this->look['preset'] ?? null;
        $cards = [];

        foreach (array_keys(AppearancePresets::PRESETS) as $key) {
            $cards[] = $this->card($key, __('mksine::appearance.presets.' . $key), AppearancePresets::preset($key), false, null, $current === $key);
        }

        foreach (app(AppearanceRepository::class)->saved() as $row) {
            $key = AppearancePresets::SAVED_PREFIX . $row['name'];
            $cards[] = $this->card($key, $row['name'], $row['appearance'], true, $row['name'], $current === $key);
        }

        return $cards;
    }

    /**
     * @return array<string, string>
     */
    public function readyColors(): array
    {
        return AppearancePresets::COLORS;
    }

    public function defaultPrimaryHex(): string
    {
        return Color::convertToHex(Color::Blue[600]);
    }

    /**
     * @return array{key: string, label: string, saved: bool, name: string|null, primary: string, brand: string, rail: string|null, radius: string|null, active: bool}
     */
    private function card(string $key, string $label, Appearance $look, bool $saved, ?string $name, bool $active): array
    {
        $palette = $look->primary !== null ? ColorScale::palette($look->primary)->shades : null;

        return [
            'key' => $key,
            'label' => $label,
            'saved' => $saved,
            'name' => $name,
            'primary' => $palette[600] ?? $this->defaultPrimaryHex(),
            'brand' => $palette[700] ?? Color::convertToHex(Color::Blue[700]),
            'rail' => $look->rail?->value,
            'radius' => $look->radius?->value,
            'active' => $active,
        ];
    }

    private function applyLook(Appearance $appearance): void
    {
        $this->look = $appearance->toState();
        $this->syncPrimaryInput();
        $this->dispatch('mks-appearance-preview', editor: $this->getId(), state: $this->previewState());
    }

    private function syncPrimaryInput(): void
    {
        $this->primaryInput = (string) ($this->look['primary'] ?? '');
        $this->primaryInputInvalid = false;
    }
}
