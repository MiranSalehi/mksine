@extends('mksine::installer.layout', ['currentStep' => 'site'])

@section('content')
    <form method="post" action="{{ route('mksine.installer.site.store') }}">
        @csrf
        <div class="card">
            <h2>{{ __('mksine::install.web.site_heading') }}</h2>

            <label for="site_name">{{ __('mksine::install.web.site_name') }}</label>
            <input id="site_name" name="site_name" type="text" required value="{{ old('site_name', $defaults['site_name']) }}">

            <div class="row">
                <div>
                    <label for="site_url">{{ __('mksine::install.web.site_url') }}</label>
                    <input id="site_url" name="site_url" type="url" dir="ltr" required value="{{ old('site_url', $defaults['site_url']) }}">
                </div>
                <div>
                    <label for="locale">{{ __('mksine::install.web.site_language') }}</label>
                    <select id="locale" name="locale">
                        <option value="fa" @selected(old('locale', $defaults['locale']) === 'fa')>فارسی</option>
                        <option value="en" @selected(old('locale', $defaults['locale']) === 'en')>English</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('mksine::install.web.admin_heading') }}</h2>
            <div class="row">
                <div>
                    <label for="admin_name">{{ __('mksine::install.web.admin_name') }}</label>
                    <input id="admin_name" name="admin_name" type="text" required value="{{ old('admin_name', $defaults['admin_name']) }}">
                </div>
                <div>
                    <label for="admin_email">{{ __('mksine::install.web.admin_email') }}</label>
                    <input id="admin_email" name="admin_email" type="email" dir="ltr" required value="{{ old('admin_email', $defaults['admin_email']) }}">
                </div>
                <div>
                    <label for="admin_password">{{ __('mksine::install.web.admin_password') }} <span class="hint">{{ __('mksine::install.web.password_hint') }}</span></label>
                    <input id="admin_password" name="admin_password" type="password" dir="ltr" minlength="8" required autocomplete="new-password">
                </div>
                <div>
                    <label for="admin_password_confirmation">{{ __('mksine::install.web.admin_password_confirmation') }}</label>
                    <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" dir="ltr" minlength="8" required autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('mksine::install.web.extensions_heading') }}</h2>

            @if ($themes !== [])
                <label for="theme">{{ __('mksine::install.web.theme') }}</label>
                <select id="theme" name="theme">
                    @foreach ($themes as $identifier => $name)
                        <option value="{{ $identifier }}" @selected(old('theme', $defaults['theme']) === $identifier)>{{ $name }}</option>
                    @endforeach
                </select>
            @endif

            @if ($plugins !== [])
                <h3>{{ __('mksine::install.web.plugins') }} <span class="hint">{{ __('mksine::install.web.plugins_hint') }}</span></h3>
                <div class="plugins">
                    @foreach ($plugins as $id => $plugin)
                        <label class="check">
                            <input type="checkbox" name="plugins[]" value="{{ $id }}" @checked(in_array($id, old('plugins', $defaults['plugins']), true))>
                            <span>{{ $plugin['name'] }} @if ($plugin['description'] !== '')<br><span class="hint">{{ $plugin['description'] }}</span>@endif</span>
                        </label>
                    @endforeach
                </div>
            @endif

            @if ($sampleContentAvailable)
                <h3>{{ __('mksine::install.web.sample_content_heading') }}</h3>
                <input type="hidden" name="sample_content" value="0">
                <label class="check">
                    <input type="checkbox" name="sample_content" value="1" @checked(old('sample_content', $defaults['sample_content']))>
                    <span>{{ __('mksine::install.web.sample_content') }}</span>
                </label>
            @endif

            <div class="actions">
                <a class="btn secondary" href="{{ route('mksine.installer.database') }}">{{ __('mksine::install.web.back') }}</a>
                <button type="submit" class="btn">{{ __('mksine::install.web.continue') }}</button>
            </div>
        </div>
    </form>
@endsection
