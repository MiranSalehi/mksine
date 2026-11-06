@php($rtl = app()->getLocale() === 'fa')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('mksine::install.web.title') }}</title>
    <style>
        :root { --bg: #f5f6f8; --card: #fff; --text: #1f2430; --muted: #6b7280; --border: #e3e6eb; --primary: #4f46e5; --primary-text: #fff; --ok: #15803d; --warn: #b45309; --error: #b91c1c; }
        @media (prefers-color-scheme: dark) { :root { --bg: #111318; --card: #1a1d24; --text: #e6e8ec; --muted: #9aa1ad; --border: #2a2e38; --primary: #818cf8; --primary-text: #0b0d12; --ok: #4ade80; --warn: #fbbf24; --error: #f87171; } }
        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.6 {{ $rtl ? 'Tahoma, "Vazirmatn", "IRANYekan", sans-serif' : 'system-ui, -apple-system, "Segoe UI", sans-serif' }}; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 64px; }
        header { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        header h1 { font-size: 20px; margin: 0; }
        .steps { display: flex; gap: 6px; flex-wrap: wrap; list-style: none; padding: 0; margin: 0 0 20px; font-size: 13px; color: var(--muted); }
        .steps li { padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); }
        .steps li.current { background: var(--primary); border-color: var(--primary); color: var(--primary-text); }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 16px; }
        h2 { font-size: 18px; margin: 0 0 6px; }
        h3 { font-size: 15px; margin: 20px 0 8px; }
        p.lead { color: var(--muted); margin: 0 0 18px; }
        label { display: block; font-weight: 600; margin: 14px 0 4px; }
        .hint { color: var(--muted); font-size: 13px; font-weight: 400; }
        input[type=text], input[type=email], input[type=url], input[type=password], input[type=number], select { width: 100%; padding: 9px 11px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font: inherit; }
        input:focus, select:focus { outline: 2px solid var(--primary); outline-offset: 1px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        @media (max-width: 560px) { .row { grid-template-columns: 1fr; } }
        .check { display: flex; gap: 8px; align-items: flex-start; font-weight: 400; margin: 8px 0; }
        .check input { margin-top: 4px; }
        .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; border: 1px solid var(--primary); background: var(--primary); color: var(--primary-text); font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn.secondary { background: transparent; color: var(--primary); }
        .btn[disabled] { opacity: .5; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 4px; border-bottom: 1px solid var(--border); vertical-align: top; }
        td.status { text-align: end; white-space: nowrap; font-weight: 600; }
        td.status .mono { white-space: normal; overflow-wrap: anywhere; font-weight: 400; }
        .ok { color: var(--ok); } .warn { color: var(--warn); } .error { color: var(--error); }
        .alert { padding: 12px 14px; border-radius: 8px; border: 1px solid currentColor; margin-bottom: 14px; }
        .alert ul { margin: 6px 0 0; padding-inline-start: 18px; }
        code, .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; direction: ltr; unicode-bidi: embed; }
        .mono-box { display: block; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; overflow-x: auto; white-space: pre; text-align: left; }
        .lang { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .lang button { padding: 22px; font-size: 17px; }
        ul.progress { list-style: none; padding: 0; margin: 0; }
        ul.progress li { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid var(--border); }
        ul.progress li .detail { color: var(--muted); font-size: 13px; }
        .bar { height: 8px; background: var(--bg); border-radius: 999px; overflow: hidden; margin: 6px 0 16px; border: 1px solid var(--border); }
        .bar span { display: block; height: 100%; width: 0; background: var(--primary); transition: width .3s; }
        .plugins { columns: 2; column-gap: 24px; }
        @media (max-width: 560px) { .plugins { columns: 1; } }
        .plugins .check { break-inside: avoid; }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>{{ __('mksine::install.web.title') }}</h1>
        <span class="hint">MKSine {{ \Miran\Mksine\Support\PackageVersion::current() }}</span>
    </header>

    @isset($currentStep)
        <ol class="steps">
            @foreach (['language', 'requirements', 'database', 'site', 'install'] as $wizardStep)
                <li @class(['current' => $currentStep === $wizardStep])>{{ __('mksine::install.web.wizard.'.$wizardStep) }}</li>
            @endforeach
        </ol>
    @endisset

    @if ($errors->any())
        <div class="alert error" role="alert">
            {{ __('mksine::install.web.fix_errors') }}
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</div>
@stack('scripts')
</body>
</html>
