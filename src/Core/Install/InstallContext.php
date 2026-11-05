<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

/**
 * Choices for one installation run, shared by the CLI and the web installer.
 *
 * The admin password is never included in {@see toArray()}, so it is not written to the progress file.
 */
final readonly class InstallContext
{
    /**
     * @param  list<string>  $plugins  Plugin identifiers to install and activate.
     * @param  array<string, string>  $environment  .env keys to write before anything else runs.
     */
    public function __construct(
        public InstallProfile $profile = InstallProfile::Vps,
        public bool $migrate = true,
        public bool $force = false,
        public ?string $adminName = null,
        public ?string $adminEmail = null,
        public ?string $adminPassword = null,
        public ?string $theme = null,
        public array $plugins = [],
        public ?string $locale = null,
        public bool $seedSampleContent = false,
        public array $environment = [],
    ) {}

    public function wantsSuperAdmin(): bool
    {
        return filled($this->adminEmail) && filled($this->adminPassword);
    }

    public function isShared(): bool
    {
        return $this->profile === InstallProfile::Shared;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile->value,
            'migrate' => $this->migrate,
            'force' => $this->force,
            'admin_name' => $this->adminName,
            'admin_email' => $this->adminEmail,
            'theme' => $this->theme,
            'plugins' => $this->plugins,
            'locale' => $this->locale,
            'seed_sample_content' => $this->seedSampleContent,
        ];
    }
}
