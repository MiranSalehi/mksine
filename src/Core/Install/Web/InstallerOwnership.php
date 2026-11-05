<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Which browser session may run the installer. A session becomes the owner after it
 * enters the setup token ({@see SetupToken}); a later session that enters the token
 * takes over, because only someone with access to the server files can read it.
 */
class InstallerOwnership
{
    public const FILE = 'mksine/installer-owner.json';

    public const SESSION_KEY = 'mksine_installer.owner_token';

    public function __construct(private readonly Filesystem $files) {}

    public function isOwner(Session $session): bool
    {
        $token = $session->get(self::SESSION_KEY);
        $hash = $this->ownerHash();

        return is_string($token) && $hash !== null && hash_equals($hash, hash('sha256', $token));
    }

    public function claim(Session $session): void
    {
        $token = Str::random(64);
        $session->put(self::SESSION_KEY, $token);

        $this->files->ensureDirectoryExists(dirname($this->path()));
        $this->files->put($this->path(), (string) json_encode(['token_hash' => hash('sha256', $token), 'claimed_at' => time()]));
    }

    public function release(): void
    {
        $this->files->delete($this->path());
    }

    public function path(): string
    {
        return storage_path(self::FILE);
    }

    private function ownerHash(): ?string
    {
        if (! $this->files->exists($this->path())) {
            return null;
        }

        $data = json_decode((string) $this->files->get($this->path()), true);

        return is_array($data) && is_string($data['token_hash'] ?? null) ? $data['token_hash'] : null;
    }
}
