<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Models\ConsoleLog;
use Miran\Mksine\Support\Console\AdminConsoleCommandParser;
use Miran\Mksine\Support\Console\Interactive\AdminInteractiveCommandResolver;
use Miran\Mksine\Support\Console\Interactive\MigrateSmartInteractiveHandler;

final class ConsoleInteractiveController extends Controller
{
    public function detect(Request $request): JsonResponse
    {
        SuperAdminGate::authorize();

        $command = trim((string) $request->input('command', ''));
        if ($command === '') {
            return response()->json(['message' => 'Command cannot be empty.'], 422);
        }

        $interactive = AdminInteractiveCommandResolver::isInteractiveInput($command, base_path());
        $handler = AdminInteractiveCommandResolver::handlerKey($command, base_path());

        return response()->json([
            'interactive' => $interactive,
            'handler' => $handler,
        ]);
    }

    public function migrateSmartCatalog(MigrateSmartInteractiveHandler $handler): JsonResponse
    {
        SuperAdminGate::authorize();

        return response()->json($handler->catalog());
    }

    public function migrateSmartAnalyze(Request $request, MigrateSmartInteractiveHandler $handler): JsonResponse
    {
        SuperAdminGate::authorize();

        /** @var list<string> $migrationNames */
        $migrationNames = array_values(array_filter(
            (array) $request->input('migrations', []),
            fn (mixed $value): bool => is_string($value) && $value !== '',
        ));

        if ($migrationNames === []) {
            return response()->json(['message' => 'Select at least one migration.'], 422);
        }

        $database = $this->nullableString($request->input('database'));

        return response()->json($handler->analyze($migrationNames, $database));
    }

    public function migrateSmartExecute(Request $request, MigrateSmartInteractiveHandler $handler): JsonResponse
    {
        SuperAdminGate::authorize();

        /** @var list<string> $migrationNames */
        $migrationNames = array_values(array_filter(
            (array) $request->input('migrations', []),
            fn (mixed $value): bool => is_string($value) && $value !== '',
        ));

        if ($migrationNames === []) {
            return response()->json(['message' => 'Select at least one migration.'], 422);
        }

        $database = $this->nullableString($request->input('database'));
        $dryRun = (bool) $request->boolean('dry_run');
        $force = (bool) $request->boolean('force');

        return response()->json($handler->execute($migrationNames, $dryRun, $force, $database));
    }

    public function storeLog(Request $request): JsonResponse
    {
        SuperAdminGate::authorize();

        $command = trim((string) $request->input('command', ''));
        $output = (string) $request->input('output', '');
        $exitCode = (int) $request->input('exit_code', 1);
        $durationMs = max(0, (int) $request->input('duration_ms', 0));

        if ($command === '') {
            return response()->json(['message' => 'Command cannot be empty.'], 422);
        }

        try {
            $parsed = AdminConsoleCommandParser::parse($command, base_path());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $log = ConsoleLog::query()->create([
            'user_id' => Auth::id(),
            'runner' => $parsed['runner'],
            'command' => $parsed['display'],
            'argv' => $parsed['argv'],
            'output' => $output,
            'exit_code' => $exitCode,
            'duration_ms' => $durationMs,
        ]);

        return response()->json([
            'id' => $log->id,
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
