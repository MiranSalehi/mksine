<?php

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;

/**
 * Named preview targets. A region lists the fields edited together.
 * A repeater region plus an index edits one row and leaves the other rows in place.
 */
final class BlockRegion
{
    /**
     * @return array<string, array<string, array{fields?: list<string>, repeater?: string}>>
     */
    public static function map(): array
    {
        return [
            'heading' => [],
            'text' => [
                'content' => ['fields' => ['content']],
            ],
            'image' => [
                'image' => ['fields' => ['image', 'alt', 'caption', 'size', 'alignment', 'rounded', 'shadow']],
            ],
            'button' => [
                'button' => ['fields' => ['text', 'url', 'style', 'size', 'alignment', 'new_tab', 'full_width']],
            ],
            'video' => [
                'video' => ['fields' => ['video', 'caption', 'controls', 'autoplay', 'loop']],
            ],
            'audio' => [
                'audio' => ['fields' => ['audio', 'caption', 'controls', 'autoplay', 'loop']],
            ],
            'spacer' => [
                'spacer' => ['fields' => ['size']],
            ],
            'divider' => [
                'divider' => ['fields' => ['style', 'width', 'alignment']],
            ],
            'hero' => [
                'frame' => ['fields' => ['background_image', 'overlay', 'height', 'text_alignment', 'text_color']],
                'title' => ['fields' => ['title']],
                'subtitle' => ['fields' => ['subtitle']],
                'button' => ['fields' => ['button_text', 'button_url', 'button_style']],
                'secondary' => ['fields' => ['show_secondary_button', 'secondary_button_text', 'secondary_button_url']],
            ],
            'cta' => [
                'frame' => ['fields' => ['style', 'alignment']],
                'title' => ['fields' => ['title']],
                'description' => ['fields' => ['description']],
                'button' => ['fields' => ['button_text', 'button_url']],
                'secondary' => ['fields' => ['show_secondary_button', 'secondary_button_text', 'secondary_button_url']],
            ],
            'features' => [
                'frame' => ['fields' => ['columns', 'style', 'icon_style']],
                'heading' => ['fields' => ['heading']],
                'subheading' => ['fields' => ['subheading']],
                'features' => ['repeater' => 'features'],
            ],
            'testimonial' => [
                'frame' => ['fields' => ['layout', 'columns', 'style']],
                'heading' => ['fields' => ['heading']],
                'testimonials' => ['repeater' => 'testimonials'],
            ],
            'slider' => [
                'settings' => ['fields' => ['type', 'height', 'autoplay', 'autoplay_speed', 'show_arrows', 'show_dots', 'loop', 'effect']],
                'slides' => ['repeater' => 'slides'],
            ],
            'accordion' => [
                'settings' => ['fields' => ['style', 'allow_multiple', 'first_open', 'icon_position']],
                'heading' => ['fields' => ['heading']],
                'items' => ['repeater' => 'items'],
            ],
            'tabs' => [
                'settings' => ['fields' => ['style', 'alignment', 'orientation']],
                'tabs' => ['repeater' => 'tabs'],
            ],
            'columns' => [
                'layout' => ['fields' => ['columns', 'layout', 'gap', 'vertical_alignment', 'stack_on_mobile']],
            ],
            'grid_layout' => [
                'layout' => ['fields' => ['column_spans', 'gap', 'vertical_alignment', 'stack_on_mobile']],
            ],
            'container_inset' => [
                'frame' => ['fields' => ['max_width', 'background_full_bleed', 'background_color', 'background_gradient']],
            ],
            'card' => [
                'frame' => ['fields' => ['show_header', 'show_footer', 'background_color', 'text_color', 'border_color', 'border_width', 'radius', 'shadow']],
                'header' => ['fields' => ['header_background', 'header_color', 'header_align', 'header_padding']],
                'body' => ['fields' => ['body_background', 'body_padding']],
                'footer' => ['fields' => ['footer_background', 'footer_color', 'footer_align', 'footer_padding']],
            ],
            'section_ref' => [
                'section' => ['fields' => ['section_id']],
            ],
            'mksine_finance_showcase' => [
                'heading' => ['fields' => ['heading', 'heading_accent']],
                'paragraph_1' => ['fields' => ['paragraph_1', 'paragraph_1_highlight']],
                'paragraph_2' => ['fields' => ['paragraph_2', 'paragraph_2_highlight']],
                'social_caption' => ['fields' => ['social_caption']],
                'social_count' => ['fields' => ['social_count']],
                'avatars' => ['repeater' => 'avatars'],
                'trust_line' => ['fields' => ['trust_line']],
                'cta_primary' => ['fields' => ['cta_primary_label', 'cta_primary_url']],
                'cta_secondary' => ['fields' => ['cta_secondary_label', 'cta_secondary_url']],
                'chart' => ['fields' => ['mock_chart_caption']],
            ],
            'mksine_hero_domain' => [
                'background' => ['fields' => ['background_image', 'background_color']],
                'heading' => ['fields' => ['heading_line1', 'heading_line2_prefix', 'heading_accent', 'heading_after']],
                'subheading' => ['fields' => ['subheading']],
                'cta' => ['fields' => ['cta_label', 'cta_url']],
                'illustration' => ['fields' => ['illustration', 'illustration_alt', 'illustration_position']],
            ],
            'mksine_services_trio' => [
                'title' => ['fields' => ['section_title']],
                'cards' => ['repeater' => 'cards'],
            ],
            'mksine_featured_domains' => [
                'title' => ['fields' => ['title']],
                'view_all' => ['fields' => ['view_all_label', 'view_all_url']],
                'items' => ['repeater' => 'items'],
            ],
            'mksine_clinic_features_grid' => [
                'direction' => ['fields' => ['text_direction']],
                'badge' => ['fields' => ['badge']],
                'heading' => ['fields' => ['heading_prefix', 'heading_accent']],
                'subheading' => ['fields' => ['subheading']],
                'cards' => ['repeater' => 'cards'],
            ],
            'mksine_testimonials_grid' => [
                'direction' => ['fields' => ['text_direction']],
                'badge' => ['fields' => ['badge']],
                'heading' => ['fields' => ['title_prefix', 'title_accent']],
                'subheading' => ['fields' => ['subheading']],
                'aside' => ['fields' => ['aside']],
                'items' => ['repeater' => 'items'],
            ],
            'mksine_post_comments_feed' => [
                'badge' => ['fields' => ['badge']],
                'heading' => ['fields' => ['title_prefix', 'title_accent']],
                'subheading' => ['fields' => ['subheading']],
                'aside' => ['fields' => ['aside']],
                'source' => ['fields' => ['post_id', 'max_root_comments', 'quote_max_chars', 'text_direction']],
            ],
        ];
    }

