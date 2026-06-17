<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Masuk — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        .gradient-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 40%, #db2777 100%);
        }

        .gradient-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            transition: opacity 0.2s ease, transform 0.1s ease;
        }

        .gradient-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .gradient-btn:active {
            transform: translateY(0);
        }

        .pattern-overlay {
            background-image:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.06) 0%, transparent 50%),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .input-field {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .floating-card {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .stat-badge {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="h-full bg-gray-50">

<div class="min-h-screen flex">

    {{-- ============================================================ --}}
    {{-- KIRI: Panel Branding --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden gradient-bg pattern-overlay flex-col justify-between p-12">

        {{-- Dekorasi lingkaran --}}
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, white, transparent); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full opacity-10" style="background: radial-gradient(circle, white, transparent); transform: translate(-30%, 30%);"></div>

        {{-- Header logo --}}
        <div class="relative z-10">
            <a href="/" class="inline-flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center group-hover:bg-white/30 transition-colors">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white tracking-tight">EduZone</span>
            </a>
        </div>

        {{-- Konten tengah --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center py-12">
            {{-- Quote/tagline --}}
            <div class="mb-8">
                <p class="text-indigo-200 text-sm font-medium uppercase tracking-widest mb-3">Platform Manajemen Sekolah</p>
                <h2 class="text-4xl xl:text-5xl font-bold text-white leading-tight mb-4">
                    Satu Platform,<br/>
                    <span class="text-pink-300">Semua Kebutuhan</span><br/>
                    Sekolahmu
                </h2>
                <p class="text-indigo-100 text-lg leading-relaxed max-w-md">
                    Kelola administrasi, akademik, keuangan, dan laporan sekolah dalam satu sistem yang terintegrasi dan mudah digunakan.
                </p>
            </div>

            {{-- Floating card ilustrasi --}}
            <div class="floating-card mb-8">
                <div class="stat-badge rounded-2xl p-5 max-w-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-white font-semibold text-sm">Sistem Multi-Tenant</span>
                    </div>
                    <p class="text-indigo-100 text-sm leading-relaxed">
                        Setiap sekolah punya data yang terisolasi dan aman. Akses kapan saja, dari mana saja.
                    </p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="stat-badge rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">500+</p>
                    <p class="text-indigo-200 text-xs mt-1">Sekolah Aktif</p>
                </div>
                <div class="stat-badge rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">50K+</p>
                    <p class="text-indigo-200 text-xs mt-1">Pengguna</p>
                </div>
                <div class="stat-badge rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">99.9%</p>
                    <p class="text-indigo-200 text-xs mt-1">Uptime</p>
                </div>
            </div>
        </div>

        {{-- Footer panel kiri --}}
        <div class="relative z-10">
            <p class="text-indigo-300 text-sm">
                Belum punya akun?
                <a href="mailto:sales@eduzone.id" class="text-white font-medium hover:text-pink-300 transition-colors underline underline-offset-2">
                    Hubungi kami
                </a>
            </p>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- KANAN: Form Login --}}
    {{-- ============================================================ --}}
    <div class="w-full lg:w-1/2 xl:w-[45%] flex items-center justify-center px-6 py-12 sm:px-12 bg-white">
        <div class="w-full max-w-md">

            {{-- Logo (mobile only) --}}
            <div class="lg:hidden mb-8 text-center">
                <a href="/" class="inline-flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center gradient-bg">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">EduZone</span>
                </a>
            </div>

            {{-- Header form --}}
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Selamat datang</h1>
                <p class="text-gray-500">Masuk ke akun EduZone kamu</p>
            </div>

            {{-- Error dari session (misal akun dinonaktifkan) --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
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
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-green-700 font-medium">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                {{-- Field: Email atau Username --}}
                <div>
                    <label for="login" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Email atau Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4.5 h-4.5 text-gray-400 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="email@sekolah.sch.id atau username"
                            autocomplete="username"
                            autofocus
                            class="input-field w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('login') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-gray-900 text-sm placeholder-gray-400 focus:bg-white"
                        />
                    </div>
                    @error('login')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field: Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-semibold text-gray-700">
                            Password
                        </label>
                        <a href="#" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                            Lupa password?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="input-field w-full pl-11 pr-12 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-gray-900 text-sm placeholder-gray-400 focus:bg-white"
                        />
                        {{-- Toggle password visibility --}}
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            tabindex="-1"
                            aria-label="Tampilkan password"
                        >
                            <svg id="eyeOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg id="eyeClosed" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer"
                    />
                    <label for="remember" class="ml-2.5 text-sm text-gray-600 cursor-pointer select-none">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                {{-- Tombol submit --}}
                <button
                    type="submit"
                    class="gradient-btn w-full py-3.5 px-6 rounded-xl text-white font-semibold text-sm shadow-lg shadow-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Masuk ke EduZone
                </button>
            </form>

            {{-- Divider & back to landing --}}
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <a
                    href="/"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 transition-colors font-medium"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Kembali ke halaman utama
                </a>
            </div>

            {{-- Footer copyright --}}
            <p class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} EduZone. Platform Manajemen Sekolah Modern.
            </p>

        </div>
    </div>

</div>

<script>
    // Toggle password visibility
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
