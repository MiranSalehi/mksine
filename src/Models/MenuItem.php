<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const TYPE_CUSTOM_LINK = 'custom_link';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_PAGE = 'page';

    public const TYPE_POST = 'post';

    public const TYPE_TAG = 'tag';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'label',
        'url',
        'reference_id',
        'order',
        'target',
    ];

    protected $casts = [
        'order' => 'integer',
        'reference_id' => 'integer',
    ];

    /**
     * URL schemes a menu link may use. Anything else (javascript:, data:, vbscript:) becomes `#`.
     *
     * @var list<string>
     */
    public const array SAFE_URL_SCHEMES = ['http', 'https', 'mailto', 'tel', 'sms'];

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item): void {
            if ($item->isDirty('url') && $item->url !== null && $item->url !== '') {
                $item->url = static::safeUrl($item->url);
            }
        });
    }

    /**
     * Whether a link target is a relative URL or uses an allowed scheme.
     *
     * Browsers ignore tabs, newlines and leading control characters inside a scheme and decode
     * entities in attributes (`java&#x09;script:`), so the scheme is read after removing both.
     */
    public static function isSafeUrl(?string $url): bool
    {
        if ($url === null) {
            return true;
        }

        $normalized = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = (string) preg_replace('/[\x00-\x20\x7F]+/u', '', $normalized);

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches) !== 1) {
            return true;
        }

        return in_array(strtolower($matches[1]), self::SAFE_URL_SCHEMES, true);
    }

    /**
     * The URL itself when {@see self::isSafeUrl()} allows it, otherwise `#`.
     */
    public static function safeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return static::isSafeUrl($url) ? $url : '#';
    }

    /**
     * Get the menu this item belongs to.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (parent chain).
     */
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get the resolved URL for this menu item.
     */
    public function getResolvedUrl(): ?string
    {
        // For referenced items, URL resolution should be handled by the source
        return static::safeUrl($this->url);
    }

    /**
     * Check if this item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the depth level of this item in the tree.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }
}
