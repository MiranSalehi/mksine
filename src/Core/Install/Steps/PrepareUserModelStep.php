<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Miran\Mksine\Support\MksineUserModelRequirements;
use Miran\Mksine\Support\UserModelPatcher;

/**
 * Patches app/Models/User.php in place (with a backup), or publishes the package model when missing.
 */
class PrepareUserModelStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'user-model';
    }

    public function run(InstallContext $context): StepResult
    {
        $targetPath = app_path('Models/User.php');

        if (! File::exists($targetPath)) {
            return $this->publishPackageModel($targetPath);
        }

        // File inspection only: reflecting the class here would autoload the stale model
        // before the patch lands in this process.
        $result = (new UserModelPatcher($targetPath))->patch();

        return match ($result) {
            UserModelPatcher::RESULT_PATCHED => StepResult::completed([$this->message('user_model_patched')]),
            UserModelPatcher::RESULT_ALREADY => $this->alreadyConfigured(),
            default => StepResult::completed([], [$this->message('user_model_manual')]),
        };
    }

    private function alreadyConfigured(): StepResult
    {
        $missing = MksineUserModelRequirements::missingRequirements();

        if ($missing === []) {
            return StepResult::completed([$this->message('user_model_ready')]);
        }

        return StepResult::completed([], [$this->message('user_model_missing', ['items' => implode(', ', $missing)])]);
    }

    private function publishPackageModel(string $targetPath): StepResult
    {
        $sourcePath = dirname(__DIR__, 3) . '/Models/User.php';

        if (! File::exists($sourcePath)) {
            return StepResult::failed($this->message('user_model_source_missing'));
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::put(
            $targetPath,
            str_replace('namespace Miran\Mksine\Models;', 'namespace App\Models;', File::get($sourcePath)),
        );

        return StepResult::completed([$this->message('user_model_published')]);
    }
}
