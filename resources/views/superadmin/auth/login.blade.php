<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Superadmin — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/superadmin.js'])
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        :root {
            --bg: #060914;
            --surface: rgba(15, 23, 42, 0.84);
            --surface-strong: rgba(15, 23, 42, 0.95);
            --border: rgba(99, 102, 241, 0.14);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #818cf8;
            --accent-soft: rgba(99, 102, 241, 0.14);
            --danger: #fda4af;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top left, rgba(99,102,241,0.16), transparent 22%),
                        radial-gradient(circle at bottom right, rgba(236,72,153,0.12), transparent 25%),
                        linear-gradient(180deg, #02060f 0%, #090e1d 100%);
            color: var(--text);
        }

        .page-shell {
            position: relative;
            overflow: hidden;
        }

        .hero-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top center, rgba(99, 102, 241, 0.16), transparent 20%),
                        radial-gradient(circle at bottom left, rgba(236, 72, 153, 0.14), transparent 20%);
            pointer-events: none;
            opacity: 0.95;
        }

        .grid-bg {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
        }

        .auth-panel {
            width: 100%;
            max-width: 1080px;
            display: grid;
            gap: 2.25rem;
            grid-template-columns: minmax(320px, 1.15fr) minmax(320px, 0.85fr);
            align-items: stretch;
        }

        .auth-intro,
        .auth-card {
            border-radius: 32px;
            border: 1px solid var(--border);
            background: var(--surface);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .auth-intro {
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2rem;
        }

        .auth-card {
            padding: 2.5rem;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.22);
            max-width: 520px;
            justify-self: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-card-main {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        .control-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 9999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(148,163,184,0.1);
            color: var(--accent);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .auth-card-heading {
            margin-top: 0.8rem;
            font-size: clamp(1.8rem, 2.2vw, 2.3rem);
            line-height: 1.08;
            color: #f8fafc;
        }

        .auth-card-copy {
            line-height: 1.8;
            color: var(--muted);
        }

        .alert-box p {
            margin: 0;
        }

        .link-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-top: 0.75rem;
            font-size: 0.95rem;
            color: var(--muted);
            flex-wrap: wrap;
        }

        .link-row a {
            color: var(--accent);
            text-decoration: none;
        }

        .link-row a:hover {
            color: #c7d2fe;
        }

        .footer-row {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--muted);
            font-size: 0.82rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .cta-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            background: rgba(99,102,241,0.12);
            color: #c7d2fe;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .warning-text {
            margin-top: 1.25rem;
            font-size: 0.84rem;
            color: #94a3b8;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .auth-panel { grid-template-columns: 1fr; }
            .auth-card { max-width: 100%; }
            .auth-card { justify-self: stretch; }
        }

        @media (max-width: 640px) {
            .auth-shell { padding: 2rem 1rem; }
            .auth-intro, .auth-card { border-radius: 28px; }
            .auth-card { padding: 2rem; }
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.8rem 1.15rem;
            background: rgba(255,255,255,0.05);
            border-radius: 9999px;
            border: 1px solid rgba(148,163,184,0.16);
            color: var(--accent);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .eyebrow-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #818cf8, #ec4899);
        }

        .hero-title {
            margin-top: 1.25rem;
            margin-bottom: 1rem;
            font-size: clamp(2.4rem, 4vw, 3.5rem);
            line-height: 1.02;
            letter-spacing: -0.05em;
            color: #f8fafc;
        }

        .hero-copy {
            max-width: 38rem;
            line-height: 1.85;
            color: var(--muted);
            font-size: 1rem;
        }

        .feature-grid {
            display: grid;
            gap: 1rem;
            margin-top: 2rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .feature-card {
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.04);
            padding: 1.35rem 1.4rem;
            color: #e2e8f0;
        }

        .feature-card strong {
            display: block;
            margin-bottom: 0.55rem;
            color: #f8fafc;
        }

        .field-label {
            display: block;
            margin-bottom: 0.65rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .input-group {
            position: relative;
        }

        .sa-input {
            width: 100%;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.06);
            color: var(--text);
            padding: 1rem 1rem 1rem 3.4rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .sa-input::placeholder {
            color: rgba(226,232,240,0.55);
        }

        .sa-input:focus {
            outline: none;
            border-color: rgba(129,140,248,0.65);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 0 4px rgba(129,140,248,0.12);
        }

        .sa-input.error {
            border-color: rgba(251,146,60,0.45);
            background: rgba(248,113,113,0.08);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(148,163,184,0.8);
        }

        .sa-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            border-radius: 20px;
            border: none;
            padding: 1rem 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 55%, #ec4899 100%);
            color: white;
            box-shadow: 0 18px 32px -12px rgba(99,102,241,0.7);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .sa-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 40px -14px rgba(99,102,241,0.75);
        }

        .link-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .link-row a {
            color: var(--accent);
            text-decoration: none;
        }

        .link-row a:hover {
            color: #c7d2fe;
        }

        .alert-box {
            border-radius: 20px;
            border: 1px solid rgba(248,113,113,0.2);
            background: rgba(248,113,113,0.08);
            padding: 1rem 1.25rem;
            color: #fecaca;
            margin-bottom: 1rem;
        }

        .footer-row {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .cta-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            background: rgba(99,102,241,0.12);
            color: #c7d2fe;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .warning-text {
            margin-top: 1rem;
            font-size: 0.78rem;
            color: #94a3b8;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .auth-panel { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .auth-shell { padding: 2rem 1rem; }
            .auth-intro, .auth-card { border-radius: 28px; }
        }
    </style>
