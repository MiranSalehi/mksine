@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['fa', 'ar', 'ku', 'he'], true);
    $rawMessage = trim((string) ($message ?? ($exception?->getMessage() ?? '')));
    $displayMessage = $rawMessage !== '' && $rawMessage !== 'Forbidden'
        ? $rawMessage
        : __('mksine::errors.403.default_message');
    $pageUrl = (string) ($currentUrl ?? request()->fullUrl());
    $appName = (string) config('app.name', 'MKSine');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('mksine::errors.403.title') }} — {{ $appName }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg-1: #0b1020;
            --bg-2: #151b33;
            --card: rgba(255, 255, 255, 0.08);
            --card-border: rgba(255, 255, 255, 0.14);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #f59e0b;
            --accent-2: #fb7185;
            --btn: #ffffff;
            --btn-text: #0f172a;
            --shadow: 0 28px 80px -24px rgba(0, 0, 0, 0.65);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(245, 158, 11, 0.22), transparent 60%),
                radial-gradient(900px 500px at 100% 0%, rgba(251, 113, 133, 0.18), transparent 55%),
                linear-gradient(160deg, var(--bg-1), var(--bg-2));
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }

        .shell {
            width: min(100%, 42rem);
        }

        .card {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid var(--card-border);
            background: var(--card);
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
            padding: clamp(1.5rem, 4vw, 2.25rem);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), transparent 45%);
            pointer-events: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            border: 1px solid rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.12);
            color: #fde68a;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .code {
            font-size: clamp(3.5rem, 12vw, 5.5rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin: 1rem 0 0.5rem;
            background: linear-gradient(120deg, #fff 10%, #fbbf24 55%, #fb7185 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.25rem, 3.5vw, 1.65rem);
            line-height: 1.35;
        }

        .message {
            margin: 0 0 1.5rem;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.98rem;
        }

        .url-box {
            margin-bottom: 1.5rem;
            padding: 0.85rem 1rem;
            border-radius: 0.9rem;
            border: 1px dashed rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.35);
        }

        .url-label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .url-link {
            color: #e2e8f0;
            word-break: break-all;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .url-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn {
            appearance: none;
            border: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.1rem;
            border-radius: 0.85rem;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .btn:focus-visible {
            outline: 2px solid #fbbf24;
            outline-offset: 2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #fbbf24, #f97316);
            color: #111827;
            box-shadow: 0 10px 30px -12px rgba(249, 115, 22, 0.8);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--btn);
            color: var(--btn-text);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px -14px rgba(255, 255, 255, 0.8);
        }

        .icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        .footer {
            margin-top: 1rem;
            text-align: center;
            color: rgba(148, 163, 184, 0.85);
            font-size: 0.8rem;
        }

        @media (prefers-reduced-motion: reduce) {
            .btn { transition: none; }
            .btn-primary:hover,
            .btn-secondary:hover { transform: none; }
        }
    </style>
</head>
<body data-mksine-error-page="403">
    <main class="shell" role="main" aria-labelledby="mksine-error-403-title">
        <div class="card">
            <span class="badge">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2L4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M9.5 11.5 11 13l3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ __('mksine::errors.403.code_label') }} 403
            </span>

            <div class="code" aria-hidden="true">403</div>
            <h1 id="mksine-error-403-title">{{ __('mksine::errors.403.heading') }}</h1>
            <p class="message">{{ $displayMessage }}</p>

            <div class="url-box">
                <span class="url-label">{{ __('mksine::errors.403.current_page') }}</span>
                <a href="{{ $pageUrl }}" class="url-link">{{ $pageUrl }}</a>
            </div>

            <div class="actions">
                <a href="{{ $pageUrl }}" class="btn btn-primary">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M14 4h6v6M10 14 20 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('mksine::errors.403.retry_same_page') }}
                </a>
                <button type="button" class="btn btn-secondary" onclick="window.location.reload()">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M21 3v6h-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('mksine::errors.403.refresh') }}
                </button>
            </div>
        </div>

        <p class="footer">{{ $appName }}</p>
    </main>
</body>
</html>
