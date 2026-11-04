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

        return response()->json($manager->present($process));
    }

    public function stop(ConsoleProcess $process, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $process = $manager->stop($manager->syncStatus($process));

        return response()->json($manager->present($process));
    }

    public function status(ConsoleProcess $process, AdminConsoleProcessManager $manager): JsonResponse
    {
        SuperAdminGate::authorize();

        $process = $manager->syncStatus($process);

        return response()->json($manager->present($process));
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
            ->map(fn (ConsoleProcess $process): array => $manager->present($manager->syncStatus($process)));

        return response()->json(['processes' => $processes]);
    }
}
