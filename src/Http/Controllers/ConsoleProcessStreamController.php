<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ConsoleProcessStreamController extends Controller
{
    public function __invoke(
        Request $request,
        ConsoleProcess $process,
        AdminConsoleProcessManager $manager,
    ): StreamedResponse {
        SuperAdminGate::authorize();

        $offset = max(0, (int) $request->query('offset', 0));
        $maxSeconds = max(60, (int) config('mksine.console_terminal.stream_max_seconds', 86_400));

        $processId = $process->id;

        return response()->stream(function () use ($processId, $manager, $offset, $maxSeconds): void {
            if (function_exists('set_time_limit')) {
                set_time_limit(0);
            }

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $currentOffset = $offset;
            $startedAt = time();
            $lastKeepalive = time();

            while (! connection_aborted() && (time() - $startedAt) < $maxSeconds) {
                $process = ConsoleProcess::query()->findOrFail($processId);
                $process = $manager->syncStatus($process);

                $payload = $manager->readOutputFromOffset($process, $currentOffset);
                if ($payload['chunk'] !== '') {
                    $currentOffset = $payload['offset'];
                    $this->emit('output', [
                        'chunk' => $payload['chunk'],
                        'offset' => $currentOffset,
                    ]);
                }

                if (! $process->status->isActive() && $payload['chunk'] === '') {
                    $this->emit('done', [
                        'status' => $process->status->value,
                        'exit_code' => $process->exit_code,
                        'pid' => $process->pid,
                    ]);
                    break;
                }

                if (! $process->status->isActive()) {
                    $final = $manager->readOutputFromOffset($process, $currentOffset);
                    if ($final['chunk'] !== '') {
                        $currentOffset = $final['offset'];
                        $this->emit('output', [
                            'chunk' => $final['chunk'],
                            'offset' => $currentOffset,
                        ]);
                    }

                    $this->emit('done', [
                        'status' => $process->status->value,
                        'exit_code' => $process->exit_code,
                        'pid' => $process->pid,
                    ]);
                    break;
                }

                if (time() - $lastKeepalive >= 15) {
                    echo ": keepalive\n\n";
                    flush();
                    $lastKeepalive = time();
                }

                usleep(200_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(string $event, array $data): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
