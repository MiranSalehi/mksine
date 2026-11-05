@extends('mksine::installer.layout', ['currentStep' => 'database'])

@section('content')
    <form method="post" action="{{ route('mksine.installer.database.store') }}" class="card" id="database-form">
        @csrf
        <h2>{{ __('mksine::install.web.database_heading') }}</h2>
        <p class="lead">{{ __('mksine::install.web.database_lead') }}</p>

        <label for="connection">{{ __('mksine::install.web.database_driver') }}</label>
        <select id="connection" name="connection">
            @foreach ($drivers as $driver)
                <option value="{{ $driver }}" @selected(old('connection', $defaults['connection']) === $driver)>{{ __('mksine::install.web.drivers.'.$driver) }}</option>
            @endforeach
        </select>

        <div data-server-fields>
            <div class="row">
                <div>
                    <label for="host">{{ __('mksine::install.web.database_host') }}</label>
                    <input id="host" name="host" type="text" dir="ltr" value="{{ old('host', $defaults['host']) }}">
                </div>
                <div>
                    <label for="port">{{ __('mksine::install.web.database_port') }}</label>
                    <input id="port" name="port" type="number" dir="ltr" value="{{ old('port', $defaults['port']) }}">
                </div>
            </div>
        </div>

        <label for="database">{{ __('mksine::install.web.database_name') }}
            <span class="hint" data-sqlite-hint hidden>{{ __('mksine::install.web.sqlite_hint') }}</span>
        </label>
        <input id="database" name="database" type="text" dir="ltr" value="{{ old('database', $defaults['database']) }}">

        <div data-server-fields>
            <div class="row">
                <div>
                    <label for="username">{{ __('mksine::install.web.database_username') }}</label>
                    <input id="username" name="username" type="text" dir="ltr" autocomplete="off" value="{{ old('username', $defaults['username']) }}">
                </div>
                <div>
                    <label for="password">{{ __('mksine::install.web.database_password') }}</label>
                    <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password">
                </div>
            </div>
            <p class="hint">{{ __('mksine::install.web.database_panel_hint') }}</p>
        </div>

        <div class="actions">
            <a class="btn secondary" href="{{ route('mksine.installer.requirements') }}">{{ __('mksine::install.web.back') }}</a>
            <button type="submit" class="btn">{{ __('mksine::install.web.test_and_continue') }}</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var select = document.getElementById('connection');
            var port = document.getElementById('port');
            var defaultPorts = { mysql: '3306', mariadb: '3306', pgsql: '5432' };
            function sync() {
                var sqlite = select.value === 'sqlite';
                document.querySelectorAll('[data-server-fields]').forEach(function (el) { el.hidden = sqlite; });
                document.querySelector('[data-sqlite-hint]').hidden = ! sqlite;
                if (port.value === '' || port.value === '3306' || port.value === '5432') { port.value = defaultPorts[select.value] || port.value; }
            }
            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
