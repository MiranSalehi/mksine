<x-filament-panels::page>
    @php
        $minHeight = max(320, (int) config('mksine.console_terminal.default_output_height_px', 500));
        $logs = $this->logs;
        $iconButton = 'inline-flex size-8 items-center justify-center rounded-md text-zinc-400 transition hover:bg-white/[0.07] hover:text-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/60 disabled:pointer-events-none disabled:opacity-40';
    @endphp

    <div
        x-data="mksAdminConsole(@js($this->consoleApi))"
        x-on:mks-console-log-deleted.window="onLogDeleted($event.detail)"
        x-on:mks-console-logs-cleared.window="onLogsCleared()"
        x-on:keydown.escape.window="if (fullscreen && ! focused) fullscreen = false"
        class="mks-console flex flex-col gap-4"
        x-bind:class="{ 'mks-console--fullscreen': fullscreen }"
        style="--mks-console-min-height: {{ $minHeight }}px"
    >
        {{-- ───────────────────────── Health ───────────────────────── --}}
        @php
            $tones = [
                'healthy' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20',
                'late' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20',
                'down' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20',
                'idle' => 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10',
                'not_needed' => 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10',
                'loading' => 'bg-gray-50 text-gray-400 ring-gray-500/10 dark:bg-white/5 dark:text-gray-500 dark:ring-white/10',
            ];
            $healthLabels = [
                'scheduler' => collect(['healthy', 'late', 'down'])->mapWithKeys(fn ($s) => [$s => __("mksine::console_terminal.health_scheduler_{$s}")])->all(),
                'workers' => collect(['healthy', 'idle', 'down', 'not_needed'])->mapWithKeys(fn ($s) => [$s => __("mksine::console_terminal.health_workers_{$s}")])->all(),
                'workersCount' => __('mksine::console_terminal.health_workers_count'),
                'lastRun' => __('mksine::console_terminal.health_last_run'),
                'neverRan' => __('mksine::console_terminal.health_never_ran'),
                'next' => __('mksine::console_terminal.health_next_task'),
                'processed' => __('mksine::console_terminal.health_processed'),
                'busy' => __('mksine::console_terminal.health_busy'),
                'loading' => __('mksine::console_terminal.health_loading'),
            ];
            $cardClass = 'flex min-w-0 flex-col rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10';
            $iconTile = 'flex size-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400';
            $badge = 'inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset';
            $actionButton = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-950/10 transition hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-50 dark:text-gray-200 dark:ring-white/15 dark:hover:bg-white/5';
            $primaryAction = 'inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:pointer-events-none disabled:opacity-50';
        @endphp

        <div
            wire:ignore
            x-data="{ tones: @js($tones), healthLabels: @js($healthLabels) }"
            x-show="healthOpen && ! fullscreen"
            x-cloak
            class="mks-console__health space-y-3"
        >
            <div class="grid gap-4 md:grid-cols-3">
                {{-- Scheduler --}}
                <div class="{{ $cardClass }}">
                    <div class="flex items-start gap-3">
                        <div class="{{ $iconTile }}">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::console_terminal.health_scheduler') }}</p>
                            <p
                                class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                x-text="! health ? healthLabels.loading : (health.scheduler.last_run_human ? healthLabels.lastRun.replace(':time', health.scheduler.last_run_human) : healthLabels.neverRan)"
                            ></p>
                        </div>
                        <span class="{{ $badge }}" x-bind:class="tones[health?.scheduler?.status ?? 'loading']">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <span x-text="health ? healthLabels.scheduler[health.scheduler.status] : '…'"></span>
                        </span>
                    </div>

                    <div class="mt-3 min-h-0 text-xs">
                        <template x-if="health && health.scheduler.status === 'down'">
                            <div class="rounded-lg bg-gray-50 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                <p class="text-gray-600 dark:text-gray-300">{{ __('mksine::console_terminal.health_cron_hint') }}</p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <code class="min-w-0 flex-1 truncate font-mono text-[11px] text-gray-800 dark:text-gray-200" dir="ltr" x-text="health.scheduler.cron_line" x-bind:title="health.scheduler.cron_line"></code>
                                    <button
                                        type="button"
                                        class="inline-flex size-6 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-200/70 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                        x-on:click="copyCronLine()"
                                        title="{{ __('mksine::console_terminal.copy_output') }}"
                                    >
                                        <x-filament::icon x-show="! cronCopied" icon="heroicon-m-clipboard-document" class="size-3.5" />
                                        <x-filament::icon x-show="cronCopied" x-cloak icon="heroicon-m-check" class="size-3.5 text-success-500" />
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="health && health.scheduler.status !== 'down' && health.scheduler.next_task">
                            <p class="truncate text-gray-600 dark:text-gray-300">
                                <span x-text="healthLabels.next"></span>
                                <span class="font-mono text-gray-900 dark:text-gray-100" dir="ltr" x-text="health.scheduler.next_task.name"></span>
                                <span class="text-gray-400">·</span>
                                <span x-text="health.scheduler.next_task.next_run_human"></span>
                            </p>
                        </template>
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2 pt-3">
                        <button type="button" class="{{ $actionButton }}" x-on:click="tasksOpen = true" x-bind:disabled="! health">
                            <x-filament::icon icon="heroicon-m-list-bullet" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_tasks') }}
                            <span class="tabular-nums text-gray-400" x-text="health ? '(' + health.scheduler.tasks.length + ')' : ''"></span>
                        </button>
                        <button type="button" class="{{ $actionButton }}" x-on:click="runHealthCommand('scheduleRun')" x-bind:disabled="! health || loading || interactive.active">
                            <x-filament::icon icon="heroicon-m-play" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_run_scheduler') }}
                        </button>
                        <button
                            type="button"
                            class="{{ $actionButton }}"
                            x-show="health && health.scheduler.status === 'down'"
                            x-on:click="runHealthCommand('scheduleWork')"
                            x-bind:disabled="loading || interactive.active"
                            title="{{ __('mksine::console_terminal.health_start_schedule_work_hint') }}"
                        >
                            <x-filament::icon icon="heroicon-m-bolt" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_start_schedule_work') }}
                        </button>
                    </div>
                </div>

                {{-- Queue workers --}}
                <div class="{{ $cardClass }}">
                    <div class="flex items-start gap-3">
                        <div class="{{ $iconTile }}">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::console_terminal.health_workers') }}</p>
                            <p
                                class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                x-text="! health ? healthLabels.loading : (health.workers.status === 'healthy' ? healthLabels.workersCount.replace(':count', health.workers.items.length) : healthLabels.workers[health.workers.status])"
                            ></p>
                        </div>
                        <span class="{{ $badge }}" x-bind:class="tones[health?.workers?.status ?? 'loading']">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <span x-text="health ? (health.workers.status === 'healthy' ? health.workers.items.length : (health.workers.status === 'not_needed' ? 'sync' : '0')) : '…'"></span>
                        </span>
                    </div>

                    <ul class="mt-3 space-y-1.5 text-xs" x-show="health?.workers?.items?.length">
                        <template x-for="worker in (health?.workers?.items ?? []).slice(0, 3)" :key="worker.key">
                            <li class="flex items-center gap-2">
                                <span
                                    class="size-1.5 shrink-0 rounded-full"
                                    x-bind:class="worker.state === 'busy' ? 'bg-warning-500' : 'bg-success-500'"
                                ></span>
                                <span class="min-w-0 flex-1 truncate font-mono text-[11px] text-gray-700 dark:text-gray-300" dir="ltr">
                                    <span x-text="worker.connection + ':' + worker.queue"></span>
                                    <span class="text-gray-400" x-text="'· pid ' + worker.pid"></span>
                                </span>
                                <span
                                    class="shrink-0 truncate text-gray-500 dark:text-gray-400"
                                    x-text="worker.state === 'busy' ? healthLabels.busy : healthLabels.processed.replace(':count', worker.processed)"
                                    x-bind:title="worker.current_job ?? worker.last_seen_human"
                                ></span>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-auto flex flex-wrap gap-2 pt-3" x-show="health && health.workers.status !== 'not_needed'">
                        <button
                            type="button"
                            class="{{ $primaryAction }}"
                            x-show="health?.workers?.status !== 'healthy'"
                            x-on:click="runHealthCommand('queueWork')"
                            x-bind:disabled="loading || interactive.active"
                            title="{{ __('mksine::console_terminal.health_start_worker_hint') }}"
                        >
                            <x-filament::icon icon="heroicon-m-play" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_start_worker') }}
                        </button>
                        <button
                            type="button"
                            class="{{ $actionButton }}"
                            x-show="health?.workers?.status === 'healthy'"
                            x-on:click="runHealthCommand('queueRestart')"
                            x-bind:disabled="loading || interactive.active"
                            title="{{ __('mksine::console_terminal.health_restart_workers_hint') }}"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-path" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_restart_workers') }}
                        </button>
                    </div>
                </div>

                {{-- Queue backlog --}}
                <div class="{{ $cardClass }}">
                    <div class="flex items-start gap-3">
                        <div class="{{ $iconTile }}">
                            <x-filament::icon icon="heroicon-o-queue-list" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::console_terminal.health_queue') }}</p>
                            <p
                                class="mt-0.5 truncate font-mono text-xs text-gray-500 dark:text-gray-400"
                                dir="ltr"
                                x-text="health ? health.queue.connection + ' · ' + health.queue.queue : '…'"
                            ></p>
                        </div>
                        <span
                            class="{{ $badge }}"
                            x-show="(health?.queue?.failed ?? 0) > 0"
                            x-bind:class="tones.down"
                        >
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <span x-text="health?.queue?.failed + ' ' + @js(__('mksine::console_terminal.health_failed_short'))"></span>
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-3 gap-2" x-show="health && ! health.queue.sync">
                        <div class="rounded-lg bg-gray-50 px-2.5 py-2 dark:bg-white/5">
                            <dt class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('mksine::console_terminal.health_pending') }}</dt>
                            <dd
                                class="mt-0.5 text-lg font-semibold tabular-nums"
                                x-bind:class="(health?.queue?.pending ?? 0) > 0 && health?.workers?.status !== 'healthy' ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white'"
                                x-text="health?.queue?.pending ?? '—'"
                            ></dd>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-2.5 py-2 dark:bg-white/5">
                            <dt class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('mksine::console_terminal.health_failed') }}</dt>
                            <dd
                                class="mt-0.5 text-lg font-semibold tabular-nums"
                                x-bind:class="(health?.queue?.failed ?? 0) > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white'"
                                x-text="health?.queue?.failed ?? '—'"
                            ></dd>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-2.5 py-2 dark:bg-white/5">
                            <dt class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('mksine::console_terminal.health_oldest') }}</dt>
                            <dd class="mt-0.5 truncate text-sm font-semibold leading-7 text-gray-950 dark:text-white" x-text="health?.queue?.oldest_pending_human ?? '—'"></dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400" x-show="health?.queue?.sync">
                        {{ __('mksine::console_terminal.health_workers_not_needed') }}
                    </p>

                    <div class="mt-auto flex flex-wrap gap-2 pt-3" x-show="(health?.queue?.failed ?? 0) > 0">
                        <button type="button" class="{{ $actionButton }}" x-on:click="runHealthCommand('queueRetry')" x-bind:disabled="loading || interactive.active">
                            <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_retry_failed') }}
                        </button>
                        <button type="button" class="{{ $actionButton }}" x-on:click="runHealthCommand('queueFailed')" x-bind:disabled="loading || interactive.active">
                            <x-filament::icon icon="heroicon-m-list-bullet" class="size-3.5" />
                            {{ __('mksine::console_terminal.health_list_failed') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Background processes started from this console --}}
            <div
                x-show="(health?.processes ?? []).length"
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-2 dark:border-white/10">
                    <x-filament::icon icon="heroicon-m-server-stack" class="size-4 text-gray-400" />
                    <p class="text-xs font-semibold text-gray-950 dark:text-white">{{ __('mksine::console_terminal.health_background') }}</p>
                    <span class="rounded-md bg-gray-100 px-1.5 text-[11px] font-medium tabular-nums text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="(health?.processes ?? []).length"></span>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    <template x-for="item in (health?.processes ?? [])" :key="item.id">
                        <li class="flex items-center gap-3 px-4 py-2">
                            <span class="relative flex size-2 shrink-0">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-success-400 opacity-60"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-success-500"></span>
                            </span>
                            <span class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800 dark:text-gray-200" dir="ltr" x-text="item.command"></span>
                            <span class="hidden shrink-0 font-mono text-[11px] text-gray-400 sm:inline" dir="ltr" x-text="(item.pid ? 'pid ' + item.pid + ' · ' : '') + (item.started_human ?? '')"></span>
                            <span
                                x-show="isAttached(item)"
                                class="shrink-0 rounded-md bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400"
                            >{{ __('mksine::console_terminal.health_attached') }}</span>
                            <button
                                type="button"
                                x-show="! isAttached(item)"
                                class="{{ $actionButton }} shrink-0 py-1!"
                                x-on:click="attachTo(item)"
                                x-bind:disabled="interactive.active"
                            >
                                <x-filament::icon icon="heroicon-m-eye" class="size-3.5" />
                                {{ __('mksine::console_terminal.health_view') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-danger-600 ring-1 ring-danger-600/20 transition hover:bg-danger-50 dark:text-danger-400 dark:ring-danger-400/30 dark:hover:bg-danger-400/10"
                                x-on:click="stopBackground(item)"
                            >
                                <x-filament::icon icon="heroicon-m-stop" class="size-3.5" />
                                {{ __('mksine::console_terminal.stop') }}
                            </button>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- Scheduled tasks dialog --}}
            <div
                x-show="tasksOpen"
                x-cloak
                x-transition.opacity
                x-on:keydown.escape.window="tasksOpen = false"
                x-on:click.self="tasksOpen = false"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/50 p-4"
            >
                <div class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="size-5 text-gray-400" />
                        <h2 class="flex-1 text-base font-semibold text-gray-950 dark:text-white">{{ __('mksine::console_terminal.health_tasks_title') }}</h2>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-200"
                            x-on:click="tasksOpen = false"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="size-5" />
                        </button>
                    </div>
                    <div class="min-h-0 overflow-y-auto">
                        <table class="w-full text-start text-sm">
                            <thead class="sticky top-0 bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                <tr>
                                    <th class="px-5 py-2.5 text-start font-medium">{{ __('mksine::console_terminal.health_task') }}</th>
                                    <th class="px-3 py-2.5 text-start font-medium">{{ __('mksine::console_terminal.health_frequency') }}</th>
                                    <th class="px-3 py-2.5 text-start font-medium">{{ __('mksine::console_terminal.health_next_run') }}</th>
                                    <th class="px-5 py-2.5 text-start font-medium">{{ __('mksine::console_terminal.health_last_result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                <template x-for="task in (health?.scheduler?.tasks ?? [])" :key="task.name + task.expression">
                                    <tr>
                                        <td class="max-w-[16rem] truncate px-5 py-2.5 font-mono text-xs text-gray-900 dark:text-gray-100" dir="ltr" x-text="task.name" x-bind:title="task.name"></td>
                                        <td class="px-3 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400" dir="ltr" x-text="task.expression"></td>
                                        <td class="px-3 py-2.5 text-xs text-gray-600 dark:text-gray-300" x-text="task.next_run_human ?? '—'"></td>
                                        <td class="px-5 py-2.5 text-xs">
                                            <span class="inline-flex items-center gap-1.5" x-show="task.last_run_human">
                                                <span class="size-1.5 rounded-full" x-bind:class="task.last_succeeded ? 'bg-success-500' : 'bg-danger-500'"></span>
                                                <span class="text-gray-600 dark:text-gray-300" x-text="task.last_run_human"></span>
                                            </span>
                                            <span class="text-gray-400" x-show="! task.last_run_human">—</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p class="px-5 py-8 text-center text-sm text-gray-500" x-show="! (health?.scheduler?.tasks ?? []).length">{{ __('mksine::console_terminal.health_no_tasks') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mks-console__layout flex flex-col gap-4 lg:flex-row">
            {{-- ───────────────────────── Terminal ───────────────────────── --}}
            <section
                wire:ignore
                dir="ltr"
                class="mks-console__terminal flex min-w-0 flex-1 flex-col overflow-hidden rounded-xl shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10"
            >
                {{-- Toolbar --}}
                <header class="flex h-12 shrink-0 items-center gap-3 border-b border-white/[0.06] px-3">
                    <div
                        class="flex shrink-0 items-center gap-2 rounded-md bg-white/[0.05] px-2.5 py-1 text-xs font-medium"
                        x-bind:class="{
                            'text-zinc-400': state === 'idle',
                            'text-amber-300': state === 'pending' || state === 'stopped',
                            'text-emerald-400': state === 'running' || state === 'completed',
                            'text-red-400': state === 'failed',
                            'text-violet-300': state === 'interactive',
                        }"
                    >
                        <span class="relative flex size-2">
                            <span x-show="isRunning" class="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-60"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-current"></span>
                        </span>
                        <span x-text="statusLabel"></span>
                        <span x-show="elapsed" x-text="elapsed" class="font-mono tabular-nums opacity-70"></span>
                        <span
                            x-show="process && ! isRunning && process.exit_code !== null && process.exit_code !== undefined"
                            x-text="'exit ' + process?.exit_code"
                            class="font-mono opacity-70"
                        ></span>
                    </div>

                    <p
                        class="min-w-0 flex-1 truncate font-mono text-xs text-zinc-500"
                        x-text="viewing ? viewing.command : (process?.command ?? '')"
                    ></p>

                    <div class="flex shrink-0 items-center gap-0.5">
                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-on:click="copyOutput()"
                            x-bind:disabled="isEmpty"
                            title="{{ __('mksine::console_terminal.copy_output') }}"
                        >
                            <x-filament::icon x-show="! copied" icon="heroicon-m-clipboard-document" class="size-4" />
                            <x-filament::icon x-show="copied" x-cloak icon="heroicon-m-check" class="size-4 text-emerald-400" />
                        </button>
                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-bind:class="autoScroll && 'text-primary-400! bg-primary-500/10'"
                            x-on:click="autoScroll = ! autoScroll"
                            title="{{ __('mksine::console_terminal.auto_scroll') }}"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-down-circle" class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-on:click="clear()"
                            title="{{ __('mksine::console_terminal.clear_screen') }} (Ctrl+L)"
                        >
                            <x-filament::icon icon="heroicon-m-no-symbol" class="size-4" />
                        </button>

                        <span class="mx-1 h-5 w-px bg-white/10"></span>

                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-on:click="fullscreen = ! fullscreen"
                            x-bind:title="fullscreen ? @js(__('mksine::console_terminal.exit_fullscreen')) : @js(__('mksine::console_terminal.fullscreen'))"
                        >
                            <x-filament::icon x-show="! fullscreen" icon="heroicon-m-arrows-pointing-out" class="size-4" />
                            <x-filament::icon x-show="fullscreen" x-cloak icon="heroicon-m-arrows-pointing-in" class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-bind:class="healthOpen && 'text-primary-400! bg-primary-500/10'"
                            x-on:click="healthOpen = ! healthOpen"
                            title="{{ __('mksine::console_terminal.toggle_health') }}"
                        >
                            <x-filament::icon icon="heroicon-m-signal" class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="{{ $iconButton }}"
                            x-bind:class="historyOpen && 'text-primary-400! bg-primary-500/10'"
                            x-on:click="historyOpen = ! historyOpen"
                            title="{{ __('mksine::console_terminal.toggle_history') }}"
                        >
                            <x-filament::icon icon="heroicon-m-clock" class="size-4" />
                        </button>
                    </div>
                </header>

                {{-- Viewing a saved run --}}
                <div
                    x-show="viewing"
                    x-cloak
                    class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-1 border-b border-primary-500/20 bg-primary-500/10 px-4 py-2 text-xs text-zinc-300"
                >
                    <x-filament::icon icon="heroicon-m-archive-box" class="size-4 text-primary-400" />
                    <span class="font-medium text-zinc-100" dir="auto">{{ __('mksine::console_terminal.viewing_history') }}</span>
                    <span class="font-mono text-zinc-400" x-text="viewing?.created_at"></span>
                    <span class="font-mono text-zinc-400" x-text="viewing?.duration"></span>
                    <span
                        class="rounded px-1.5 py-0.5 font-mono text-[11px]"
                        x-bind:class="viewing?.exit_code === 0 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300'"
                        x-text="'exit ' + viewing?.exit_code"
                    ></span>
                    <span class="flex-1"></span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium text-zinc-300 hover:bg-white/[0.07] hover:text-white"
                        x-on:click="useCommand(viewing.command)"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-path" class="size-3.5" />
                        {{ __('mksine::console_terminal.rerun') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md bg-primary-500/20 px-2 py-1 font-medium text-primary-200 hover:bg-primary-500/30"
                        x-on:click="backToLive()"
                    >
                        {{ __('mksine::console_terminal.back_to_live') }}
                        <kbd>Esc</kbd>
                    </button>
                </div>

                {{-- Output --}}
                <div class="relative min-h-0 flex-1">
                    <div
                        x-ref="scroller"
                        x-on:click="onOutputClick()"
                        class="mks-console__scroller absolute inset-0 overflow-auto px-4 py-3"
                    >
                        <pre x-show="! isEmpty" class="mks-console__output" x-html="renderedOutput"></pre>

                        <div x-show="isEmpty" class="flex min-h-full flex-col items-center justify-center py-8 text-center">
                            <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-primary-500/10 text-primary-400 ring-1 ring-primary-500/25">
                                <x-filament::icon icon="heroicon-o-command-line" class="size-6" />
                            </div>
                            <p class="text-sm font-semibold text-zinc-100" dir="auto">{{ __('mksine::console_terminal.welcome_title') }}</p>
                            <p class="mt-1.5 max-w-md text-xs leading-relaxed text-zinc-500" dir="auto">{{ __('mksine::console_terminal.welcome_body') }}</p>

                            <dl class="mt-6 grid grid-cols-[auto_auto] gap-x-4 gap-y-2 text-[11px] text-zinc-500">
                                <dt class="text-end"><kbd>Enter</kbd></dt>
                                <dd class="text-start" dir="auto">{{ __('mksine::console_terminal.shortcut_run') }}</dd>
                                <dt class="text-end"><kbd>Tab</kbd></dt>
                                <dd class="text-start" dir="auto">{{ __('mksine::console_terminal.shortcut_complete') }}</dd>
                                <dt class="text-end"><kbd>↑</kbd> <kbd>↓</kbd></dt>
                                <dd class="text-start" dir="auto">{{ __('mksine::console_terminal.shortcut_history') }}</dd>
                                <dt class="text-end"><kbd>Ctrl</kbd> <kbd>C</kbd></dt>
                                <dd class="text-start" dir="auto">{{ __('mksine::console_terminal.shortcut_stop') }}</dd>
                                <dt class="text-end"><kbd>Ctrl</kbd> <kbd>L</kbd></dt>
                                <dd class="text-start" dir="auto">{{ __('mksine::console_terminal.shortcut_clear') }}</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Interactive wizard (migrate:smart) --}}
                    <div
                        x-show="interactive.active"
                        x-cloak
                        x-transition.opacity
                        class="absolute inset-0 z-20 flex flex-col bg-[#0b0e14]/95 p-4 text-sm text-zinc-200 backdrop-blur-sm"
                    >
                        <div class="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col overflow-hidden rounded-xl border border-white/10 bg-white/[0.03]">
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-violet-300" dir="auto" x-text="label('interactive_title')"></p>
                                    <p class="mt-0.5 truncate font-mono text-xs text-zinc-500" x-text="interactive.command"></p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-1.5 text-xs text-zinc-400 hover:bg-white/[0.07] hover:text-zinc-100"
                                    x-on:click="cancelInteractive()"
                                    x-text="label('interactive_cancel')"
                                ></button>
                            </div>

                            <div x-show="interactive.step === 'mode'" class="space-y-2 p-4">
                                <p class="mb-3 text-xs text-zinc-500" dir="auto" x-text="interactiveSummary"></p>
                                <template x-for="option in interactiveModeOptions" :key="option.id">
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-3 rounded-lg border border-white/10 bg-black/20 px-4 py-3 text-start transition hover:border-violet-400/50 hover:bg-violet-500/10"
                                        x-on:click="chooseInteractiveMode(option.id)"
                                    >
                                        <span class="flex size-7 items-center justify-center rounded-md bg-violet-500/15 text-violet-300" x-text="option.icon"></span>
                                        <span dir="auto" x-text="option.label"></span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="interactive.step === 'select'" class="flex min-h-0 flex-1 flex-col gap-3 p-4">
                                <input
                                    type="search"
                                    x-ref="interactiveSearch"
                                    x-model="interactive.search"
                                    x-bind:placeholder="label('interactive_search_placeholder')"
                                    class="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 font-mono text-sm text-zinc-100 placeholder:text-zinc-600 focus:border-violet-400/60 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                                />
                                <div class="min-h-0 flex-1 space-y-0.5 overflow-y-auto rounded-lg border border-white/10 bg-black/20 p-1.5">
                                    <template x-for="entry in filteredInteractiveMigrations()" :key="entry.name">
                                        <label class="flex cursor-pointer items-start gap-3 rounded-md px-3 py-2 hover:bg-white/[0.04]">
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-zinc-600 bg-transparent text-violet-500 focus:ring-violet-500"
                                                :value="entry.name"
                                                x-model="interactive.selected"
                                                x-show="interactive.mode !== 'single'"
                                            />
                                            <input
                                                type="radio"
                                                name="mks-console-interactive-migration"
                                                class="mt-0.5 border-zinc-600 bg-transparent text-violet-500 focus:ring-violet-500"
                                                :value="entry.name"
                                                x-model="interactive.singleSelected"
                                                x-show="interactive.mode === 'single'"
                                            />
                                            <span
                                                class="mt-px shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                                x-bind:class="entry.executed ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300'"
                                                x-text="migrationStatusLabel(entry)"
                                            ></span>
                                            <span class="min-w-0 break-all font-mono text-xs leading-relaxed text-zinc-300">
                                                <span class="text-zinc-500" x-text="'[' + entry.source_label + ']'"></span>
                                                <span x-text="entry.name"></span>
                                            </span>
                                        </label>
                                    </template>
                                    <p
                                        x-show="filteredInteractiveMigrations().length === 0"
                                        class="px-3 py-6 text-center text-xs text-zinc-500"
                                        x-text="label('interactive_no_migrations')"
                                    ></p>
                                </div>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg px-4 py-2 text-xs text-zinc-400 hover:bg-white/[0.07] hover:text-zinc-100"
                                        x-on:click="interactive.step = 'mode'"
                                        x-text="label('interactive_back')"
                                    ></button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-violet-600 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-500 disabled:opacity-40"
                                        x-on:click="previewInteractive()"
                                        x-bind:disabled="selectedMigrationNames().length === 0 || loading"
                                        x-text="label('interactive_continue')"
                                    ></button>
                                </div>
                            </div>

                            <div x-show="interactive.step === 'preview'" class="flex min-h-0 flex-1 flex-col gap-3 p-4">
                                <pre
                                    class="mks-console__output min-h-0 flex-1 overflow-y-auto rounded-lg border border-white/10 bg-black/30 p-3"
                                    x-text="interactive.previewText"
                                ></pre>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg px-4 py-2 text-xs text-zinc-400 hover:bg-white/[0.07] hover:text-zinc-100"
                                        x-on:click="interactive.step = interactive.mode === 'all' ? 'mode' : 'select'"
                                        x-text="label('interactive_back')"
                                    ></button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-amber-400/40 px-4 py-2 text-xs text-amber-200 hover:bg-amber-500/10 disabled:opacity-40"
                                        x-on:click="executeInteractive(true)"
                                        x-bind:disabled="loading"
                                        x-text="label('interactive_dry_run')"
                                    ></button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-40"
                                        x-on:click="executeInteractive(false)"
                                        x-bind:disabled="loading"
                                        x-text="interactive.requiresProductionConfirm ? label('interactive_confirm_production') : label('interactive_execute')"
                                    ></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Prompt --}}
                <footer class="shrink-0 border-t border-white/[0.06] bg-black/25">
                    <div
                        x-show="(config.quickCommands ?? []).length"
                        class="mks-console__chips flex items-center gap-1.5 overflow-x-auto px-3 pb-1 pt-2.5"
                    >
                        <template x-for="chip in config.quickCommands" :key="chip.command">
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-white/[0.08] bg-white/[0.03] px-2 py-1 font-mono text-[11px] text-zinc-400 transition hover:border-primary-500/40 hover:bg-primary-500/10 hover:text-zinc-100"
                                x-on:click="useCommand(chip.command)"
                                x-bind:title="chip.command"
                            >
                                <span
                                    x-show="chip.long_running"
                                    class="size-1.5 rounded-full bg-amber-400"
                                    title="{{ __('mksine::console_terminal.long_running') }}"
                                ></span>
                                <span x-text="chip.label"></span>
                            </button>
                        </template>
                    </div>

                    <div class="relative px-3 pb-3 pt-1.5">
                        {{-- Autocomplete --}}
                        <div
                            x-show="suggestionsOpen"
                            x-cloak
                            class="absolute inset-x-3 bottom-full z-30 mb-1 overflow-hidden rounded-lg border border-white/10 bg-[#151922] shadow-2xl shadow-black/50"
                        >
                            <template x-for="(item, index) in matchingSuggestions" :key="item.value">
                                <button
                                    type="button"
                                    class="flex w-full items-baseline gap-3 px-3 py-1.5 text-start"
                                    x-bind:class="index === suggestionIndex ? 'bg-primary-500/15 text-white' : 'text-zinc-300 hover:bg-white/[0.04]'"
                                    x-on:mousedown.prevent="acceptSuggestion(index)"
                                    x-on:mouseenter="suggestionIndex = index"
                                >
                                    <span class="shrink-0 font-mono text-xs" x-text="item.value"></span>
                                    <span class="min-w-0 truncate text-[11px] text-zinc-500" x-text="item.description"></span>
                                </button>
                            </template>
                            <div class="border-t border-white/[0.06] px-3 py-1.5 text-[10px] text-zinc-500">
                                <kbd>Tab</kbd> {{ __('mksine::console_terminal.shortcut_complete') }}
                                · <kbd>↑</kbd> <kbd>↓</kbd>
                                · <kbd>Esc</kbd>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 rounded-lg border border-white/10 bg-black/30 ps-3 pe-1.5 transition focus-within:border-primary-500/60 focus-within:ring-2 focus-within:ring-primary-500/20">
                            <span class="select-none font-mono text-sm font-semibold text-primary-400" aria-hidden="true">❯</span>
                            <input
                                x-ref="input"
                                type="text"
                                x-model="input"
                                x-on:input="onInput()"
                                x-on:keydown="onKeydown($event)"
                                x-on:focus="focused = true"
                                x-on:blur="focused = false"
                                x-bind:disabled="interactive.active"
                                dir="ltr"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                aria-label="{{ __('mksine::console_terminal.input_label') }}"
                                placeholder="{{ __('mksine::console_terminal.input_placeholder') }}"
                                class="h-11 min-w-0 flex-1 border-0 bg-transparent p-0 font-mono text-sm text-zinc-100 shadow-none outline-none placeholder:text-zinc-600 focus:outline-none focus:ring-0 disabled:opacity-50"
                            />

                            <svg x-show="loading" x-cloak class="size-4 animate-spin text-zinc-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                            </svg>

                            <button
                                x-show="! canStop"
                                type="button"
                                class="inline-flex h-8 items-center gap-2 rounded-md bg-primary-600 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-40 disabled:hover:bg-primary-600"
                                x-on:click="run()"
                                x-bind:disabled="! canStart || input.trim() === ''"
                            >
                                <x-filament::icon icon="heroicon-m-play" class="size-3.5" />
                                {{ __('mksine::console_terminal.run') }}
                            </button>
                            <button
                                x-show="canStop"
                                x-cloak
                                type="button"
                                class="inline-flex h-8 items-center gap-2 rounded-md px-3 text-xs font-medium text-zinc-300 ring-1 ring-white/15 transition hover:bg-white/[0.07] hover:text-white"
                                x-on:click="detach()"
                                title="{{ __('mksine::console_terminal.detach_hint') }}"
                            >
                                <x-filament::icon icon="heroicon-m-arrow-down-on-square" class="size-3.5" />
                                {{ __('mksine::console_terminal.detach') }}
                            </button>
                            <button
                                x-show="canStop"
                                x-cloak
                                type="button"
                                class="inline-flex h-8 items-center gap-2 rounded-md bg-red-600 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:opacity-40"
                                x-on:click="stop()"
                                x-bind:disabled="loading"
                            >
                                <x-filament::icon icon="heroicon-m-stop" class="size-3.5" />
                                {{ __('mksine::console_terminal.stop') }}
                            </button>
                        </div>
                    </div>
                </footer>
            </section>

            {{-- ───────────────────────── History ───────────────────────── --}}
            <aside
                x-show="historyOpen"
                x-cloak
                class="mks-console__history flex w-full shrink-0 flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 lg:w-80 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <x-filament::icon icon="heroicon-m-clock" class="size-4 text-gray-400 dark:text-gray-500" />
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('mksine::console_terminal.history_heading') }}
                    </h2>
                    @if ($logs->isNotEmpty())
                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $logs->count() }}
                        </span>
                    @endif
                    <span class="flex-1"></span>
                    <button
                        type="button"
                        class="inline-flex size-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-200"
                        x-on:click="historyOpen = false"
                        title="{{ __('mksine::console_terminal.toggle_history') }}"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="size-4" />
                    </button>
                </div>

                @if ($logs->isNotEmpty())
                    <div class="px-3 pt-3">
                        <div class="relative">
                            <x-filament::icon
                                icon="heroicon-m-magnifying-glass"
                                class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-gray-400"
                            />
                            <input
                                type="search"
                                x-model="historySearch"
                                placeholder="{{ __('mksine::console_terminal.history_search') }}"
                                class="block w-full rounded-lg border-0 bg-gray-50 py-1.5 pe-3 ps-8 text-sm text-gray-950 ring-1 ring-gray-950/10 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-white dark:ring-white/10"
                            />
                        </div>
                    </div>
                @endif

                <ul class="min-h-0 flex-1 space-y-0.5 overflow-y-auto p-2">
                    @forelse ($logs as $log)
                        @php($succeeded = (int) $log->exit_code === 0)
                        <li wire:key="mks-console-log-{{ $log->id }}" x-show="matchesHistory(@js($log->command))">
                            <div
                                role="button"
                                tabindex="0"
                                class="group flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                x-bind:class="viewing?.id === {{ $log->id }}
                                    ? 'bg-primary-50 ring-1 ring-primary-600/20 dark:bg-primary-500/10 dark:ring-primary-400/30'
                                    : 'hover:bg-gray-50 dark:hover:bg-white/5'"
                                x-on:click="showLog({{ $log->id }})"
                                x-on:keydown.enter.prevent="showLog({{ $log->id }})"
                            >
                                <span @class([
                                    'mt-1.5 size-2 shrink-0 rounded-full',
                                    'bg-success-500' => $succeeded,
                                    'bg-danger-500' => ! $succeeded,
                                ])></span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-start font-mono text-xs font-medium text-gray-900 dark:text-gray-100" dir="ltr" title="{{ $log->command }}">
                                        {{ preg_replace('/^php\s+artisan\s+/i', '', $log->command) }}
                                    </p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        <span>{{ $log->created_at?->diffForHumans() }}</span>
                                        @if ($log->duration_ms !== null)
                                            <span aria-hidden="true">·</span>
                                            <span dir="ltr">{{ \Miran\Mksine\Filament\Pages\ConsoleTerminal::formatDuration($log->duration_ms) }}</span>
                                        @endif
                                        @unless ($succeeded)
                                            <span aria-hidden="true">·</span>
                                            <span class="font-mono text-danger-600 dark:text-danger-400" dir="ltr">exit {{ $log->exit_code }}</span>
                                        @endunless
                                        @if ($log->user)
                                            <span aria-hidden="true">·</span>
                                            <span class="truncate">{{ $log->user->name }}</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-0.5 transition lg:opacity-0 lg:group-hover:opacity-100 lg:group-focus-within:opacity-100">
                                    <button
                                        type="button"
                                        class="inline-flex size-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-200/70 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                        x-on:click.stop="useCommand(@js($log->command))"
                                        title="{{ __('mksine::console_terminal.rerun') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-arrow-path" class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-7 items-center justify-center rounded-md text-gray-400 hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                        x-on:click.stop="deleteLog({{ $log->id }})"
                                        title="{{ __('mksine::console_terminal.delete') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-trash" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="flex flex-col items-center px-4 py-12 text-center">
                            <x-filament::icon icon="heroicon-o-clock" class="mb-3 size-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('mksine::console_terminal.no_logs') }}</p>
                        </li>
                    @endforelse
                </ul>
            </aside>
        </div>
    </div>

    @push('styles')
        <style>
            .mks-console {
                min-height: max(var(--mks-console-min-height), calc(100dvh - 12.5rem));
            }

            .mks-console__layout {
                flex: 1 1 0;
                min-height: var(--mks-console-min-height);
            }

            .mks-console__terminal {
                background: #0b0e14;
                color: #d4d4d8;
                color-scheme: dark;
            }

            .mks-console__output {
                margin: 0;
                font-family: ui-monospace, 'JetBrains Mono', 'SF Mono', SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;
                font-size: 12.5px;
                line-height: 1.65;
                color: #d4d4d8;
                white-space: pre-wrap;
                word-break: break-word;
                tab-size: 4;
                text-align: left;
                unicode-bidi: plaintext;
            }

            .mks-console__output ::selection,
            .mks-console__output::selection {
                background: rgb(99 102 241 / 0.4);
            }

            .mks-console kbd {
                display: inline-flex;
                min-width: 1.25rem;
                align-items: center;
                justify-content: center;
                border-radius: 0.25rem;
                border: 1px solid rgb(255 255 255 / 0.12);
                border-bottom-width: 2px;
                background: rgb(255 255 255 / 0.04);
                padding: 0 0.3rem;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 10px;
                line-height: 1.1rem;
                color: #a1a1aa;
            }

            .mks-console__scroller,
            .mks-console__chips,
            .mks-console__terminal [class*='overflow-y-auto'] {
                scrollbar-width: thin;
                scrollbar-color: rgb(255 255 255 / 0.14) transparent;
            }

            .mks-console__chips {
                scrollbar-width: none;
            }

            .mks-console--fullscreen {
                position: fixed;
                inset: 0;
                z-index: 60;
                padding: 0.75rem;
                background: rgb(3 7 18 / 0.8);
                backdrop-filter: blur(4px);
            }

            .mks-console--fullscreen {
                min-height: 0;
            }

            html.mks-console-fullscreen-lock {
                overflow: hidden;
            }

            @media (max-width: 1023px) {
                .mks-console {
                    min-height: 0;
                }

                .mks-console__layout {
                    flex: none;
                    min-height: 0;
                }

                .mks-console__terminal {
                    height: max(var(--mks-console-min-height), calc(100dvh - 14rem));
                    flex: none;
                }

                .mks-console__history {
                    max-height: 26rem;
                }

                .mks-console--fullscreen .mks-console__terminal {
                    height: 100%;
                }

                .mks-console--fullscreen .mks-console__history {
                    display: none !important;
                }
            }
        </style>
    @endpush
</x-filament-panels::page>
