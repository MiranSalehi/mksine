@extends('mksine::installer.layout', ['currentStep' => 'language'])

@section('content')
    <div class="card">
        <h2>{{ __('mksine::install.web.welcome_heading') }}</h2>
        <p class="lead">{{ __('mksine::install.web.welcome_lead') }}</p>

        <form method="post" action="{{ route('mksine.installer.language') }}" class="lang">
            @csrf
            <button type="submit" name="locale" value="fa" class="btn" lang="fa" dir="rtl">فارسی</button>
            <button type="submit" name="locale" value="en" class="btn secondary" lang="en" dir="ltr">English</button>
        </form>
    </div>
@endsection
