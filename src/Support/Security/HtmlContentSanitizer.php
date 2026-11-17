<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Security;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Strips script from rich content written by anyone but a Super Admin (stored XSS guard).
 *
 * Content is cleaned when it is saved, not when it is shown: Super Admins keep raw HTML
 * (embed scripts, custom markup from source editing), rendering costs nothing extra, and every
 * write path (Filament forms, the page builder, imports in a web request) goes through the
 * model's `saving` event. Formatting CKEditor produces (headings, tables, figures, classes,
 * inline styles) survives; `<script>`, event handlers, `javascript:` URLs and iframes from
 * hosts outside `mksine.content.embed_hosts` do not.
 */
final class HtmlContentSanitizer
{
    /**
     * Page builder block types whose fields hold rich HTML: type => list of dotted paths,
     * where `*` walks a list.
     *
     * @var array<string, list<string>>
     */
    public const BUILDER_HTML_FIELDS = [
        'text' => ['content'],
        'tabs' => ['tabs.*.content'],
        'accordion' => ['items.*.answer'],
    ];

    /**
     * Video and map hosts whose https iframes are kept when `mksine.content.embed_hosts` is not
     * set (published configs from before 1.24.0 lack the key).
     *
     * @var list<string>
     */
    public const DEFAULT_EMBED_HOSTS = [
        'www.aparat.com',
        'aparat.com',
        'www.youtube.com',
        'youtube.com',
        'www.youtube-nocookie.com',
        'player.vimeo.com',
        'www.google.com',
        'maps.google.com',
        'balad.ir',
        'neshan.org',
    ];

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * Whether content saved in the current context must be cleaned.
     *
     * Console runs without a signed-in user (migrations, imports run by the owner, tests) are
     * trusted; any web request is cleaned unless a Super Admin made it.
     */
    public function shouldSanitize(): bool
    {
        if (! (bool) config('mksine.content.sanitize_html', true)) {
            return false;
        }

        $user = auth()->user();

        if ($user === null) {
            return ! app()->runningInConsole();
        }

        return ! SuperAdminGate::check($user);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return $this->sanitizer()->sanitize($html);
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function sanitizeBuilderPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizeBuilderPayload($value);
            }
        }

        $type = $payload['type'] ?? null;

        if (is_string($type) && isset(self::BUILDER_HTML_FIELDS[$type]) && is_array($payload['data'] ?? null)) {
            foreach (self::BUILDER_HTML_FIELDS[$type] as $path) {
                $payload['data'] = $this->sanitizePath($payload['data'], explode('.', $path));
            }
        }

        return $payload;
    }

    /**
     * Clean the model's dirty rich-text attributes when the current user may not write raw HTML.
     *
     * @param  list<string>  $htmlAttributes
     * @param  list<string>  $builderPayloadAttributes
     */
    public function sanitizeModel(Model $model, array $htmlAttributes, array $builderPayloadAttributes = []): void
    {
        if (! $this->shouldSanitize()) {
            return;
        }

        foreach ($htmlAttributes as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $model->isDirty($attribute)) {
                $model->setAttribute($attribute, $this->sanitize($value));
            }
        }

        foreach ($builderPayloadAttributes as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_array($value) && $model->isDirty($attribute)) {
                $model->setAttribute($attribute, $this->sanitizeBuilderPayload($value));
            }
        }
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  list<string>  $segments
     * @return array<int|string, mixed>
     */
    private function sanitizePath(array $data, array $segments): array
    {
        $segment = array_shift($segments);

        if ($segment === '*') {
            foreach ($data as $index => $item) {
                if (is_array($item)) {
                    $data[$index] = $this->sanitizePath($item, $segments);
                }
            }

            return $data;
        }

        if ($segment === null || ! array_key_exists($segment, $data)) {
            return $data;
        }

        if ($segments === []) {
            if (is_string($data[$segment])) {
                $data[$segment] = $this->sanitize($data[$segment]);
            }

            return $data;
        }

        if (is_array($data[$segment])) {
            $data[$segment] = $this->sanitizePath($data[$segment], $segments);
        }

        return $data;
    }

    private function sanitizer(): HtmlSanitizer
    {
        return $this->sanitizer ??= new HtmlSanitizer($this->config());
    }

    private function config(): HtmlSanitizerConfig
    {
        /** @var list<string> $embedHosts */
        $embedHosts = array_values(array_filter(array_map(
            static fn (mixed $host): string => is_string($host) ? strtolower(trim($host)) : '',
            (array) (config('mksine.content.embed_hosts') ?? self::DEFAULT_EMBED_HOSTS),
        )));

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*')
            ->allowAttribute('id', '*')
            ->allowAttribute('dir', '*')
            ->allowAttribute('lang', '*')
            ->allowAttribute('title', '*')
            ->allowAttribute('target', 'a')
            ->allowAttribute('rel', 'a')
            ->allowAttribute('download', 'a')
            ->allowAttribute('width', ['img', 'table', 'td', 'th', 'col', 'iframe'])
            ->allowAttribute('height', ['img', 'table', 'td', 'th', 'iframe'])
            ->allowAttribute('loading', ['img', 'iframe'])
            ->allowAttribute('srcset', 'img')
            ->allowAttribute('sizes', 'img')
            ->allowAttribute('colspan', ['td', 'th'])
            ->allowAttribute('rowspan', ['td', 'th'])
            ->allowAttribute('start', 'ol')
            ->allowAttribute('reversed', 'ol')
            ->allowAttribute('type', ['ol', 'ul'])
            ->withMaxInputLength(-1);

        if ($embedHosts !== []) {
            $config = $config
                ->allowElement('iframe', ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'frameborder', 'loading', 'referrerpolicy'])
                ->withAttributeSanitizer(new EmbedHostAttributeSanitizer($embedHosts));
        }

        return $config;
    }
}
