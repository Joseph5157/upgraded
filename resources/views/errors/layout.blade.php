<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }} | {{ config('app.name', 'Portal') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7fb;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top right, #e2e8f0 0%, var(--bg) 40%);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 640px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.08);
        }
        .code {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0369a1;
            margin-bottom: 8px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: clamp(1.4rem, 2vw, 1.9rem);
            line-height: 1.25;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }
        .actions {
            margin-top: 22px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            text-decoration: none;
            font-weight: 600;
            color: var(--text);
            background: #fff;
        }
        .btn.primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .btn.primary:hover { background: var(--accent-hover); }
    </style>
</head>
<body>
    <section class="card" role="alert">
        <div class="code">{{ $status ?? 'Error' }}</div>
        <h1>{{ $title ?? 'Something went wrong' }}</h1>
        <p>{{ $message ?? 'Please try again in a moment.' }}</p>
        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Go Home</a>
            @if (!empty($secondaryUrl) && !empty($secondaryText))
                <a class="btn primary" href="{{ $secondaryUrl }}">{{ $secondaryText }}</a>
            @else
                <a class="btn primary" href="javascript:history.back()">Go Back</a>
            @endif
        </div>
    </section>
</body>
</html>
