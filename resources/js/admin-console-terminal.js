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

        init() {
            this.fetchActive();
        },

        get statusText() {
            if (!this.process) {
                return config.labels.idle;
            }

            if (this.process.alive && this.process.pid) {
                return `${config.labels.running} (PID: ${this.process.pid})`;
            }

            return this.process.status_label ?? this.process.status;
        },

        get statusClass() {
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
            return !this.loading && (!this.process || !this.process.alive);
        },

        get canStop() {
            return this.process?.alive === true;
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

        async start() {
            const command = this.commandValue();
            if (!command) {
                return;
            }

            this.loading = true;
            this.daemonMode = true;
            this.output = '';
            this.streamOffset = 0;
            this.stopOutputPolling();

            try {
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
                    return;
                }

                this.attachProcess(data);
            } catch (error) {
                this.output = `Error: ${error.message}`;
            } finally {
                this.loading = false;
            }
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
