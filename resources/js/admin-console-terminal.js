/**
 * MKSine admin console (Filament page `ConsoleTerminal`).
 *
 * Every command runs as a detached process with live output; finished runs land in the
 * history panel (rendered by Livewire). Output is rendered with ANSI colors.
 */
(() => {
    const MAX_BUFFER_CHARS = 1_500_000;
    const TRIM_BUFFER_TO = 1_000_000;
    const MAX_SUGGESTIONS = 8;
    const HISTORY_OPEN_KEY = 'mksConsoleHistoryOpen';
    const AUTOSCROLL_KEY = 'mksConsoleAutoScroll';
    const HEALTH_OPEN_KEY = 'mksConsoleHealthOpen';
    const DETACHED_KEY = 'mksConsoleDetachedProcesses';

    const ANSI_16 = [
        '#3f3f46', '#f87171', '#4ade80', '#facc15', '#60a5fa', '#c084fc', '#22d3ee', '#d4d4d8',
        '#71717a', '#fca5a5', '#86efac', '#fde047', '#93c5fd', '#d8b4fe', '#67e8f9', '#fafafa',
    ];

    const escapeHtml = (value) =>
        value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    const color256 = (index) => {
        if (index < 16) {
            return ANSI_16[index];
        }

        if (index >= 232) {
            const level = 8 + (index - 232) * 10;

            return `rgb(${level},${level},${level})`;
        }

        const offset = index - 16;
        const steps = [0, 95, 135, 175, 215, 255];

        return `rgb(${steps[Math.floor(offset / 36)]},${steps[Math.floor(offset / 6) % 6]},${steps[offset % 6]})`;
    };

    /** Collapses carriage-return overwrites (progress bars) and strips non-SGR escape sequences. */
    const normalizeTerminalText = (text) =>
        text
            .replace(/\r\n/g, '\n')
            .replace(/\x1b\][^\x07\x1b]*(?:\x07|\x1b\\)/g, '')
            .replace(/\x1b\[[0-9;?]*$/, '')
            .split('\n')
            .map((line) => {
                if (!line.includes('\r')) {
                    return line;
                }

                const parts = line.split('\r').filter((part) => part !== '');

                return parts.length ? parts[parts.length - 1] : '';
            })
            .join('\n');

    const stripAnsi = (text) => normalizeTerminalText(text).replace(/\x1b\[[0-9;?]*[A-Za-z]/g, '');

    const renderAnsi = (text) => {
        const source = normalizeTerminalText(text);
        const pattern = /\x1b\[([0-9;?]*)([A-Za-z])/g;
        const state = { fg: null, bg: null, bold: false, dim: false, italic: false, underline: false };
        let html = '';
        let cursor = 0;
        let match;

        const emit = (segment) => {
            if (segment === '') {
                return;
            }

            const cleaned = escapeHtml(segment.replace(/[\x00-\x08\x0b-\x1f\x7f]/g, ''));
            const styles = [];

            if (state.fg) styles.push(`color:${state.fg}`);
            if (state.bg) styles.push(`background-color:${state.bg}`);
            if (state.bold) styles.push('font-weight:600');
            if (state.dim) styles.push('opacity:.6');
            if (state.italic) styles.push('font-style:italic');
            if (state.underline) styles.push('text-decoration:underline');

            html += styles.length ? `<span style="${styles.join(';')}">${cleaned}</span>` : cleaned;
        };

        const applySgr = (params) => {
            const codes = params === '' ? [0] : params.split(';').map((code) => parseInt(code || '0', 10));

            for (let i = 0; i < codes.length; i++) {
                const code = codes[i];

                if (code === 0) {
                    Object.assign(state, { fg: null, bg: null, bold: false, dim: false, italic: false, underline: false });
                } else if (code === 1) state.bold = true;
                else if (code === 2) state.dim = true;
                else if (code === 3) state.italic = true;
                else if (code === 4) state.underline = true;
                else if (code === 22) state.bold = state.dim = false;
                else if (code === 23) state.italic = false;
                else if (code === 24) state.underline = false;
                else if (code >= 30 && code <= 37) state.fg = ANSI_16[code - 30];
                else if (code >= 90 && code <= 97) state.fg = ANSI_16[code - 90 + 8];
                else if (code === 39) state.fg = null;
                else if (code >= 40 && code <= 47) state.bg = ANSI_16[code - 40];
                else if (code >= 100 && code <= 107) state.bg = ANSI_16[code - 100 + 8];
                else if (code === 49) state.bg = null;
                else if (code === 38 || code === 48) {
                    const key = code === 38 ? 'fg' : 'bg';

                    if (codes[i + 1] === 5 && codes[i + 2] !== undefined) {
                        state[key] = color256(codes[i + 2]);
                        i += 2;
                    } else if (codes[i + 1] === 2 && codes[i + 4] !== undefined) {
                        state[key] = `rgb(${codes[i + 2]},${codes[i + 3]},${codes[i + 4]})`;
                        i += 4;
                    }
                }
            }
        };

        while ((match = pattern.exec(source)) !== null) {
            emit(source.slice(cursor, match.index));
            cursor = pattern.lastIndex;

            if (match[2] === 'm') {
                applySgr(match[1]);
            }
        }

        emit(source.slice(cursor));

        return html;
    };

    const formatClock = (milliseconds) => {
        const total = Math.max(0, Math.floor(milliseconds / 1000));
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;
        const pad = (value) => String(value).padStart(2, '0');

        return hours > 0 ? `${hours}:${pad(minutes)}:${pad(seconds)}` : `${pad(minutes)}:${pad(seconds)}`;
    };

    const timestamp = () => {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');

        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    };

    const promptLine = (command) => `\x1b[2m[${timestamp()}]\x1b[0m \x1b[1;35m❯\x1b[0m \x1b[1m${command}\x1b[0m\n`;

    const readStoredBoolean = (key, fallback) => {
        try {
            const value = window.localStorage.getItem(key);

            return value === null ? fallback : value === '1';
        } catch {
            return fallback;
        }
    };

    const storeBoolean = (key, value) => {
        try {
            window.localStorage.setItem(key, value ? '1' : '0');
        } catch {
            // storage unavailable (private mode) — ignore
        }
    };

    const readDetachedIds = () => {
        try {
            const ids = JSON.parse(window.localStorage.getItem(DETACHED_KEY) ?? '[]');

            return Array.isArray(ids) ? ids.map(Number) : [];
        } catch {
            return [];
        }
    };

    const storeDetachedIds = (ids) => {
        try {
            window.localStorage.setItem(DETACHED_KEY, JSON.stringify(ids));
        } catch {
            // storage unavailable — ignore
        }
    };

    const register = () => {
        window.Alpine.data('mksAdminConsole', (config) => ({
            config,
            input: '',
            output: '',
            streamOffset: 0,
            process: null,
            statusTimer: null,
            outputTimer: null,
            clockTimer: null,
            healthTimer: null,
            health: null,
            healthOpen: readStoredBoolean(HEALTH_OPEN_KEY, true),
            tasksOpen: false,
            cronCopied: false,
            detachedIds: readDetachedIds(),
            now: Date.now(),
            autoScroll: readStoredBoolean(AUTOSCROLL_KEY, true),
            historyOpen: readStoredBoolean(HISTORY_OPEN_KEY, window.innerWidth >= 1024),
            fullscreen: false,
            loading: false,
            focused: false,
            copied: false,
            viewing: null,
            historySearch: '',
            suggestions: { commands: [], recent: [] },
            suggestionIndex: -1,
            suggestionsSuppressed: false,
            commandHistory: [],
            historyIndex: -1,
            draft: '',
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
                executedCount: 0,
                pendingCount: 0,
                totalCount: 0,
            },

            label(key, fallback = '') {
                return this.config.labels?.[key] ?? fallback;
            },

            init() {
                this.fetchActive();
                this.loadSuggestions();
                this.loadHealth();
                this.healthTimer = window.setInterval(() => {
                    if (!document.hidden && this.healthOpen) {
                        this.loadHealth();
                    }
                }, this.config.healthPollMs ?? 10000);

                this.$watch('healthOpen', (value) => {
                    storeBoolean(HEALTH_OPEN_KEY, value);

                    if (value) {
                        this.loadHealth();
                    }
                });
                this.clockTimer = window.setInterval(() => {
                    if (this.isRunning) {
                        this.now = Date.now();
                    }
                }, 1000);

                this.$watch('historyOpen', (value) => storeBoolean(HISTORY_OPEN_KEY, value));
                this.$watch('autoScroll', (value) => {
                    storeBoolean(AUTOSCROLL_KEY, value);

                    if (value) {
                        this.scrollToBottom(true);
                    }
                });
                this.$watch('fullscreen', (value) => {
                    document.documentElement.classList.toggle('mks-console-fullscreen-lock', value);
                    this.$nextTick(() => this.scrollToBottom(true));
                });

                this.$nextTick(() => this.focusInput());
            },

            destroy() {
                this.stopOutputPolling();
                this.stopStatusPolling();
                window.clearInterval(this.clockTimer);
                window.clearInterval(this.healthTimer);
                document.documentElement.classList.remove('mks-console-fullscreen-lock');
            },

            // ── Status ────────────────────────────────────────────────────────────

            get isRunning() {
                return this.process?.alive === true;
            },

            get state() {
                if (this.interactive.active) {
                    return 'interactive';
                }

                if (!this.process) {
                    return 'idle';
                }

                if (this.isRunning) {
                    return this.process.status === 'pending' ? 'pending' : 'running';
                }

                return this.process.status ?? 'idle';
            },

            get statusLabel() {
                if (this.state === 'interactive') {
                    return this.label('interactive_title', 'Interactive');
                }

                return this.label(`status_${this.state}`, this.state);
            },

            get elapsed() {
                const started = this.process?.started_at ? Date.parse(this.process.started_at) : null;

                if (!started) {
                    return '';
                }

                const finished = this.process.finished_at ? Date.parse(this.process.finished_at) : null;

                return formatClock((this.isRunning || !finished ? this.now : finished) - started);
            },

            get canStart() {
                return !this.loading && !this.interactive.active && !this.isRunning;
            },

            get canStop() {
                return this.isRunning && !this.interactive.active;
            },

            // ── Output ────────────────────────────────────────────────────────────

            get displayText() {
                return this.viewing ? this.viewing.output : this.output;
            },

            get renderedOutput() {
                return renderAnsi(this.displayText);
            },

            get isEmpty() {
                return !this.viewing && this.output === '';
            },

            appendOutput(text) {
                if (!text) {
                    return;
                }

                this.output += text;

                if (this.output.length > MAX_BUFFER_CHARS) {
                    const cut = this.output.indexOf('\n', this.output.length - TRIM_BUFFER_TO);
                    this.output = this.output.slice(cut === -1 ? this.output.length - TRIM_BUFFER_TO : cut + 1);
                }

                if (!this.viewing) {
                    this.scrollToBottom();
                }
            },

            beginBlock(command) {
                if (this.output !== '' && !this.output.endsWith('\n\n')) {
                    this.appendOutput(this.output.endsWith('\n') ? '\n' : '\n\n');
                }

                if (command) {
                    this.appendOutput(promptLine(command));
                }
            },

            scrollToBottom(force = false) {
                if (!this.autoScroll && !force) {
                    return;
                }

                this.$nextTick(() => {
                    const el = this.$refs.scroller;

                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                });
            },

            clear() {
                this.output = '';
                this.viewing = null;

                if (!this.isRunning) {
                    this.process = null;
                    this.streamOffset = 0;
                }

                this.focusInput();
            },

            async copyOutput() {
                const text = stripAnsi(this.displayText);

                if (!text) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(text);
                    this.copied = true;
                    window.setTimeout(() => (this.copied = false), 1500);
                } catch {
                    // clipboard blocked — nothing to do
                }
            },

            focusInput() {
                this.$nextTick(() => this.$refs.input?.focus({ preventScroll: true }));
            },

            onOutputClick() {
                if (window.getSelection()?.toString()) {
                    return;
                }

                this.focusInput();
            },

            // ── Prompt: autocomplete + history ───────────────────────────────────

            async loadSuggestions() {
                try {
                    const data = await this.$wire.consoleSuggestions();
                    this.suggestions = {
                        commands: data?.commands ?? [],
                        recent: data?.recent ?? [],
                    };
                    this.commandHistory = [...this.suggestions.recent];
                } catch {
                    // autocomplete is optional
                }
            },

            get matchingSuggestions() {
                const raw = this.input.replace(/^\s+/, '').replace(/\s+/g, ' ').toLowerCase();

                if (raw === '') {
                    return [];
                }

                const query = raw.startsWith('artisan ') ? `php ${raw}` : raw;
                const commands = this.suggestions.commands;

                if (query.startsWith('php artisan ') || query.startsWith('composer ')) {
                    return commands
                        .filter((item) => item.value.toLowerCase().startsWith(query) && item.value.toLowerCase() !== query.trimEnd())
                        .slice(0, MAX_SUGGESTIONS);
                }

                if (query.includes(' ')) {
                    return [];
                }

                const nameOf = (value) => value.replace(/^(php artisan|composer) /, '').toLowerCase();

                return commands
                    .filter((item) => item.value.toLowerCase().startsWith(query) || nameOf(item.value).includes(query))
                    .sort((a, b) => Number(!nameOf(a.value).startsWith(query)) - Number(!nameOf(b.value).startsWith(query)))
                    .slice(0, MAX_SUGGESTIONS);
            },

            get suggestionsOpen() {
                return this.focused && !this.suggestionsSuppressed && this.canStart && this.matchingSuggestions.length > 0;
            },

            onInput() {
                this.suggestionsSuppressed = false;
                this.suggestionIndex = -1;
                this.historyIndex = -1;
            },

            acceptSuggestion(index = null) {
                const list = this.matchingSuggestions;
                const item = list[index ?? (this.suggestionIndex >= 0 ? this.suggestionIndex : 0)];

                if (!item) {
                    return;
                }

                this.input = `${item.value} `;
                this.suggestionIndex = -1;
                this.focusInput();
            },

            moveSuggestion(step) {
                const count = this.matchingSuggestions.length;

                if (count === 0) {
                    return;
                }

                if (this.suggestionIndex === -1) {
                    this.suggestionIndex = step < 0 ? count - 1 : 0;

                    return;
                }

                this.suggestionIndex = (this.suggestionIndex + step + count) % count;
            },

            recallHistory(step) {
                const history = this.commandHistory;

                if (history.length === 0) {
                    return;
                }

                if (this.historyIndex === -1) {
                    this.draft = this.input;
                }

                const next = Math.min(history.length - 1, Math.max(-1, this.historyIndex + step));
                this.historyIndex = next;
                this.input = next === -1 ? this.draft : history[next];
                this.suggestionsSuppressed = true;

                this.$nextTick(() => {
                    const el = this.$refs.input;

                    if (el) {
                        el.selectionStart = el.selectionEnd = el.value.length;
                    }
                });
            },

            rememberCommand(command) {
                this.commandHistory = [command, ...this.commandHistory.filter((item) => item !== command)].slice(0, 100);
                this.historyIndex = -1;
                this.draft = '';
            },

            onKeydown(event) {
                const open = this.suggestionsOpen;

                if (event.key === 'Enter') {
                    event.preventDefault();

                    if (open && this.suggestionIndex >= 0) {
                        this.acceptSuggestion();

                        return;
                    }

                    this.run();

                    return;
                }

                if (event.key === 'Tab' && open) {
                    event.preventDefault();
                    this.acceptSuggestion();

                    return;
                }

                if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                    event.preventDefault();
                    const step = event.key === 'ArrowUp' ? -1 : 1;

                    if (open) {
                        this.moveSuggestion(step);
                    } else {
                        this.recallHistory(-step);
                    }

                    return;
                }

                if (event.key === 'Escape') {
                    if (open) {
                        this.suggestionsSuppressed = true;
                    } else if (this.viewing) {
                        this.backToLive();
                    } else if (this.fullscreen) {
                        this.fullscreen = false;
                    } else {
                        this.input = '';
                    }

                    return;
                }

                if (event.ctrlKey && !event.metaKey && event.key.toLowerCase() === 'c') {
                    const el = event.target;
                    const hasSelection = el && el.selectionStart !== el.selectionEnd;

                    if (this.canStop && !hasSelection) {
                        event.preventDefault();
                        this.stop();
                    }

                    return;
                }

                if (event.ctrlKey && event.key.toLowerCase() === 'l') {
                    event.preventDefault();
                    this.clear();
                }
            },

            useCommand(command) {
                this.viewing = null;
                this.input = command;
                this.suggestionsSuppressed = true;
                this.focusInput();
            },

            // ── History panel ─────────────────────────────────────────────────────

            matchesHistory(command) {
                const needle = this.historySearch.trim().toLowerCase();

                return needle === '' || command.toLowerCase().includes(needle);
            },

            async showLog(id) {
                try {
                    const entry = await this.$wire.logEntry(id);

                    if (entry) {
                        this.viewing = entry;
                        this.$nextTick(() => {
                            if (this.$refs.scroller) {
                                this.$refs.scroller.scrollTop = this.$refs.scroller.scrollHeight;
                            }
                        });
                    }
                } catch {
                    // ignore
                }

                if (window.innerWidth < 1024) {
                    this.historyOpen = false;
                }
            },

            backToLive() {
                this.viewing = null;
                this.scrollToBottom(true);
                this.focusInput();
            },

            async deleteLog(id) {
                if (!window.confirm(this.label('delete_log_confirm', 'Delete this log entry?'))) {
                    return;
                }

                await this.$wire.deleteLog(id);
            },

            onLogDeleted(detail) {
                if (this.viewing && Number(detail?.id) === Number(this.viewing.id)) {
                    this.backToLive();
                }
            },

            onLogsCleared() {
                if (this.viewing) {
                    this.backToLive();
                }
            },

            refreshHistory() {
                this.$wire.$refresh();
            },

            // ── Running commands ──────────────────────────────────────────────────

            async postJson(url, body) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body ?? {}),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? `Request failed (${response.status}).`);
                }

                return data;
            },

            async getJson(url) {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Request failed (${response.status}).`);
                }

                return response.json();
            },

            async run(commandOverride = null) {
                const command = (commandOverride ?? this.input).trim();

                if (!command || !this.canStart) {
                    return;
                }

                this.input = '';
                this.viewing = null;
                this.suggestionsSuppressed = true;
                this.rememberCommand(command);
                this.loading = true;

                try {
                    const detection = await this.postJson(this.config.interactive.detectUrl, { command });

                    if (detection.interactive) {
                        await this.beginInteractive(command, detection.handler);

                        return;
                    }

                    const process = await this.postJson(this.config.startUrl, { command });

                    this.beginBlock(null);
                    this.streamOffset = 0;
                    this.attachProcess(process);
                } catch (error) {
                    this.beginBlock(command);
                    this.appendOutput(`\x1b[31m${error.message}\x1b[0m\n`);
                } finally {
                    this.loading = false;
                    this.focusInput();
                }
            },

            async fetchActive() {
                try {
                    const data = await this.getJson(this.config.activeUrl);
                    const running = (data.processes ?? []).find(
                        (item) => item.alive && !this.detachedIds.includes(Number(item.id)),
                    );

                    if (!running) {
                        return;
                    }

                    const snapshot = await this.getJson(this.withOffset(running.output_url, 0));
                    this.output = snapshot.chunk ?? '';
                    this.streamOffset = snapshot.offset ?? 0;
                    this.attachProcess(running);
                    this.scrollToBottom(true);
                } catch {
                    // ignore reconnect errors
                }
            },

            withOffset(url, offset) {
                const target = new URL(url, window.location.origin);
                target.searchParams.set('offset', String(offset));

                return target;
            },

            attachProcess(process) {
                this.process = process;
                this.now = Date.now();
                this.startOutputPolling();
                this.startStatusPolling();
            },

            /** True while the terminal is still attached to the process a request was made for. */
            isCurrentProcess(id) {
                return id !== undefined && id !== null && Number(this.process?.id) === Number(id);
            },

            async pullOutput() {
                if (!this.process?.output_url) {
                    return null;
                }

                const processId = this.process.id;
                const data = await this.getJson(this.withOffset(this.process.output_url, this.streamOffset));

                // Detached / switched while the request was in flight: drop the stale response.
                if (!this.isCurrentProcess(processId)) {
                    return null;
                }

                if (data.chunk) {
                    this.appendOutput(data.chunk);
                    this.streamOffset = data.offset ?? this.streamOffset;
                }

                return data;
            },

            startOutputPolling() {
                this.stopOutputPolling();

                const tick = async () => {
                    try {
                        const data = await this.pullOutput();

                        if (!data || !this.process) {
                            return;
                        }

                        if (typeof data.alive === 'boolean') {
                            this.process = { ...this.process, alive: data.alive, status: data.status };
                        }

                        if (data.finished && !data.alive) {
                            this.stopOutputPolling();
                            await this.onProcessFinished();
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
                    window.clearInterval(this.outputTimer);
                    this.outputTimer = null;
                }
            },

            startStatusPolling() {
                this.stopStatusPolling();
                this.statusTimer = window.setInterval(() => this.refreshStatus(), this.config.pollIntervalMs ?? 2000);
            },

            stopStatusPolling() {
                if (this.statusTimer) {
                    window.clearInterval(this.statusTimer);
                    this.statusTimer = null;
                }
            },

            async refreshStatus() {
                if (!this.process?.status_url) {
                    return;
                }

                const processId = this.process.id;

                try {
                    const data = await this.getJson(this.process.status_url);

                    if (!this.isCurrentProcess(processId)) {
                        return;
                    }

                    this.process = { ...this.process, ...data };

                    if (!data.alive) {
                        this.stopStatusPolling();
                    }
                } catch {
                    // ignore transient poll errors
                }
            },

            async onProcessFinished() {
                this.stopStatusPolling();
                await this.refreshStatus();

                try {
                    await this.pullOutput();
                } catch {
                    // ignore
                }

                this.now = Date.now();
                this.refreshHistory();
                this.loadHealth();
                this.focusInput();
            },

            async stop() {
                if (!this.process?.stop_url) {
                    return;
                }

                this.loading = true;

                try {
                    const data = await this.postJson(this.process.stop_url);
                    this.process = { ...this.process, ...data };
                } catch (error) {
                    this.appendOutput(`\n\x1b[31mError stopping: ${error.message}\x1b[0m\n`);
                } finally {
                    this.stopOutputPolling();
                    this.loading = false;
                    await this.onProcessFinished();
                }
            },

            // ── Health panel: scheduler, workers, queue, background processes ────

            async loadHealth() {
                try {
                    const health = await this.$wire.consoleHealth();

                    if (!health) {
                        return;
                    }

                    this.health = health;

                    const activeIds = (health.processes ?? []).map((item) => Number(item.id));
                    const detached = this.detachedIds.filter((id) => activeIds.includes(id));

                    if (detached.length !== this.detachedIds.length) {
                        this.detachedIds = detached;
                        storeDetachedIds(detached);
                    }
                } catch {
                    // health is informational — ignore failures
                }
            },

            healthCommand(key) {
                return this.config.healthCommands?.[key] ?? '';
            },

            /** Runs a health action in the terminal, detaching any attached process so it keeps running. */
            async runHealthCommand(key) {
                const command = this.healthCommand(key);

                if (!command || this.interactive.active || this.loading) {
                    return;
                }

                if (this.isRunning) {
                    this.detach();
                }

                this.viewing = null;
                await this.run(command);
                this.loadHealth();
            },

            isAttached(item) {
                return Number(this.process?.id) === Number(item?.id) && this.isRunning;
            },

            detach() {
                if (!this.process) {
                    return;
                }

                const id = Number(this.process.id);

                if (this.isRunning && !this.detachedIds.includes(id)) {
                    this.detachedIds = [...this.detachedIds, id];
                    storeDetachedIds(this.detachedIds);
                    this.appendOutput(`\n\x1b[2m${this.label('detached_notice', 'Detached — the process keeps running in the background.')}\x1b[0m\n`);
                }

                this.stopOutputPolling();
                this.stopStatusPolling();
                this.process = null;
                this.streamOffset = 0;
                this.loadHealth();
                this.focusInput();
            },

            async attachTo(item) {
                if (!item || this.isAttached(item) || this.interactive.active) {
                    return;
                }

                if (this.isRunning) {
                    this.detach();
                }

                this.detachedIds = this.detachedIds.filter((id) => id !== Number(item.id));
                storeDetachedIds(this.detachedIds);
                this.viewing = null;

                try {
                    const snapshot = await this.getJson(this.withOffset(item.output_url, 0));
                    this.beginBlock(null);
                    this.appendOutput(snapshot.chunk ?? '');
                    this.streamOffset = snapshot.offset ?? 0;
                } catch {
                    this.streamOffset = 0;
                }

                this.attachProcess(item);
                this.scrollToBottom(true);
            },

            async stopBackground(item) {
                if (!item?.stop_url || !window.confirm(this.label('stop_process_confirm', 'Stop this process?'))) {
                    return;
                }

                if (this.isAttached(item)) {
                    await this.stop();

                    return;
                }

                try {
                    await this.postJson(item.stop_url);
                } catch {
                    // the next health refresh shows the real state
                }

                this.loadHealth();
                this.refreshHistory();
            },

            async copyCronLine() {
                const line = this.health?.scheduler?.cron_line;

                if (!line) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(line);
                    this.cronCopied = true;
                    window.setTimeout(() => (this.cronCopied = false), 1500);
                } catch {
                    // clipboard blocked
                }
            },

            // ── Interactive commands (migrate:smart wizard) ──────────────────────

            get interactiveModeOptions() {
                return [
                    { id: 'all', label: this.label('interactive_mode_all', 'Run all'), icon: '⇶' },
                    { id: 'search', label: this.label('interactive_mode_search', 'Search and select'), icon: '⌕' },
                    { id: 'single', label: this.label('interactive_mode_single', 'Pick one'), icon: '◉' },
                ];
            },

            get interactiveSummary() {
                return this.label('interactive_migration_summary', ':executed executed · :pending pending · :total total')
                    .replace(':executed', String(this.interactive.executedCount ?? 0))
                    .replace(':pending', String(this.interactive.pendingCount ?? 0))
                    .replace(':total', String(this.interactive.totalCount ?? 0));
            },

            async beginInteractive(command, handler) {
                this.beginBlock(command);

                if (handler !== 'migrate:smart') {
                    this.appendOutput('\x1b[31mUnsupported interactive handler.\x1b[0m\n');

                    return;
                }

                const data = await this.getJson(this.config.interactive.migrateSmartCatalogUrl);

                if ((data.count ?? 0) === 0) {
                    this.appendOutput(`${this.label('interactive_no_migrations')}\n`);

                    return;
                }

                this.interactive = {
                    ...this.interactive,
                    active: true,
                    command,
                    handler,
                    step: 'mode',
                    mode: null,
                    migrations: data.migrations ?? [],
                    search: '',
                    selected: [],
                    singleSelected: '',
                    previewText: '',
                    requiresProductionConfirm: false,
                    executedCount: data.executed_count ?? 0,
                    pendingCount: data.pending_count ?? 0,
                    totalCount: data.count ?? (data.migrations ?? []).length,
                };
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
                this.$nextTick(() => this.$refs.interactiveSearch?.focus());
            },

            selectedMigrationNames() {
                if (this.interactive.mode === 'single') {
                    return this.interactive.singleSelected ? [this.interactive.singleSelected] : [];
                }

                return [...this.interactive.selected];
            },

            migrationStatusLabel(entry) {
                return entry?.executed
                    ? this.label('interactive_status_ran', 'ran')
                    : this.label('interactive_status_pending', 'pending');
            },

            filteredInteractiveMigrations() {
                const needle = this.interactive.search.trim().toLowerCase();

                return (this.interactive.migrations ?? []).filter((entry) => {
                    if (!needle) {
                        return true;
                    }

                    return `${entry.name} ${entry.source_label ?? ''}`.toLowerCase().includes(needle);
                });
            },

            async previewInteractive() {
                const migrations = this.selectedMigrationNames();

                if (migrations.length === 0) {
                    return;
                }

                this.loading = true;

                try {
                    const data = await this.postJson(this.config.interactive.migrateSmartAnalyzeUrl, { migrations });
                    const lines = [...(data.notices ?? []), ...(data.warnings ?? [])];

                    if ((data.actions ?? []).length === 0 && (data.pending_runs ?? []).length === 0) {
                        lines.push(this.label('interactive_nothing_to_sync', 'Nothing to synchronize.'));
                    } else {
                        lines.push('The following changes will be applied:');
                        (data.pending_runs ?? []).forEach((run) => lines.push(`+ ${run.label}`));
                        (data.actions ?? []).forEach((action) => lines.push(`+ ${action.label}`));
                    }

                    this.interactive.previewText = lines.join('\n');
                    this.interactive.step = 'preview';
                } catch (error) {
                    this.appendOutput(`\x1b[31mError: ${error.message}\x1b[0m\n`);
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
                    const data = await this.postJson(this.config.interactive.migrateSmartExecuteUrl, {
                        migrations,
                        dry_run: dryRun,
                        force: this.interactive.requiresProductionConfirm,
                    });

                    if (data.requires_confirmation) {
                        this.interactive.requiresProductionConfirm = true;
                        this.interactive.previewText = (data.lines ?? []).join('\n');
                        this.interactive.step = 'preview';

                        return;
                    }

                    const text = (data.lines ?? []).join('\n');
                    const exitCode = data.exit_code ?? 0;
                    this.appendOutput(`${text}\n`);

                    await this.postJson(this.config.interactive.logUrl, {
                        command: this.interactive.command,
                        output: text,
                        exit_code: exitCode,
                        duration_ms: Math.round(performance.now() - started),
                    }).catch(() => null);

                    this.cancelInteractive();
                    this.refreshHistory();
                } catch (error) {
                    this.appendOutput(`\x1b[31mError: ${error.message}\x1b[0m\n`);
                } finally {
                    this.loading = false;
                }
            },

            cancelInteractive() {
                this.interactive.active = false;
                this.interactive.step = 'mode';
                this.interactive.requiresProductionConfirm = false;
                this.focusInput();
            },
        }));
    };

    if (window.Alpine) {
        register();
    } else {
        document.addEventListener('alpine:init', register);
    }
})();