    /**
     * @return array<string, array{fields?: list<string>, repeater?: string}>
     */
    public static function forType(string $type): array
    {
        if (array_key_exists($type, self::map())) {
            return self::map()[$type];
        }

        if (! function_exists('app')) {
            return [];
        }

        try {
            $class = app(ComponentRegistry::class)->get($type);
        } catch (\Throwable) {
            return [];
        }

        if ($class === null || ! method_exists($class, 'regionDefinitions')) {
            return [];
        }

        $method = new \ReflectionMethod($class, 'regionDefinitions');
        if ($method->getDeclaringClass()->getName() === BaseBuilderComponent::class) {
            return [];
        }

        return $class::regionDefinitions();
    }

    public static function attributes(?string $region, bool $preview, ?int $index = null): string
    {
        if (! $preview || $region === null || $region === '') {
            return '';
        }

        $html = ' data-mksine-region="'.e($region).'"';
        if ($index !== null && $index >= 0) {
            $html .= ' data-mksine-region-index="'.$index.'"';
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function authorData(string $type, array $data): array
    {
        if ($type === MksineFinanceShowcaseComponent::getType()) {
            return MksineFinanceShowcaseComponent::forEditor($data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>
     */
    public static function apply(string $type, array $base, array $submitted, string $region, ?int $index): array
    {
        $definition = self::forType($type)[$region] ?? null;
        if ($definition === null) {
            return $submitted;
        }

        unset($submitted['presentation']);

        if (isset($definition['repeater']) && $index !== null) {
            $key = $definition['repeater'];
            $list = array_values(is_array($base[$key] ?? null) ? $base[$key] : []);
            if (! isset($list[$index]) || ! is_array($list[$index])) {
                return $base;
            }

            $list[$index] = array_merge($list[$index], $submitted);
            $base[$key] = $list;

            return $base;
        }

        foreach ($definition['fields'] ?? [] as $field) {
            if (array_key_exists($field, $submitted)) {
                $base[$field] = $submitted[$field];
            }
        }

        if (isset($definition['repeater']) && array_key_exists($definition['repeater'], $submitted)) {
            $base[$definition['repeater']] = $submitted[$definition['repeater']];
        }

        return $base;
    }
}
