<x-filament-panels::page>
    @php
        $terminalHeight = (int) config('mksine.console_terminal.default_output_height_px', 500);
    @endphp

    <div
        x-data="mksAdminConsole(@js($this->consoleApi))"
        x-on:destroy.window="destroy()"
        class="mks-console-terminal space-y-3"
        dir="ltr"
    >
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('mksine::console_terminal.status_label') }}</span>
                <span class="font-mono text-sm" :class="statusClass" x-text="statusText"></span>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <input type="checkbox" x-model="autoScroll" class="rounded border-gray-400 text-violet-600 focus:ring-violet-500" />
                {{ __('mksine::console_terminal.auto_scroll') }}
            </label>
        </div>

        <div
            class="mks-console-terminal__grid grid min-h-0 gap-4 max-lg:grid-rows-[minmax(18rem,52vh)_minmax(10rem,28vh)] lg:grid-cols-[minmax(0,1fr)_min(280px,300px)] lg:grid-rows-1"
            style="height: {{ $terminalHeight }}px; max-height: calc(100dvh - 14rem);"
        >
            <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-gray-950 shadow-xl ring-1 ring-black/5 dark:border-gray-800">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-gradient-to-r from-violet-600/90 via-fuchsia-600/80 to-amber-500/70 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-3 w-3 rounded-full bg-red-400/90"></span>
                        <span class="inline-flex h-3 w-3 rounded-full bg-amber-300/90"></span>
                        <span class="inline-flex h-3 w-3 rounded-full bg-emerald-400/90"></span>
                    </div>
                    <p class="truncate text-xs font-medium tracking-wide text-white/90">
                        {{ __('mksine::console_terminal.terminal_title') }}
                    </p>
                    <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white/80">
                        {{ __('mksine::console_terminal.super_admin_only') }}
                    </span>
                </div>

                <div class="relative min-h-0 flex-1 overflow-hidden">
                    <pre
                        x-show="daemonMode"
                        x-ref="output"
                        x-text="output"
                        class="mks-console-terminal__output absolute inset-0 m-0 overflow-auto px-4 py-4 font-mono text-left text-[12px] leading-relaxed text-emerald-300/95 whitespace-pre-wrap break-words"
                    ></pre>
                    <pre
                        x-show="!daemonMode"
                        wire:key="terminal-output-{{ $selectedLogId ?? 'welcome' }}"
                        class="mks-console-terminal__output absolute inset-0 m-0 overflow-auto px-4 py-4 font-mono text-left text-[12px] leading-relaxed text-emerald-300/95 whitespace-pre-wrap break-words"
                    >{{ $terminalOutput }}</pre>
                    @if ($isRunning)
                        <div class="absolute inset-0 z-10 flex items-center justify-center bg-gray-950/85 text-sm font-medium text-amber-300">
                            <span class="animate-pulse">{{ __('mksine::console_terminal.running') }}</span>
                        </div>
                    @endif
                    <div
                        x-show="loading && daemonMode"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-gray-950/85 text-sm font-medium text-amber-300"
                    >
                        <span class="animate-pulse">{{ __('mksine::console_terminal.running') }}</span>
                    </div>
                </div>

                <div class="shrink-0 border-t border-white/10 bg-gray-900/90 p-4">
                    <div class="flex flex-wrap items-stretch gap-2">
                        <div class="flex shrink-0 items-center rounded-xl bg-black/40 px-3 text-sm font-semibold text-violet-300" aria-hidden="true">
                            &gt;
                        </div>
                        <input
                            type="text"
                            wire:model.live="command"
                            dir="ltr"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="{{ __('mksine::console_terminal.input_placeholder') }}"
                            class="min-w-0 flex-1 rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-left font-mono text-sm text-white placeholder:text-gray-500 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/40 disabled:opacity-50"
                        />
                        <x-filament::button
                            type="button"
                            color="success"
                            icon="heroicon-m-play"
                            x-on:click="start()"
                            x-bind:disabled="!canStart"
                        >
                            {{ __('mksine::console_terminal.start') }}
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            color="danger"
                            icon="heroicon-m-stop"
                            x-on:click="stop()"
                            x-bind:disabled="!canStop"
                        >
                            {{ __('mksine::console_terminal.stop') }}
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            color="gray"
                            icon="heroicon-m-trash"
                            x-on:click="clear()"
                        >
                            {{ __('mksine::console_terminal.clear_output') }}
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            color="primary"
                            icon="heroicon-m-bolt"
                            wire:click="runCommand"
                            wire:loading.attr="disabled"
                            wire:target="runCommand"
                            x-on:click="daemonMode = false"
                        >
                            {{ __('mksine::console_terminal.run_once') }}
                        </x-filament::button>
                    </div>
                    <p class="mt-2 text-left text-xs text-gray-500">
                        {{ __('mksine::console_terminal.hint_daemon') }}
                    </p>
                </div>
            </div>

            <aside class="flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="shrink-0 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ __('mksine::console_terminal.history_heading') }}
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('mksine::console_terminal.history_subheading') }}
                    </p>
                </div>

                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-3">
                    @forelse ($this->logs as $log)
                        <div
                            @class([
                                'group rounded-xl border p-3 transition',
                                'border-violet-300 bg-violet-50 dark:border-violet-500/40 dark:bg-violet-500/10' => $selectedLogId === $log->id,
                                'border-gray-200 bg-gray-50 hover:border-gray-300 dark:border-gray-800 dark:bg-gray-950/40 dark:hover:border-gray-700' => $selectedLogId !== $log->id,
                            ])
                        >
                            <button
                                type="button"
                                wire:click="selectLog({{ $log->id }})"
                                x-on:click="daemonMode = false"
                                class="w-full text-left"
                            >
                                <div class="flex items-center justify-between gap-2 text-left">
                                    <span @class([
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-violet-200 text-violet-800 dark:bg-violet-500/30 dark:text-violet-200' => $log->runner === 'artisan',
                                        'bg-amber-200 text-amber-900 dark:bg-amber-500/30 dark:text-amber-100' => $log->runner === 'composer',
                                    ])>
                                        {{ $log->runner }}
                                    </span>
                                    <span @class([
                                        'text-[10px] font-medium',
                                        'text-emerald-600 dark:text-emerald-400' => (int) $log->exit_code === 0,
                                        'text-red-600 dark:text-red-400' => (int) $log->exit_code !== 0,
                                    ])>
                                        exit {{ $log->exit_code }}
                                    </span>
                                </div>
                                <p class="mt-2 line-clamp-2 text-left font-mono text-xs text-gray-800 dark:text-gray-200" dir="ltr">
                                    {{ $log->command }}
                                </p>
                                <p class="mt-1 text-left text-[10px] text-gray-500">
                                    {{ $log->created_at?->diffForHumans() }}
                                    @if ($log->user)
                                        · {{ $log->user->name }}
                                    @endif
                                </p>
                            </button>
                            <div class="mt-2 flex justify-end lg:opacity-0 lg:transition lg:group-hover:opacity-100">
                                <x-filament::button
                                    size="xs"
                                    color="danger"
                                    icon="heroicon-m-trash"
                                    wire:click="deleteLog({{ $log->id }})"
                                    wire:confirm="{{ __('mksine::console_terminal.delete_log_confirm') }}"
                                >
                                    {{ __('mksine::console_terminal.delete') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700">
                            {{ __('mksine::console_terminal.no_logs') }}
                        </p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

    @push('styles')
        <style>
            .mks-console-terminal {
                direction: ltr;
                text-align: left;
            }

            .mks-console-terminal__output {
                unicode-bidi: plaintext;
            }

            .fi-main:has(.mks-console-terminal) {
                overflow: clip;
            }
        </style>
    @endpush

</x-filament-panels::page>
