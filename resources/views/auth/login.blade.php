{{--
    Asumsi yang dipakai di file ini (sesuaikan kalau beda dengan struktur repo aktual):
    - Route login tenant didaftarkan di routes/web.php sebagai name('login'), method POST ke route yang sama.
    - Field: login (email atau username), password, remember — cocok dengan LoginController
      yang mem-validasi field 'login' dan fallback Auth::attempt ke email lalu username.
    - Halaman ini berdiri sendiri (bukan @extends ke layout lain) karena guest page biasanya
      punya shell berbeda dari authenticated app shell. Kalau sudah ada layouts/guest.blade.php,
      pindahkan <head> & wrapper <html> ke sana dan ganti file ini jadi @extends('layouts.guest').
    - Font Fraunces & Plus Jakarta Sans di-load lewat Google Fonts CDN langsung di head halaman
      ini (bukan lewat app.css) supaya tidak mempengaruhi bundle area lain yang tidak butuh font ini.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        @view-transition { navigation: auto; }
    </style>
</head>
<body class="antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui;">

    <div class="min-h-screen flex">

        {{-- Panel kiri — brand & signature "buku absen" --}}
        <div class="hidden lg:flex lg:w-[38%] flex-col justify-between p-10 xl:p-12"
             style="background:#1B3A34; color:#F6F3EC; view-transition-name: brand-panel;">
            <div>
                <div class="text-2xl font-semibold" style="font-family:'Fraunces',serif;">EduZone</div>
                <div class="text-sm mt-1" style="color:#9FB8AE;">Platform manajemen sekolah</div>
            </div>

            <div>
                <div class="text-sm font-medium mb-3" style="color:#C9A227;">Absen hari ini</div>
                <div class="flex flex-col gap-2" aria-hidden="true">
                    @php
                        // Baris ke-3 (index 2) ditandai "hadir" — murni dekoratif, tidak terhubung data nyata.
                        $rows = [false, false, true, false, false];
                    @endphp
                    @foreach ($rows as $isPresent)
                        <div class="flex items-center gap-1.5">
                            @for ($i = 0; $i < 6; $i++)
                                <span class="block rounded-full"
                                      style="width:7px; height:7px; background: {{ $isPresent && $i < 4 ? '#C9A227' : 'rgba(246,243,236,0.18)' }};"></span>
                            @endfor
                            @if ($isPresent)
                                <svg class="w-3 h-3 ml-1" style="color:#C9A227;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="text-xs leading-relaxed" style="color:#7C9A8E;">
                Satu platform untuk akademik, absensi, keuangan, dan kesiswaan —
                terisolasi aman per sekolah.
            </p>
        </div>

        {{-- Panel kanan — form login --}}
        <div class="w-full lg:w-[62%] flex items-center justify-center p-8"
             style="background:#F6F3EC; view-transition-name: form-panel;">
            <div class="w-full max-w-sm">

                <h1 class="sr-only">Masuk ke akun sekolah EduZone</h1>

                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs mb-6 hover:underline" style="color:#8A8A80;">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Kembali ke beranda
                </a>

                {{-- Wordmark tampil di mobile saja, karena panel kiri disembunyikan di layar kecil --}}
                <div class="lg:hidden text-xl font-semibold mb-8" style="font-family:'Fraunces',serif; color:#1B3A34;">
                    EduZone
                </div>

                <div class="text-xl font-semibold mb-1" style="font-family:'Fraunces',serif; color:#1B3A34;">
                    Masuk ke sekolah Anda
                </div>
                <p class="text-sm mb-7" style="color:#6B6B63;">
                    Gunakan email dan kata sandi yang terdaftar
                </p>

                @if (session('status'))
                    <div class="mb-5 text-sm rounded-md px-3 py-2" style="background:#EAF3DE; color:#3B6D11;">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 text-sm rounded-md px-3 py-2" style="background:#FCEBEB; color:#A32D2D;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4" x-data="{ showPassword: false }">
                    @csrf

                    <div>
                        <label for="login" class="block text-xs font-medium mb-1.5" style="color:#4A4A44;">
                            Email
                        </label>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="nama@sekolah.sch.id"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full h-10 rounded-md px-3 text-sm border focus:outline-none focus:ring-2"
                            style="border-color:#D8D4C6; background:#fff; color:#1C1C1A; --tw-ring-color:#1B3A34;"
                        >
                        @error('login')
                            <p class="text-xs mt-1" style="color:#A32D2D;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium mb-1.5" style="color:#4A4A44;">
                            Kata sandi
                        </label>
                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                placeholder="Masukkan kata sandi"
                                required
                                autocomplete="current-password"
                                class="w-full h-10 rounded-md pl-3 pr-10 text-sm border focus:outline-none focus:ring-2"
                                style="border-color:#D8D4C6; background:#fff; color:#1C1C1A; --tw-ring-color:#1B3A34;"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2"
                                style="color:#8A8A80;"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            >
                                <svg x-show="!showPassword" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.1A10.9 10.9 0 0112 5c7 0 11 7 11 7a17.5 17.5 0 01-4 4.6M6.2 6.2C3.7 7.9 2 12 2 12s4 7 11 7c1.4 0 2.7-.3 3.9-.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs mt-1" style="color:#A32D2D;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between text-sm mt-0.5">
                        <label class="flex items-center gap-1.5" style="color:#4A4A44;">
                            <input type="checkbox" name="remember" style="accent-color:#1B3A34;">
                            Ingat saya
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="font-medium hover:underline" style="color:#1B3A34;">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>

                    <button
                        type="submit"
                        class="w-full h-[42px] rounded-md text-sm font-medium mt-1.5 transition-opacity hover:opacity-90"
                        style="background:#1B3A34; color:#F6F3EC;"
                    >
                        Masuk
                    </button>
                </form>

                <p class="text-xs text-center mt-6" style="color:#9A9A8E;">
                    Butuh bantuan akses? Hubungi tata usaha sekolah Anda.
                </p>

                @if (Route::has('superadmin.login'))
                    <div class="text-center mt-3">
                        <a href="{{ route('superadmin.login') }}" class="text-xs hover:underline" style="color:#8A8A80;">
                            Masuk sebagai superadmin
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

</body>
</html>