<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Masuk — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        :root {
            --ez-ink: #111827;
            --ez-muted: #6B7280;
            --ez-border: rgba(148, 163, 184, 0.18);
            --ez-indigo: #4338ca;
            --ez-violet: #7c3aed;
            --ez-pink: #ec4899;
            --ez-surface: rgba(255, 255, 255, 0.85);
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--ez-ink);
            background: radial-gradient(circle at top left, rgba(79, 70, 229, 0.16), transparent 28%),
                        radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.14), transparent 30%),
                        linear-gradient(180deg, #F8FAFC 0%, #EFF2FF 100%);
        }

        .page-shell {
            position: relative;
            overflow: hidden;
        }

        .hero-shape {
            position: absolute;
            border-radius: 9999px;
            filter: blur(48px);
            opacity: 0.45;
            pointer-events: none;
        }

        .hero-shape.one {
            width: 420px;
            height: 420px;
            top: -110px;
            left: -110px;
            background: rgba(79, 70, 229, 0.16);
        }

        .hero-shape.two {
            width: 320px;
            height: 320px;
            bottom: -120px;
            right: -110px;
            background: rgba(236, 72, 153, 0.14);
        }

        .hero-gradient {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.32), transparent 42%);
            pointer-events: none;
        }

        .login-card {
            background: var(--ez-surface);
            border: 1px solid var(--ez-border);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .content-panel {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.22);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .section-divider {
            height: 1px;
            background: linear-gradient(to right, rgba(148, 163, 184, 0), rgba(148, 163, 184, 0.24), rgba(148, 163, 184, 0));
        }

        .field-icon {
            color: #9ca3af;
        }

        .input-glow {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.26);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .input-glow:focus {
            outline: none;
            border-color: rgba(67, 56, 202, 0.7);
            box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.12);
        }

        .input-error {
            border-color: #fca5a5 !important;
            background: #fef2f2;
        }

        .primary-btn {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #4338ca 0%, #7c3aed 55%, #ec4899 100%);
            box-shadow: 0 18px 32px -12px rgba(67, 56, 202, 0.5);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 44px -14px rgba(67, 56, 202, 0.55);
        }

        .primary-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.35), transparent 45%);
            opacity: 0.25;
            pointer-events: none;
        }

        .feature-pill {
            background: rgba(67, 56, 202, 0.08);
            color: #4338ca;
        }

        .top-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.75rem 1rem;
            border-radius: 9999px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(148, 163, 184, 0.24);
            color: #4338ca;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 12px 35px -24px rgba(15, 23, 42, 0.3);
        }

        .top-badge span {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #4338ca, #ec4899);
        }

        @media (prefers-reduced-motion: reduce) {
            .primary-btn, .primary-btn::before, .input-glow { transition: none !important; }
        }
    </style>
</head>
<body class="page-shell">
    <div class="hero-shape one"></div>
    <div class="hero-shape two"></div>
    <div class="hero-gradient"></div>

    <div class="min-h-screen flex items-start justify-center px-6 py-12">
        <div class="w-full max-w-6xl">
            <div class="grid gap-10 lg:grid-cols-[1.15fr_0.9fr] items-start">

                <div class="space-y-8 pt-6">
                    <div class="content-panel rounded-[32px] p-8 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.32em] font-semibold text-indigo-700">EduZone login</p>
                        <h1 class="mt-5 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-950">Tampilan login lebih terstruktur dan premium.</h1>
                        <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">Sistem masuk yang bersih membuat pengguna lebih nyaman dan meningkatkan kesan profesional bagi sekolah. Desain ini tetap ringan dan mudah digunakan pada desktop maupun tablet.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-[28px] border border-slate-200/80 bg-white/85 p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">Tata letak konsisten</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Form login terpusat dengan informasi samping yang bersih.</p>
                        </div>
                        <div class="rounded-[28px] border border-slate-200/80 bg-white/85 p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">Rapi dan mudah dibaca</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Kontras warna yang lembut menjaga tampilan tetap elegan.</p>
                        </div>
                    </div>
                </div>

                <div class="login-card rounded-[32px] border border-slate-300/60 p-8 sm:p-10">
                    <div class="mb-8">
                        <div class="top-badge">
                            <span></span>
                            Premium login experience
                        </div>
                        <h2 class="mt-6 text-3xl font-semibold text-slate-900">Selamat datang kembali</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">Masuk menggunakan kredensial sekolah untuk melanjutkan pekerjaan administrasi dengan aman dan cepat.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                        @csrf

                        <div>
                            <label for="login" class="block text-sm font-semibold text-slate-700 mb-2">Email atau Username</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <svg class="field-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16.5 8.25a3 3 0 11-6 0 3 3 0 016 0zm-8.364 7.141A4.5 4.5 0 0112 15.75h0a4.5 4.5 0 014.864 3.641M6 20.25h12" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    id="login"
                                    name="login"
                                    value="{{ old('login') }}"
                                    placeholder="email@sekolah.sch.id"
                                    autocomplete="username"
                                    autofocus
                                    class="input-glow block w-full rounded-[28px] py-4 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 {{ $errors->has('login') ? 'input-error' : '' }}"
                                />
                            </div>
                            @error('login')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{ show: false }">
                            <div class="flex items-center justify-between mb-2">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Lupa password?</a>
                            </div>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <svg class="field-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 11.25a2.25 2.25 0 00-2.25 2.25v1.5h4.5v-1.5A2.25 2.25 0 0012 11.25zm0 0V9.75a3 3 0 00-6 0v1.5m6-1.5h6a2.25 2.25 0 012.25 2.25v3.75a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 14.25V10.5A2.25 2.25 0 016.75 8.25h1.5" />
                                    </svg>
                                </span>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••••••"
                                    autocomplete="current-password"
                                    class="input-glow block w-full rounded-[28px] py-4 pl-12 pr-14 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 {{ $errors->has('password') ? 'input-error' : '' }}"
                                />
                                <button
                                    type="button"
                                    @click="show = !show"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 transition hover:text-slate-900"
                                    aria-label="Toggle password visibility"
                                >
                                    <svg x-show="!show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1.5 12s4.5-7.5 10.5-7.5S22.5 12 22.5 12 18 19.5 12 19.5 1.5 12 1.5 12z" />
                                        <path d="M12 9.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z" />
                                    </svg>
                                    <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5.82 5.82l12.36 12.36" />
                                        <path d="M8.22 8.22A7.5 7.5 0 0112 6.75c5.25 0 10.5 6.75 10.5 6.75s-1.5 1.875-4.2 3.75" />
                                        <path d="M4.5 18.75c1.5-1.5 3.75-3.75 7.5-3.75" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center text-sm text-slate-600">
                                <input
                                    type="checkbox"
                                    id="remember"
                                    name="remember"
                                    {{ old('remember') ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-3">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <button type="submit" class="primary-btn w-full rounded-[28px] py-4 text-sm font-semibold text-white shadow-lg">
                            Masuk ke EduZone
                        </button>
                    </form>

                    <div class="mt-8 space-y-4">
                        <div class="section-divider"></div>
                        <p class="text-center text-sm text-slate-500">
                            Belum punya akun? Hubungi admin sekolah untuk aktivasi akses.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                            <span class="inline-flex items-center rounded-full px-3 py-1 feature-pill">Cepat</span>
                            <span class="inline-flex items-center rounded-full px-3 py-1 feature-pill">Rapi</span>
                            <span class="inline-flex items-center rounded-full px-3 py-1 feature-pill">Terpercaya</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
