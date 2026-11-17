<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LengthException;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Support\SettingStore;

/**
 * Reads and writes the admin looks in the `settings` table. The only writer of these keys:
 * the generic settings form refuses them ({@see isReservedKey()}).
 *
 * - {@see COMPANY_KEY}: the company look (everyone's default).
 * - {@see SAVED_KEY}: named looks the company saved, shown next to the ready looks.
 * - {@see USER_KEY_PREFIX}{id}: one person's look, holding only the parts that differ from the company.
 *
 * Authorization is the caller's job ({@see canEditCompany()}).
 */
final class AppearanceRepository
{
    public const string COMPANY_KEY = 'mksine_appearance';

    public const string SAVED_KEY = 'mksine_appearance_saved';

    public const string USER_KEY_PREFIX = 'mksine_appearance_user_';

    /** Shield permission of the Settings → Appearance page; it also guards every company write. */
    public const string COMPANY_PERMISSION = 'View:SettingsAppearancePage';

    public const int MAX_SAVED = 24;

    public const int MAX_NAME_LENGTH = 40;

    /** Matches the `settings.value` column length. */
    public const int MAX_STORED_LENGTH = 8192;

    public function __construct(private readonly SettingStore $settings) {}

    /**
     * Keys only this class may write (company, saved and every personal look).
     */
    public static function isReservedKey(string $key): bool
    {
        return str_starts_with($key, self::COMPANY_KEY);
    }

    public static function userKey(int | string $userId): string
    {
        return self::USER_KEY_PREFIX . $userId;
    }

    public static function canEditCompany(?Authenticatable $user): bool
    {
        return $user !== null && method_exists($user, 'can') && (bool) $user->can(self::COMPANY_PERMISSION);
    }

    public function company(): Appearance
    {
        return Appearance::fromJson($this->read(self::COMPANY_KEY));
    }

    /**
     * The personal layer alone (parts that differ from the company look).
     */
    public function personal(int | string $userId): Appearance
    {
        return Appearance::fromJson($this->read(self::userKey($userId)));
    }

    /**
     * The look a request should get: company over the app default, then the person's own parts.
     */
    public function resolvedFor(?Authenticatable $user): Appearance
    {
        $look = $this->company();

        if ($user !== null) {
            $look = $this->personal($user->getAuthIdentifier())->over($look);
        }

        return $look;
    }

    /**
     * @return list<array{name: string, appearance: Appearance}>
     */
    public function saved(): array
    {
        $decoded = json_decode((string) $this->read(self::SAVED_KEY), true);

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];

        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = self::normalizeName($row['name'] ?? null);

            if ($name === null || $name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH || self::findIndex($out, $name) !== null) {
                continue;
            }

            $out[] = ['name' => $name, 'appearance' => Appearance::fromArray($row['look'] ?? null)];

            if (count($out) === self::MAX_SAVED) {
                break;
            }
        }

        return $out;
    }

    public function findSaved(string $name): ?Appearance
    {
        $saved = $this->saved();
        $index = self::findIndex($saved, $name);

        return $index === null ? null : $saved[$index]['appearance'];
    }

    public function saveCompany(Appearance $appearance): void
    {
        $this->write(self::COMPANY_KEY, $appearance->isEmpty() ? null : $appearance->toArray());
    }

    /**
     * Store a person's look as its difference from the company look; no difference removes the row.
     */
    public function saveForUser(int | string $userId, Appearance $appearance): void
    {
        $diff = $appearance->withoutPartsOf($this->company());

        $this->write(self::userKey($userId), $diff->isEmpty() ? null : $diff->toArray());
    }

    /**
     * "Same as the company": forget the person's look.
     */
    public function resetUser(int | string $userId): void
    {
        $this->write(self::userKey($userId), null);
    }

    /**
     * Save under a name; an existing name (any case) is replaced in place.
     *
     * @throws ValidationException
     */
    public function saveNamed(string $name, Appearance $appearance): void
    {
        $name = self::normalizeName($name) ?? '';

        if ($name === '') {
            throw ValidationException::withMessages(['name' => __('mksine::appearance.errors.name_required')]);
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw ValidationException::withMessages(['name' => __('mksine::appearance.errors.name_too_long', ['max' => self::MAX_NAME_LENGTH])]);
        }

        $saved = $this->saved();
        $look = Appearance::fromArray(['preset' => AppearancePresets::SAVED_PREFIX . $name] + $appearance->toArray());
        $row = ['name' => $name, 'appearance' => $look];
        $index = self::findIndex($saved, $name);

        if ($index !== null) {
            $saved[$index] = $row;
        } elseif (count($saved) >= self::MAX_SAVED) {
            throw ValidationException::withMessages(['name' => __('mksine::appearance.errors.too_many', ['max' => self::MAX_SAVED])]);
        } else {
            $saved[] = $row;
        }

        $this->writeSaved($saved);
    }

    public function deleteNamed(string $name): void
    {
        $saved = $this->saved();
        $index = self::findIndex($saved, $name);

        if ($index === null) {
            return;
        }

        array_splice($saved, $index, 1);

        $this->writeSaved($saved);
    }

    /**
     * @param  list<array{name: string, appearance: Appearance}>  $saved
     */
    private function writeSaved(array $saved): void
    {
        $rows = array_map(
            static fn (array $row): array => ['name' => $row['name'], 'look' => $row['appearance']->toArray()],
            $saved,
        );

        $this->write(self::SAVED_KEY, $rows === [] ? null : $rows);
    }

    /**
     * @param  array<mixed>|null  $value  null deletes the row
     */
    private function write(string $key, ?array $value): void
    {
        if ($value === null) {
            Setting::query()->where('key', $key)->get()->each->delete();
            $this->settings->flush();

            return;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (strlen($json) > self::MAX_STORED_LENGTH) {
            throw new LengthException(__('mksine::appearance.errors.too_large'));
        }

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $json]);
        $this->settings->flush();
    }

    private function read(string $key): ?string
    {
        $value = $this->settings->get($key);

        return is_string($value) ? $value : null;
    }

    private static function normalizeName(mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * @param  list<array{name: string, appearance: Appearance}>  $saved
     */
    private static function findIndex(array $saved, string $name): ?int
    {
        $needle = mb_strtolower(trim($name));

        foreach ($saved as $index => $row) {
            if (mb_strtolower($row['name']) === $needle) {
                return $index;
            }
        }

        return null;
    }
}
