@extends('mksine::installer.layout', ['currentStep' => 'requirements'])

@section('content')
    <div class="card">
        <h2>{{ __('mksine::install.web.requirements_heading') }}</h2>
        <p class="lead">{{ $passes ? __('mksine::install.web.requirements_pass') : __('mksine::install.web.requirements_fail') }}</p>

        <table>
            @foreach ($checks as $check)
                <tr>
                    <td>
                        {{ __('mksine::install.'.$check->labelKey, $check->parameters) }}
                        @if ($check->current && ! str_starts_with($check->key, 'writable:'))
                            <span class="hint mono">{{ $check->current }}</span>
                        @endif
                    </td>
                    <td class="status">
                        @if ($check->passed)
                            <span class="ok">{{ __('mksine::install.web.ok') }}</span>
                        @elseif ($check->required)
                            <span class="error">{{ __('mksine::install.web.missing') }}</span>
                        @else
                            <span class="warn">{{ __('mksine::install.web.recommended') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>

        <h3>{{ __('mksine::install.web.server_heading') }}</h3>
        <table>
            <tr><td>{{ __('mksine::install.web.profile') }}</td><td class="status">{{ $report->recommendedProfile()->label() }}</td></tr>
            <tr><td>{{ __('mksine::install.web.processes') }}</td><td class="status {{ $report->canProcOpen ? 'ok' : 'warn' }}">{{ $report->canProcOpen ? __('mksine::install.web.available') : __('mksine::install.web.disabled') }}</td></tr>
            <tr><td>{{ __('mksine::install.web.symlinks') }}</td><td class="status {{ $report->canSymlink ? 'ok' : 'warn' }}">{{ $report->canSymlink ? __('mksine::install.web.available') : __('mksine::install.web.disabled') }}</td></tr>
            <tr><td>{{ __('mksine::install.web.time_limit') }}</td><td class="status">{{ $report->hasExecutionTimeLimit() ? $report->maxExecutionTime.'s' : __('mksine::install.web.unlimited') }}</td></tr>
            <tr><td>{{ __('mksine::install.web.public_path') }}</td><td class="status"><span class="mono">{{ $report->publicPath }}</span></td></tr>
        </table>

        @if ($projectInsideWebRoot)
            <div class="alert warn" style="margin-top: 16px">{{ __('mksine::install.web.inside_web_root') }}</div>
        @endif

        <div class="actions">
            <a class="btn secondary" href="{{ route('mksine.installer.requirements') }}">{{ __('mksine::install.web.check_again') }}</a>
            @if ($passes)
                <a class="btn" href="{{ route('mksine.installer.database') }}">{{ __('mksine::install.web.continue') }}</a>
            @else
                <button class="btn" disabled>{{ __('mksine::install.web.continue') }}</button>
            @endif
        </div>
    </div>
@endsection
