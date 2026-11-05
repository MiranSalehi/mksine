<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

use Closure;
use Illuminate\Contracts\Container\Container;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Miran\Mksine\Core\Install\Steps\ActivatePluginsStep;
use Miran\Mksine\Core\Install\Steps\ActivateThemeStep;
use Miran\Mksine\Core\Install\Steps\CheckRequirementsStep;
use Miran\Mksine\Core\Install\Steps\ClearCachesStep;
use Miran\Mksine\Core\Install\Steps\ConfigureAdminPanelStep;
use Miran\Mksine\Core\Install\Steps\ConfigureLocaleStep;
use Miran\Mksine\Core\Install\Steps\ConfigureRuntimeStep;
use Miran\Mksine\Core\Install\Steps\CreateSuperAdminStep;
use Miran\Mksine\Core\Install\Steps\DiscoverHooksStep;
use Miran\Mksine\Core\Install\Steps\FinalizeStep;
use Miran\Mksine\Core\Install\Steps\GeneratePermissionsStep;
use Miran\Mksine\Core\Install\Steps\PrepareUserModelStep;
use Miran\Mksine\Core\Install\Steps\PublishAssetsStep;
use Miran\Mksine\Core\Install\Steps\PublishResourcesStep;
use Miran\Mksine\Core\Install\Steps\RunMigrationsStep;
use Miran\Mksine\Core\Install\Steps\SeedSampleContentStep;
use Miran\Mksine\Core\Install\Steps\WriteEnvironmentStep;
use Throwable;

/**
 * Runs install steps in order, records progress and stops at the first failure.
 *
 * The CLI runs everything in one process with {@see run()}; the web installer calls
 * {@see runStep()} once per request so each request stays within the host's time limit.
 */
class InstallPipeline
{
    /**
     * Steps in run order.
     *
     * @var list<class-string<InstallStep>>
     */
    public const DEFAULT_STEPS = [
        CheckRequirementsStep::class,
        WriteEnvironmentStep::class,
        PrepareUserModelStep::class,
        ConfigureAdminPanelStep::class,
        PublishResourcesStep::class,
        ClearCachesStep::class,
        RunMigrationsStep::class,
        PublishAssetsStep::class,
        ActivateThemeStep::class,
        ActivatePluginsStep::class,
        GeneratePermissionsStep::class,
        DiscoverHooksStep::class,
        CreateSuperAdminStep::class,
        SeedSampleContentStep::class,
        ConfigureLocaleStep::class,
        ConfigureRuntimeStep::class,
        FinalizeStep::class,
    ];

    /**
     * @param  list<InstallStep>  $steps
     */
    public function __construct(
        private readonly array $steps,
        private readonly InstallProgress $progress,
    ) {}

    public static function makeDefault(Container $container): self
    {
        return new self(
            array_map(fn (string $step): InstallStep => $container->make($step), self::DEFAULT_STEPS),
            $container->make(InstallProgress::class),
        );
    }

    /**
     * @return list<InstallStep>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function step(string $key): ?InstallStep
    {
        foreach ($this->steps as $step) {
            if ($step->key() === $key) {
                return $step;
            }
        }

        return null;
    }

    public function nextPendingStep(): ?InstallStep
    {
        foreach ($this->steps as $step) {
            if (! $this->progress->isCompleted($step->key())) {
                return $step;
            }
        }

        return null;
    }

    public function progress(): InstallProgress
    {
        return $this->progress;
    }

    public function runStep(InstallStep $step, InstallContext $context): StepResult
    {
        $this->progress->start($context);

        try {
            $result = $step->run($context);
        } catch (Throwable $exception) {
            report($exception);
            $result = StepResult::failed($exception->getMessage());
        }

        if ($result->isFinished()) {
            $this->progress->markCompleted($step->key());
        } elseif ($result->status === InstallStepStatus::Failed) {
            $this->progress->recordFailure($step->key(), (string) $result->error);
        }

        return $result;
    }

    /**
     * Run every step that has not finished yet, repeating incomplete steps until they finish.
     *
     * @param  (Closure(InstallStep, StepResult): void)|null  $onStepFinished
     */
    public function run(InstallContext $context, ?Closure $onStepFinished = null): InstallRunResult
    {
        $results = [];

        foreach ($this->steps as $step) {
            if ($this->progress->exists() && $this->progress->isCompleted($step->key())) {
                continue;
            }

            do {
                $result = $this->runStep($step, $context);
            } while ($result->status === InstallStepStatus::Incomplete);

            $results[$step->key()] = $result;

            if ($onStepFinished !== null) {
                $onStepFinished($step, $result);
            }

            if ($result->status === InstallStepStatus::Failed) {
                break;
            }
        }

        return new InstallRunResult($results);
    }
}
