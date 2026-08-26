<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $textDirection = in_array($locale, ['fa', 'ar', 'ku', 'he'], true) ? 'rtl' : 'ltr';
    $themeName = $theme?->name ?? __('mksine::frontend.theme_dependencies_unknown_theme');
    $pluginList = implode(', ', $missingPluginLabels ?? $missingPlugins ?? []);
@endphp
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $textDirection }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('mksine::frontend.theme_dependencies_page_title', ['theme' => $themeName]) }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #fff7ed;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #ea580c;
            --accent-soft: #ffedd5;
            --border: #fed7aa;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --card: #111827;
                --text: #f8fafc;
                --muted: #94a3b8;
                --accent: #fb923c;
                --accent-soft: rgba(251, 146, 60, 0.12);
                --border: rgba(251, 146, 60, 0.25);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(251, 146, 60, 0.18), transparent 42%),
                var(--bg);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .panel {
            width: min(100%, 42rem);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1 {
            margin: 1rem 0 0.75rem;
            font-size: clamp(1.5rem, 4vw, 2rem);
            line-height: 1.2;
        }

        p {
            margin: 0;
            line-height: 1.7;
            color: var(--muted);
        }

        .plugins {
            margin: 1.25rem 0 0;
            padding: 1rem 1.1rem;
            border-radius: 0.9rem;
            background: var(--accent-soft);
            color: var(--text);
            font-weight: 600;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        a.button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0 1rem;
            border-radius: 0.85rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }

        a.button-primary {
            background: var(--accent);
            color: #fff;
        }

        a.button-secondary {
            border: 1px solid var(--border);
            color: var(--text);
            background: transparent;
        }
    </style>
</head>
<body>
    <main class="panel" role="alert" aria-live="polite">
        <div class="eyebrow">{{ __('mksine::frontend.theme_dependencies_eyebrow') }}</div>
        <h1>{{ __('mksine::frontend.theme_dependencies_heading', ['theme' => $themeName]) }}</h1>
        <p>{{ __('mksine::frontend.theme_dependencies_lead') }}</p>

        <div class="plugins">
            {{ __('mksine::frontend.theme_dependencies_plugins', ['plugins' => $pluginList]) }}
        </div>

        <p style="margin-top: 1rem;">
            {{ __('mksine::frontend.theme_dependencies_hint') }}
        </p>

        <div class="actions">
            <a class="button button-primary" href="{{ url('/admin/plugins') }}">
                {{ __('mksine::frontend.theme_dependencies_admin_plugins') }}
            </a>
            <a class="button button-secondary" href="{{ url('/admin/themes') }}">
                {{ __('mksine::frontend.theme_dependencies_admin_themes') }}
            </a>
        </div>
    </main>
</body>
</html>
