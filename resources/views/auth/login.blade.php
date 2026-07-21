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
            --ez-ink: #17152B;
            --ez-muted: #6E6B85;
            --ez-border: #E7E5F0;
            --ez-indigo: #4F46E5;
            --ez-violet: #7C3AED;
            --ez-pink: #DB2777;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--ez-ink);
        }

        /* Background: mesh gradient lembut + dot-grid halus bertema "hari kehadiran" */
        .ez-canvas {
            background-color: #FAFAFC;
            background-image:
                radial-gradient(circle at 15% 10%, rgba(79, 70, 229, 0.10) 0%, transparent 42%),
                radial-gradient(circle at 88% 82%, rgba(219, 39, 119, 0.09) 0%, transparent 45%),
                radial-gradient(circle at 50% 100%, rgba(124, 58, 237, 0.06) 0%, transparent 55%);
        }

        .ez-dotgrid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(79, 70, 229, 0.14) 1px, transparent 1.4px);
            background-size: 26px 26px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black 15%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black 15%, transparent 75%);
        }

        /* Beberapa titik "hadir" nyala lebih terang, isyarat halus ke fitur absensi tanpa jadi ikon literal */
        .ez-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: linear-gradient(135deg, var(--ez-indigo), var(--ez-pink));
            opacity: 0.55;
            filter: blur(0.2px);
        }

        .ez-logo-mark {
            background: linear-gradient(135deg, var(--ez-indigo) 0%, var(--ez-violet) 55%, var(--ez-pink) 100%);
            box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.45);
        }

        .ez-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border: 1px solid var(--ez-border);
            box-shadow:
                0 1px 2px rgba(23, 21, 43, 0.04),
                0 24px 48px -16px rgba(79, 70, 229, 0.20),
                0 8px 24px -12px rgba(219, 39, 119, 0.10);
        }

        .ez-eyebrow {
            letter-spacing: 0.08em;
        }

        .ez-input {
            background: #FBFBFD;
            border: 1.5px solid var(--ez-border);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .ez-input:focus {
            outline: none;
            background: #FFFFFF;
            border-color: var(--ez-indigo);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .ez-input.ez-input-error {
            border-color: #F87171;
            background: #FEF2F2;
        }

        .ez-btn {
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, var(--ez-indigo) 0%, var(--ez-violet) 50%, var(--ez-pink) 100%);
            box-shadow: 0 14px 28px -10px rgba(79, 70, 229, 0.55);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .ez-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px -10px rgba(79, 70, 229, 0.65);
        }

        .ez-btn:active {
            transform: translateY(0);
        }

        /* Sapuan cahaya halus saat hover — detail premium, bukan animasi yang mengganggu */
        .ez-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -60%;
            width: 40%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent);
            transform: skewX(-20deg);
            transition: left 0.5s ease;
        }

        .ez-btn:hover::after {
            left: 130%;
        }

        @media (prefers-reduced-motion: reduce) {
            .ez-btn, .ez-btn::after, .ez-input { transition: none; }
            .ez-btn:hover { transform: none; }
        }
    </style>
</head>
<body class="h-full">

<div class="ez-canvas min-h-screen relative overflow-hidden flex items-center justify-center px-6 py-12">

    {{-- Tekstur latar: dot-grid halus + beberapa titik "aktif" --}}
    <div class="ez-dotgrid" aria-hidden="true"></div>
    <div class="ez-dot" style="top: 22%; left: 18%;" aria-hidden="true"></div>
    <div class="ez-dot" style="top: 68%; left: 12%;" aria-hidden="true"></div>
    <div class="ez-dot" style="top: 30%; left: 84%;" aria-hidden="true"></div>
    <div class="ez-dot" style="top: 75%; left: 80%;" aria-hidden="true"></div>
    <div class="ez-dot" style="top: 14%; left: 55%;" aria-hidden="true"></div>

    <div class="relative z-10 w-full max-w-[420px]">

        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <a href="/" class="inline-flex items-center gap-2.5 group">
                <div class="ez-logo-mark w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                    <svg class="w-5.5 h-5.5 text-white" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                <span class="text-xl font-extrabold tracking-tight" style="color: var(--ez-ink);">EduZone</span>
            </a>
        </div>

        {{-- Kartu login --}}
        <div class="ez-card rounded-[28px] px-8 py-10 sm:px-10">

            {{-- Header --}}
            <div class="mb-8 text-center">
                <p class="ez-eyebrow text-[11px] font-bold uppercase mb-2" style="color: var(--ez-violet);">Portal Sekolah</p>
                <h1 class="text-[26px] font-extrabold tracking-tight leading-tight mb-1.5" style="color: var(--ez-ink);">
                    Selamat datang kembali
                </h1>
                <p class="text-sm" style="color: var(--ez-muted);">
                    Masuk untuk melanjutkan ke akun EduZone kamu
                </p>
            </div>

            {{-- Error dari session (misal akun dinonaktifkan) --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-700 font-medium">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Session status (sukses, dll) --}}
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-emerald-700 font-medium">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                {{-- Field: Email atau Username --}}
                <div>
                    <label for="login" class="block text-xs font-bold uppercase ez-eyebrow mb-2" style="color: var(--ez-muted);">
                        Email atau Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="var(--ez-muted)" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="email@sekolah.sch.id"
                            autocomplete="username"
                            autofocus
                            class="ez-input w-full pl-11 pr-4 py-3.5 rounded-2xl text-sm placeholder:text-gray-400 {{ $errors->has('login') ? 'ez-input-error' : '' }}"
                            style="color: var(--ez-ink);"
                        />
                    </div>
                    @error('login')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field: Password --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-bold uppercase ez-eyebrow" style="color: var(--ez-muted);">
                            Password
                        </label>
                        <a href="#" class="text-xs font-semibold hover:opacity-70 transition-opacity" style="color: var(--ez-indigo);">
                            Lupa password?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="var(--ez-muted)" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="ez-input w-full pl-11 pr-12 py-3.5 rounded-2xl text-sm placeholder:text-gray-400 {{ $errors->has('password') ? 'ez-input-error' : '' }}"
                            style="color: var(--ez-ink);"
                        />
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center transition-colors"
                            style="color: var(--ez-muted);"
                            tabindex="-1"
                            aria-label="Tampilkan password"
                        >
                            <svg id="eyeOpen" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg id="eyeClosed" class="w-[18px] h-[18px] hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center pt-1">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-offset-0 cursor-pointer"
                        style="accent-color: var(--ez-indigo);"
                    />
                    <label for="remember" class="ml-2.5 text-sm cursor-pointer select-none" style="color: var(--ez-muted);">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                {{-- Tombol submit --}}
                <button
                    type="submit"
                    class="ez-btn w-full py-4 px-6 rounded-2xl text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="--tw-ring-color: var(--ez-indigo);"
                >
                    Masuk ke EduZone
                </button>
            </form>

            {{-- Kembali ke landing --}}
            <div class="mt-8 pt-6 text-center" style="border-top: 1px solid var(--ez-border);">
                <a
                    href="/"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold hover:opacity-70 transition-opacity"
                    style="color: var(--ez-muted);"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Kembali ke halaman utama
                </a>
            </div>
        </div>

        {{-- Footer copyright --}}
        <p class="mt-7 text-center text-xs" style="color: var(--ez-muted);">
            &copy; {{ date('Y') }} EduZone. Platform Manajemen Sekolah Modern.
        </p>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeOpen.classList.toggle('hidden', isPassword);
            eyeClosed.classList.toggle('hidden', !isPassword);
            toggleBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
        });
    }
</script>

</body>
</html>
