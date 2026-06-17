<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Superadmin — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: #080c14;
        }

        /* Grid background */
        .grid-bg {
            background-image:
                linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Glow radial */
        .glow-top {
            position: fixed;
            top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 400px;
            background: radial-gradient(ellipse, rgba(99,102,241,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Card */
        .login-card {
            background: rgba(15, 20, 35, 0.8);
            border: 1px solid rgba(99, 102, 241, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* Input */
        .sa-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e2e8f0;
            transition: all 0.2s ease;
        }
        .sa-input::placeholder { color: #475569; }
        .sa-input:focus {
            outline: none;
            background: rgba(99,102,241,0.08);
            border-color: rgba(99,102,241,0.5);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .sa-input.error {
            border-color: rgba(239,68,68,0.5);
            background: rgba(239,68,68,0.05);
        }

        /* Button */
        .sa-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .sa-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .sa-btn:hover::before { opacity: 1; }
        .sa-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 32px rgba(79,70,229,0.4); }
        .sa-btn:active { transform: translateY(0); }
        .sa-btn span { position: relative; z-index: 1; }

        /* Divider */
        .divider {
            border-color: rgba(255,255,255,0.06);
        }

        /* Badge */
        .secure-badge {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.2);
            color: #6ee7b7;
        }

        /* Scan line animation */
        @keyframes scan {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(400%); }
        }
        .scan-line {
            position: absolute;
            left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,0.4), transparent);
            animation: scan 3s linear infinite;
            pointer-events: none;
        }

        /* Logo pulse */
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(99,102,241,0.4); }
            70% { box-shadow: 0 0 0 10px rgba(99,102,241,0); }
            100% { box-shadow: 0 0 0 0 rgba(99,102,241,0); }
        }
        .logo-pulse {
            animation: pulse-ring 2.5s ease-out infinite;
        }

        /* Error shake */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .shake { animation: shake 0.4s ease; }
    </style>
</head>
<body class="h-full grid-bg">

<div class="glow-top"></div>

<div class="min-h-screen flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-md">

        {{-- ── Logo & Header ── --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4 logo-pulse relative overflow-hidden"
                 style="background: linear-gradient(135deg, #1e1b4b, #312e81);">
                <div class="scan-line"></div>
                <svg class="w-7 h-7 text-indigo-400 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-white mb-1">
                EduZone <span style="color:#818cf8;">Control Panel</span>
            </h1>
            <p class="text-slate-500 text-sm">Area terbatas — khusus superadmin</p>
        </div>

        {{-- ── Card ── --}}
        <div class="login-card rounded-2xl p-8 relative overflow-hidden">

            {{-- Secure badge --}}
            <div class="flex items-center justify-center mb-6">
                <div class="secure-badge inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full">
                    <svg style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Koneksi Terenkripsi
                </div>
            </div>

            {{-- Error alert --}}
            @if ($errors->any())
            <div class="mb-6 rounded-xl p-4 flex gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);" id="error-box">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:#f87171;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <div>
                    @foreach ($errors->all() as $error)
                    <p class="text-sm font-medium" style="color:#fca5a5;">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-5" id="login-form">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold mb-2 uppercase tracking-widest" style="color:#94a3b8;">
                        Email Superadmin
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4" style="color:#475569;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                        </div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="superadmin@eduzone.id"
                            autocomplete="email"
                            autofocus
                            class="sa-input {{ $errors->has('email') ? 'error' : '' }} w-full pl-10 pr-4 py-3 rounded-xl text-sm"
                        >
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-xs font-semibold mb-2 uppercase tracking-widest" style="color:#94a3b8;">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4" style="color:#475569;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            class="sa-input {{ $errors->has('password') ? 'error' : '' }} w-full pl-10 pr-12 py-3 rounded-xl text-sm"
                        >
                        <button type="button" id="togglePwd"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center transition-colors"
                            style="color:#475569;"
                            tabindex="-1">
                            <svg id="eyeOn" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg id="eyeOff" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-1">
                    <button type="submit" class="sa-btn w-full py-3.5 rounded-xl text-white font-bold text-sm">
                        <span class="flex items-center justify-center gap-2">
                            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                            Masuk ke Control Panel
                        </span>
                    </button>
                </div>
            </form>

            {{-- Divider --}}
            <div class="mt-7 pt-6 border-t divider">
                <div class="flex items-center justify-between text-xs" style="color:#475569;">
                    <span>© {{ date('Y') }} EduZone</span>
                    <a href="/login" class="hover:text-indigo-400 transition-colors inline-flex items-center gap-1">
                        <svg style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Login Sekolah
                    </a>
                </div>
            </div>
        </div>

        {{-- Warning --}}
        <p class="text-center text-xs mt-4" style="color:#334155;">
            ⚠️ Aktivitas login dicatat dan dimonitor
        </p>

    </div>
</div>

<script>
    // Toggle password
    const togglePwd = document.getElementById('togglePwd');
    const pwdInput  = document.getElementById('password');
    const eyeOn     = document.getElementById('eyeOn');
    const eyeOff    = document.getElementById('eyeOff');

    togglePwd?.addEventListener('click', () => {
        const show = pwdInput.type === 'password';
        pwdInput.type = show ? 'text' : 'password';
        eyeOn.classList.toggle('hidden', show);
        eyeOff.classList.toggle('hidden', !show);
    });

    // Shake error box on load
    const errBox = document.getElementById('error-box');
    if (errBox) errBox.classList.add('shake');
</script>
</body>
</html>
