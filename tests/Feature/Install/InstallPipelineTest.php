<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallPipeline;
use Miran\Mksine\Core\Install\InstallProgress;
use Miran\Mksine\Core\Install\InstallStepStatus;
use Miran\Mksine\Core\Install\StepResult;

function fakeInstallStep(string $key, Closure $run): InstallStep
{
    return new class($key, $run) implements InstallStep
    {
        public int $runs = 0;

        public function __construct(private string $stepKey, private Closure $callback) {}

        public function key(): string
        {
            return $this->stepKey;
        }

        public function label(): string
        {
            return $this->stepKey;
        }

        public function run(InstallContext $context): StepResult
        {
            $this->runs++;

            return ($this->callback)($this->runs);
        }
    };
}

beforeEach(function (): void {
    $this->originalStoragePath = $this->app->storagePath();
    $this->storagePath = sys_get_temp_dir() . '/mksine-pipeline-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->storagePath);
    $this->app->useStoragePath($this->storagePath);
    $this->progress = new InstallProgress(new Filesystem, new InstallationState(new Filesystem));
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    (new Filesystem)->deleteDirectory($this->storagePath);
});

it('runs steps in order and records progress', function (): void {
    $order = [];
    $pipeline = new InstallPipeline([
        fakeInstallStep('one', function () use (&$order): StepResult {
            $order[] = 'one';

            return StepResult::completed();
        }),
        fakeInstallStep('two', function () use (&$order): StepResult {
            $order[] = 'two';

            return StepResult::skipped('nothing to do');
        }),
    ], $this->progress);

    $result = $pipeline->run(new InstallContext);

    expect($order)->toBe(['one', 'two'])
        ->and($result->succeeded())->toBeTrue()
        ->and($this->progress->completedKeys())->toBe(['one', 'two'])
        ->and($pipeline->nextPendingStep())->toBeNull();
});

it('stops at the first failure and resumes from it on the next run', function (): void {
    $first = fakeInstallStep('one', fn (): StepResult => StepResult::completed());
    $second = fakeInstallStep('two', fn (int $runs): StepResult => $runs === 1 ? StepResult::failed('boom') : StepResult::completed());
    $third = fakeInstallStep('three', fn (): StepResult => StepResult::completed());
    $pipeline = new InstallPipeline([$first, $second, $third], $this->progress);

    $failed = $pipeline->run(new InstallContext);

    expect($failed->succeeded())->toBeFalse()
        ->and($failed->failedStepKey())->toBe('two')
        ->and($third->runs)->toBe(0)
        ->and($this->progress->lastFailure())->toMatchArray(['step' => 'two', 'error' => 'boom'])
        ->and($pipeline->nextPendingStep()?->key())->toBe('two');

    $resumed = $pipeline->run(new InstallContext);

    expect($resumed->succeeded())->toBeTrue()
        ->and($first->runs)->toBe(1)
        ->and($second->runs)->toBe(2)
        ->and($third->runs)->toBe(1)
        ->and($this->progress->lastFailure())->toBeNull();
});

it('repeats an incomplete step until it finishes', function (): void {
    $step = fakeInstallStep('migrate', fn (int $runs): StepResult => $runs < 3 ? StepResult::incomplete() : StepResult::completed());

    $result = (new InstallPipeline([$step], $this->progress))->run(new InstallContext);

    expect($step->runs)->toBe(3)
        ->and($result->results['migrate']->status)->toBe(InstallStepStatus::Completed);
});

it('turns an exception into a failed step', function (): void {
    $step = fakeInstallStep('explodes', fn (): StepResult => throw new RuntimeException('disk full'));

    $result = (new InstallPipeline([$step], $this->progress))->runStep($step, new InstallContext);

    expect($result->status)->toBe(InstallStepStatus::Failed)
        ->and($result->error)->toBe('disk full');
});

it('marks the installation as in progress until the progress file is cleared', function (): void {
    $state = new InstallationState(new Filesystem);
    $step = fakeInstallStep('one', fn (): StepResult => StepResult::completed());

    (new InstallPipeline([$step], $this->progress))->runStep($step, new InstallContext);

    expect($state->isInstallationInProgress())->toBeTrue();

    $this->progress->clear();

    expect($state->isInstallationInProgress())->toBeFalse();
});

it('never writes the admin password or environment values to the progress file', function (): void {
    $step = fakeInstallStep('one', fn (): StepResult => StepResult::completed());
    $context = new InstallContext(adminEmail: 'admin@example.com', adminPassword: 'super-secret-pass', environment: ['DB_PASSWORD' => 'db-secret']);

    (new InstallPipeline([$step], $this->progress))->runStep($step, $context);

    expect(file_get_contents($this->progress->path()))
        ->not->toContain('super-secret-pass')
        ->not->toContain('db-secret');
});

it('builds the default pipeline from the container', function (): void {
    $keys = array_map(fn (InstallStep $step): string => $step->key(), app(InstallPipeline::class)->steps());

    expect($keys)->toHaveCount(count(InstallPipeline::DEFAULT_STEPS))
        ->and($keys[0])->toBe('requirements')
        ->and(end($keys))->toBe('finalize')
        ->and(array_unique($keys))->toHaveCount(count($keys));
});
