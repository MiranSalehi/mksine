<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Miran\Mksine\Support\Console\ConsoleProcessStatus;

final class ConsoleProcessController extends Controller
{
    public function start(Request $request, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $command = trim((string) $request->input('command', ''));
        if ($command === '') {
            return response()->json(['message' => 'Command cannot be empty.'], 422);
        }

        try {
            $process = $manager->start($command, (int) Auth::id());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->serializeProcess($process));
    }

    public function stop(ConsoleProcess $process, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $process = $manager->stop($manager->syncStatus($process));

        return response()->json($this->serializeProcess($process));
    }

    public function status(ConsoleProcess $process, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $process = $manager->syncStatus($process);

        return response()->json($this->serializeProcess($process));
    }

    public function output(Request $request, ConsoleProcess $process, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $offset = max(0, (int) $request->query('offset', 0));
        $process = $manager->syncStatus($process);
        $payload = $manager->readOutputFromOffset($process, $offset);

        $alive = $process->status->isActive()
            || ($process->pid !== null && $manager->isPidAlive((int) $process->pid));

        return response()->json([
            'chunk' => $payload['chunk'],
            'offset' => $payload['offset'],
            'status' => $process->status->value,
            'alive' => $alive,
            'finished' => ! $process->status->isActive(),
        ]);
    }

    public function active(AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $processes = ConsoleProcess::query()
            ->whereIn('status', [
                ConsoleProcessStatus::Pending,
                ConsoleProcessStatus::Running,
            ])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (ConsoleProcess $process): array => $this->serializeProcess($manager->syncStatus($process)));

        return response()->json(['processes' => $processes]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProcess(ConsoleProcess $process): array
    {
        $alive = $process->pid !== null && app(AdminConsoleProcessManager::class)->isPidAlive((int) $process->pid);

        return [
            'id' => $process->id,
            'command' => $process->command,
            'runner' => $process->runner,
            'status' => $process->status->value,
            'status_label' => __("mksine::console_terminal.{$process->status->labelKey()}"),
            'pid' => $process->pid,
            'alive' => $alive,
            'exit_code' => $process->exit_code,
            'started_at' => $process->started_at?->toIso8601String(),
            'finished_at' => $process->finished_at?->toIso8601String(),
            'stream_url' => route('mksine.console-process.stream', ['process' => $process->id]),
            'output_url' => route('mksine.console-process.output', ['process' => $process->id]),
            'stop_url' => route('mksine.console-process.stop', ['process' => $process->id]),
            'status_url' => route('mksine.console-process.status', ['process' => $process->id]),
        ];
    }
}
