document.addEventListener('alpine:init', () => {
    Alpine.data('mksAdminConsole', (config) => ({
        config,
        output: '',
        streamOffset: 0,
        process: null,
        statusTimer: null,
        outputTimer: null,
        autoScroll: true,
        daemonMode: false,
        loading: false,
        interactive: {
            active: false,
            command: '',
            handler: null,
            step: 'mode',
            mode: null,
            migrations: [],
            search: '',
            selected: [],
            singleSelected: '',
            previewText: '',
            requiresProductionConfirm: false,
            saveLog: false,
        },

        get interactiveModeOptions() {
            const labels = config.labels ?? {};

            return [
                { id: 'all', label: labels.interactiveModeAll ?? 'Run all' },
                { id: 'search', label: labels.interactiveModeSearch ?? 'Search and select' },
                { id: 'single', label: labels.interactiveModeSingle ?? 'Pick one' },
            ];
        },

        init() {
            this.fetchActive();
        },

        get statusText() {
            if (this.interactive.active) {
                return config.labels?.interactiveTitle ?? 'Interactive';
            }

            if (!this.process) {
                return config.labels.idle;
            }

            if (this.process.alive && this.process.pid) {
                return `${config.labels.running} (PID: ${this.process.pid})`;
            }

            return this.process.status_label ?? this.process.status;
        },

        get statusClass() {
            if (this.interactive.active) {
                return 'text-violet-400';
            }

            if (!this.process) {
                return 'text-gray-400';
            }

            if (this.process.alive) {
                return 'text-emerald-400';
            }

            if (this.process.status === 'failed') {
                return 'text-red-400';
            }

            if (this.process.status === 'stopped') {
                return 'text-amber-400';
            }

            return 'text-gray-400';
        },

        get canStart() {
            return !this.loading && !this.interactive.active && (!this.process || !this.process.alive);
        },

        get canStop() {
            return this.process?.alive === true && !this.interactive.active;
        },

        async fetchActive() {
            try {
                const response = await fetch(config.activeUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const running = (data.processes ?? []).find((item) => item.alive);

                if (running) {
                    this.daemonMode = true;
                    await this.loadOutputSnapshot(running.output_url);
                    this.attachProcess(running);
                }
            } catch {
                // ignore reconnect errors
            }
        },

        async loadOutputSnapshot(outputUrl) {
            if (!outputUrl) {
                return;
            }

            try {
                const url = new URL(outputUrl, window.location.origin);
                url.searchParams.set('offset', '0');
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                if (data.chunk) {
                    this.output = data.chunk;
                    this.streamOffset = data.offset ?? 0;
                }
            } catch {
                // ignore
            }
        },

        commandValue() {
            return (this.$wire?.command ?? '').trim();
        },

        async detectInteractive(command) {
            const response = await fetch(config.interactive.detectUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ command }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));

                throw new Error(data.message ?? 'Failed to detect command mode.');
            }

            return response.json();
        },

        async start(options = {}) {
            const command = this.commandValue();
            if (!command) {
                return;
            }

            const saveLog = options.saveLog === true;

            this.loading = true;

            try {
                const detection = await this.detectInteractive(command);

                if (detection.interactive) {
                    await this.beginInteractive(command, detection.handler, saveLog);

                    return;
                }

                if (saveLog) {
                    this.daemonMode = false;
                    await this.$wire.runCommand();

                    return;
                }

                this.daemonMode = true;
                this.output = '';
                this.streamOffset = 0;
                this.stopOutputPolling();

                const response = await fetch(config.startUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ command }),
                });

                const data = await response.json();
                if (!response.ok) {
                    this.output = data.message ?? 'Failed to start process.';
                    this.daemonMode = true;

                    return;
                }

                this.attachProcess(data);
            } catch (error) {
                this.output = `Error: ${error.message}`;
                this.daemonMode = true;
            } finally {
                this.loading = false;
            }
        },

        async beginInteractive(command, handler, saveLog) {
            this.daemonMode = true;
            this.output = `[${new Date().toISOString().slice(0, 19).replace('T', ' ')}] ${command}\n${'─'.repeat(48)}\n`;
            this.interactive = {
                active: true,
                command,
                handler,
                step: 'mode',
                mode: null,
                migrations: [],
                search: '',
                selected: [],
                singleSelected: '',
                previewText: '',
                requiresProductionConfirm: false,
                saveLog,
            };

            if (handler !== 'migrate:smart') {
                this.output += '\nUnsupported interactive handler.\n';
                this.cancelInteractive();

                return;
            }

            const response = await fetch(config.interactive.migrateSmartCatalogUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to load migrations catalog.');
            }

            const data = await response.json();
            this.interactive.migrations = data.migrations ?? [];
            this.interactive.executedCount = data.executed_count ?? 0;
            this.interactive.pendingCount = data.pending_count ?? 0;
            this.interactive.totalCount = data.count ?? this.interactive.migrations.length;

            if ((data.count ?? 0) === 0) {
                this.output += `\n${config.labels.interactiveNoMigrations}\n`;
                this.cancelInteractive();
            }
        },

        chooseInteractiveMode(mode) {
            this.interactive.mode = mode;
            this.interactive.selected = [];
            this.interactive.singleSelected = '';

            if (mode === 'all') {
                this.interactive.selected = this.interactive.migrations
                    .filter((entry) => entry.executed)
                    .map((entry) => entry.name);
                this.previewInteractive();

                return;
            }

            this.interactive.step = 'select';
        },

        selectedMigrationNames() {
            if (this.interactive.mode === 'single') {
                return this.interactive.singleSelected ? [this.interactive.singleSelected] : [];
            }

            return [...this.interactive.selected];
        },

        migrationStatusLabel(entry) {
            const labels = this.config.labels ?? {};

            if (entry?.executed) {
                return labels.interactiveStatusRan ?? 'ran';
            }

            return labels.interactiveStatusPending ?? 'pending';
        },

        filteredInteractiveMigrations() {
            const needle = (this.interactive.search ?? '').trim().toLowerCase();

            return (this.interactive.migrations ?? []).filter((entry) => {
                if (!needle) {
                    return true;
                }

                return `${entry.label} ${entry.name} ${entry.source_label}`.toLowerCase().includes(needle);
            });
        },

        async previewInteractive() {
            const migrations = this.selectedMigrationNames();

            if (migrations.length === 0) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(config.interactive.migrateSmartAnalyzeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ migrations }),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message ?? 'Analysis failed.');
                }

                const lines = [];
                (data.notices ?? []).forEach((line) => lines.push(line));
                (data.warnings ?? []).forEach((line) => lines.push(line));

                if ((data.actions ?? []).length === 0 && (data.pending_runs ?? []).length === 0) {
                    lines.push(config.labels.interactiveNothingToSync ?? 'Nothing to synchronize.');
                } else {
                    lines.push('The following changes will be applied:');
                    (data.pending_runs ?? []).forEach((run) => lines.push(`+ ${run.label}`));
                    (data.actions ?? []).forEach((action) => lines.push(`+ ${action.label}`));
                }

                this.interactive.previewText = lines.join('\n');
                this.interactive.step = 'preview';
            } catch (error) {
                this.output += `\nError: ${error.message}\n`;
            } finally {
                this.loading = false;
            }
        },

        async executeInteractive(dryRun) {
            const migrations = this.selectedMigrationNames();

            if (migrations.length === 0) {
                return;
            }

            this.loading = true;
            const started = performance.now();

            try {
                const force = this.interactive.requiresProductionConfirm;
                const response = await fetch(config.interactive.migrateSmartExecuteUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        migrations,
                        dry_run: dryRun,
                        force,
                    }),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message ?? 'Execution failed.');
                }

                if (data.requires_confirmation) {
                    this.interactive.requiresProductionConfirm = true;
                    this.interactive.previewText = (data.lines ?? []).join('\n');
                    this.interactive.step = 'preview';

                    return;
                }

                const lines = data.lines ?? [];
                const text = lines.join('\n');
                this.output += `\n${text}\n`;
                const exitCode = data.exit_code ?? 0;
                const durationMs = Math.round(performance.now() - started);

                if (this.interactive.saveLog) {
                    await this.storeInteractiveLog(this.interactive.command, text, exitCode, durationMs);
                }

                this.cancelInteractive();
                this.scrollToBottom();
            } catch (error) {
                this.output += `\nError: ${error.message}\n`;
            } finally {
                this.loading = false;
            }
        },

        async storeInteractiveLog(command, outputText, exitCode, durationMs) {
            await fetch(config.interactive.logUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    command,
                    output: outputText,
                    exit_code: exitCode,
                    duration_ms: durationMs,
                }),
            });

            if (this.$wire?.$refresh) {
                await this.$wire.$refresh();
            }
        },

        cancelInteractive() {
            this.interactive.active = false;
            this.interactive.step = 'mode';
            this.interactive.requiresProductionConfirm = false;
        },

        attachProcess(process) {
            this.process = process;
            this.startOutputPolling();
            this.startStatusPolling();
        },

        startOutputPolling() {
            this.stopOutputPolling();

            const tick = async () => {
                if (!this.process?.output_url) {
                    return;
                }

                try {
                    const url = new URL(this.process.output_url, window.location.origin);
                    url.searchParams.set('offset', String(this.streamOffset));

                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    if (data.chunk) {
                        this.output += data.chunk;
                        this.streamOffset = data.offset ?? this.streamOffset;
                        this.scrollToBottom();
                    }

                    if (typeof data.alive === 'boolean') {
                        this.process = { ...this.process, alive: data.alive, status: data.status };
                    }

                    if (data.finished && !data.alive) {
                        this.stopOutputPolling();
                        await this.refreshStatus();
                    }
                } catch {
                    // ignore transient poll errors
                }
            };

            tick();
            this.outputTimer = window.setInterval(tick, 400);
        },

        stopOutputPolling() {
            if (this.outputTimer) {
                clearInterval(this.outputTimer);
                this.outputTimer = null;
            }
        },

        startStatusPolling() {
            this.stopStatusPolling();
            this.statusTimer = window.setInterval(() => this.refreshStatus(), config.pollIntervalMs ?? 2000);
        },

        stopStatusPolling() {
            if (this.statusTimer) {
                clearInterval(this.statusTimer);
                this.statusTimer = null;
            }
        },

        async refreshStatus() {
            if (!this.process?.status_url) {
                return;
            }

            try {
                const response = await fetch(this.process.status_url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                this.process = { ...this.process, ...data };

                if (!data.alive) {
                    this.stopStatusPolling();
                }
            } catch {
                // ignore transient poll errors
            }
        },

        async stop() {
            if (!this.process?.stop_url) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(this.process.stop_url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json();
                this.process = { ...this.process, ...data };

                const url = new URL(this.process.output_url, window.location.origin);
                url.searchParams.set('offset', String(this.streamOffset));
                const tail = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (tail.ok) {
                    const tailData = await tail.json();
                    if (tailData.chunk) {
                        this.output += tailData.chunk;
                        this.streamOffset = tailData.offset ?? this.streamOffset;
                    }
                }
            } catch (error) {
                this.output += `\nError stopping: ${error.message}\n`;
            } finally {
                this.loading = false;
                this.stopOutputPolling();
                this.stopStatusPolling();
                await this.refreshStatus();
            }
        },

        clear() {
            if (this.process?.alive) {
                return;
            }

            this.output = '';
            this.streamOffset = 0;
            this.process = null;
            this.daemonMode = false;
            this.cancelInteractive();
            this.stopOutputPolling();
            this.stopStatusPolling();
        },

        scrollToBottom() {
            if (!this.autoScroll) {
                return;
            }

            this.$nextTick(() => {
                const el = this.$refs.output;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },

        destroy() {
            this.stopOutputPolling();
            this.stopStatusPolling();
        },
    }));
});
