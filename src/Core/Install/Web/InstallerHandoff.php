<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Web;

use Illuminate\Filesystem\Filesystem;

/**
 * A one-time code left by the quick installer (mksine.php) after it has put the
 * files in place. Posting this code to /install/handoff makes the browser that ran the
 * quick installer the owner of /install.
 *
 * The file holds only a SHA-256 hash and an expiry time; it is deleted on first use.
 */
class InstallerHandoff
{
    public const FILE = 'mksine/installer-handoff.json';

    public function __construct(private readonly Filesystem $files) {}

    public function consume(string $code): bool
    {
        $path = $this->path();

        if ($code === '' || ! $this->files->exists($path)) {
            return false;
        }

        $data = json_decode((string) $this->files->get($path), true);
        $hash = is_array($data) ? ($data['token_hash'] ?? null) : null;
        $expiresAt = is_array($data) ? (int) ($data['expires_at'] ?? 0) : 0;

        if (! is_string($hash) || ! hash_equals($hash, hash('sha256', $code))) {
            return false;
        }

        $this->files->delete($path);

        return $expiresAt >= time();
    }

    public function path(): string
    {
        return storage_path(self::FILE);
    }
}
