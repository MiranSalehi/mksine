<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Faker\Factory as FakerFactory;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Database\Seeders\MksineSeeder;
use Miran\Mksine\Models\Post;

/**
 * Optional demo pages, posts and menus. The seeder uses factories, so it needs Faker,
 * which release builds made with --no-dev do not ship.
 */
class SeedSampleContentStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'sample-content';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $context->seedSampleContent) {
            return StepResult::skipped($this->message('sample_content_not_requested'));
        }

        if (! $this->databaseIsReady()) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        if (! class_exists(FakerFactory::class)) {
            return StepResult::skipped($this->message('sample_content_unavailable'), [$this->message('sample_content_unavailable')]);
        }

        if (Post::query()->exists()) {
            return StepResult::skipped($this->message('sample_content_exists'));
        }

        [$code, $output] = $this->artisan('db:seed', ['--class' => MksineSeeder::class, '--force' => true]);

        return $code === 0
            ? StepResult::completed([$this->message('sample_content_seeded')])
            : StepResult::failed($this->message('command_failed', ['command' => 'db:seed']) . ' ' . $output);
    }
}
