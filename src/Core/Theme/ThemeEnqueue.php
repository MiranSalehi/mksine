<?php

namespace Miran\Mksine\Core\Theme;

/**
 * Request-scoped queue for CSS/JS assets (WordPress-style enqueue).
 * Enqueued assets are output when @themeAssets is rendered; no Blade edit needed.
 */
class ThemeEnqueue
{
    /** @var array<int, array{url: string, attributes: array<string, string>}> */
    protected array $styles = [];

    /** @var array<int, array{url: string, attributes: array<string, string>}> */
    protected array $scripts = [];

    /**
     * Enqueue a stylesheet. URL can be full (https://...) or relative to theme (resolved when rendered).
     *
     * @param  array<string, string>  $attributes  e.g. ['media' => 'print', 'id' => 'my-css']
     */
    public function enqueueStyle(string $url, array $attributes = []): self
    {
        $this->styles[] = [
            'url' => trim($url),
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Enqueue a script. URL can be full or relative to theme.
     *
     * @param  array<string, string>  $attributes  e.g. ['defer' => 'defer', 'async' => 'async']
     */
    public function enqueueScript(string $url, array $attributes = []): self
    {
        $this->scripts[] = [
            'url' => trim($url),
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * @return array<int, array{url: string, attributes: array<string, string>}>
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @return array<int, array{url: string, attributes: array<string, string>}>
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }
}
