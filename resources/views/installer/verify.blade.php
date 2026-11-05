@extends('mksine::installer.layout', ['currentStep' => 'verify'])

@section('content')
    <form method="post" action="{{ route('mksine.installer.verify.store') }}" class="card">
        @csrf
        <h2>{{ __('mksine::install.web.token_heading') }}</h2>
        <p class="lead">{{ __('mksine::install.web.token_lead') }}</p>

        @if ($tokenError)
            <div class="alert error">{{ __('mksine::install.web.token_write_failed', ['error' => $tokenError]) }}</div>
        @else
            <ol>
                <li>{{ __('mksine::install.web.token_step_open') }}</li>
                <li>{{ __('mksine::install.web.token_step_file') }} <code>{{ $tokenPath }}</code></li>
                <li>{{ __('mksine::install.web.token_step_copy') }}</li>
            </ol>

            <label for="token">{{ __('mksine::install.web.token_label') }}</label>
            <input id="token" name="token" type="text" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="XXXX-XXXX-XXXX" required autofocus>
        @endif

        <div class="actions">
            <a class="btn secondary" href="{{ route('mksine.installer.welcome') }}">{{ __('mksine::install.web.back') }}</a>
            <button type="submit" class="btn" @disabled($tokenError)>{{ __('mksine::install.web.continue') }}</button>
        </div>
    </form>
@endsection