</head>
<body class="page-shell grid-bg">
    <div class="hero-glow"></div>
    <div class="auth-shell">
        <div class="auth-panel">
            <section class="auth-intro">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    Superadmin login
                </div>
                <h1 class="hero-title">Akses kontrol panel EduZone dengan aman dan cepat.</h1>
                <p class="hero-copy">Halaman masuk ini didesain khusus untuk superadmin: bersih, mudah digunakan, dan menampilkan kesan profesional tanpa mengurangi fokus. Cocok untuk area manajemen sekolah yang serius.</p>

                <div class="feature-grid">
                    <div class="feature-card">
                        <strong>Keamanan prioritas</strong>
                        Akses otentikasi aman dengan tampilan yang konsisten.
                    </div>
                    <div class="feature-card">
                        <strong>Experience premium</strong>
                        Layout rapi dan fokus untuk admin yang mengelola banyak sekolah.
                    </div>
                    <div class="feature-card">
                        <strong>Informasi jelas</strong>
                        Form login mudah dibaca dengan elemen yang teratur.
                    </div>
                    <div class="feature-card">
                        <strong>Responsif</strong>
                        Tampilan tetap lapang pada desktop dan tablet.
                    </div>
                </div>
            </section>

            <section class="auth-card">
                <div class="auth-card-main">
                    <div>
                        <p class="control-badge">
                            <span class="eyebrow-dot"></span>
                            Control panel akses
                        </p>
                        <h2 class="auth-card-heading">Selamat datang, Superadmin.</h2>
                        <p class="auth-card-copy">Masuk dengan email superadmin untuk memantau sekolah, langganan, dan aktivitas keseluruhan dalam satu dashboard.</p>
                    </div>

                @if ($errors->any())
                    <div class="alert-box">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert-box" style="background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.2); color: #a7f3d0;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-5" id="login-form">
                    @csrf

                    <div>
                        <label for="email" class="field-label">Email Superadmin</label>
                        <div class="input-group">
                            <span class="input-icon">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="superadmin@eduzone.id"
                                autocomplete="email"
                                autofocus
                                class="sa-input {{ $errors->has('email') ? 'error' : '' }}"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="field-label">Password</label>
                        <div class="input-group">
                            <span class="input-icon">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••••••"
                                autocomplete="current-password"
                                class="sa-input {{ $errors->has('password') ? 'error' : '' }}"
                            >
                        </div>
                    </div>

                    <div class="link-row">
                        <label class="inline-flex items-center gap-3 text-slate-300" style="font-weight: 600;">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                {{ old('remember') ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-slate-500 bg-slate-950 text-indigo-500 focus:ring-indigo-500"
                            />
                            Ingat saya di perangkat ini
                        </label>
                        <a href="#">Lupa password?</a>
                    </div>

                    <button type="submit" class="sa-btn">
                        <span>Masuk ke Control Panel</span>
                    </button>
                </form>

                <div class="footer-row">
                    <span class="cta-pill">Superadmin</span>
                    <span>© {{ date('Y') }} EduZone</span>
                </div>

                <p class="warning-text">Aktivitas login dicatat dan dimonitor untuk keamanan akses.</p>
            </section>
        </div>
    </div>
</body>
</html>
