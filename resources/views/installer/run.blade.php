@extends('mksine::installer.layout', ['currentStep' => 'install'])

@section('content')
    <div class="card" id="installer">
        <h2>{{ __('mksine::install.web.run_heading') }}</h2>
        <p class="lead" data-lead>{{ __('mksine::install.web.run_lead') }}</p>

        <div class="bar" aria-hidden="true"><span data-bar></span></div>

        <ul class="progress">
            @foreach ($steps as $step)
                <li data-step="{{ $step['key'] }}">
                    <span>{{ $step['label'] }}<br><span class="detail" data-detail></span></span>
                    <span class="status {{ $step['completed'] ? 'ok' : '' }}" data-status>{{ $step['completed'] ? __('mksine::install.web.step_done') : __('mksine::install.web.step_pending') }}</span>
                </li>
            @endforeach
        </ul>

        <div class="alert error" data-error hidden></div>
        <div class="alert warn" data-warnings hidden><ul></ul></div>

        <div class="actions" data-run-actions>
            <a class="btn secondary" href="{{ route('mksine.installer.site') }}" data-back>{{ __('mksine::install.web.back') }}</a>
            <button type="button" class="btn" data-start>{{ __('mksine::install.web.start') }}</button>
        </div>
    </div>

    <div class="card" data-done hidden>
        <h2 class="ok">{{ __('mksine::install.web.done_heading') }}</h2>
        <p>{{ __('mksine::install.web.done_lead') }}</p>

        <h3>{{ __('mksine::install.web.cron_heading') }}</h3>
        <p class="hint">{{ __('mksine::install.web.cron_lead') }}</p>
        <code class="mono-box" data-cron></code>

        <div class="actions">
            <a class="btn secondary" data-site-link href="/">{{ __('mksine::install.web.view_site') }}</a>
            <a class="btn" data-admin-link href="/admin">{{ __('mksine::install.web.go_to_admin') }}</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var root = document.getElementById('installer');
            var startButton = root.querySelector('[data-start]');
            var errorBox = root.querySelector('[data-error]');
            var warningBox = root.querySelector('[data-warnings]');
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var text = {!! json_encode([
                'running' => __('mksine::install.web.step_running'),
                'done' => __('mksine::install.web.step_done'),
                'skipped' => __('mksine::install.web.step_skipped'),
                'failed' => __('mksine::install.web.step_failed'),
                'retry' => __('mksine::install.web.retry'),
                'network' => __('mksine::install.web.network_error'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};

            function row(key) { return root.querySelector('[data-step="' + key + '"]'); }

            function nextPendingRow() {
                var rows = root.querySelectorAll('[data-step]');
                for (var i = 0; i < rows.length; i++) {
                    if (! rows[i].dataset.finished && ! rows[i].querySelector('[data-status]').classList.contains('ok')) { return rows[i]; }
                }
                return null;
            }

            function addWarnings(warnings) {
                if (! warnings || ! warnings.length) { return; }
                warningBox.hidden = false;
                warnings.forEach(function (warning) {
                    var li = document.createElement('li');
                    li.textContent = warning;
                    warningBox.querySelector('ul').appendChild(li);
                });
            }

            function fail(message) {
                errorBox.textContent = message;
                errorBox.hidden = false;
                // Lets the user change the site details or plugin choice before retrying.
                root.querySelector('[data-back]').hidden = false;
                startButton.disabled = false;
                startButton.textContent = text.retry;
            }

            function finish(data) {
                document.querySelector('[data-done]').hidden = false;
                document.querySelector('[data-cron]').textContent = data.cron_line;
                document.querySelector('[data-admin-link]').href = data.admin_url;
                document.querySelector('[data-site-link]').href = data.site_url;
                root.querySelector('[data-run-actions]').hidden = true;
                root.querySelector('[data-bar]').style.width = '100%';
            }

            function step() {
                var pending = nextPendingRow();
                if (pending) { pending.querySelector('[data-status]').textContent = text.running; }

                fetch(@json(route('mksine.installer.run.next')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().then(function (data) { return { ok: response.ok, data: data }; });
                }).then(function (reply) {
                    var data = reply.data;
                    if (! reply.ok) { return fail(data.message || text.network); }
                    if (data.step) {
                        var current = row(data.step);
                        var status = data.result.status;
                        var label = status === 'completed' ? text.done : (status === 'skipped' ? text.skipped : (status === 'failed' ? text.failed : text.running));
                        current.querySelector('[data-status]').textContent = label;
                        current.querySelector('[data-status]').className = 'status ' + (status === 'failed' ? 'error' : (status === 'incomplete' ? '' : 'ok'));
                        current.querySelector('[data-detail]').textContent = (data.result.messages || []).join(' ');
                        if (status === 'completed' || status === 'skipped') { current.dataset.finished = '1'; }
                        addWarnings(data.result.warnings);
                        root.querySelector('[data-bar]').style.width = Math.round(100 * data.completed / data.total) + '%';
                        if (status === 'failed') { return fail(data.result.error); }
                    }
                    if (data.done) { return finish(data); }
                    step();
                }).catch(function () { fail(text.network); });
            }

            startButton.addEventListener('click', function () {
                errorBox.hidden = true;
                startButton.disabled = true;
                root.querySelector('[data-back]').hidden = true;
                step();
            });
        })();
    </script>
@endpush
