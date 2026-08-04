{{--
    Asumsi yang dipakai di file ini (sesuaikan kalau beda dengan struktur repo aktual):
    - Route: POST route('superadmin.login'), sesuai pola penamaan {area}.{resource}.{action}
      di SKILL.md. Route ini didaftarkan di routes/superadmin.php dengan middleware `guest`
      (bukan `superadmin`, karena halaman ini justru dipakai SEBELUM login).
    - Rate limiting 5x/menit/IP (lihat ARCHITECTURE.md §7) diasumsikan pakai Laravel
      RateLimiter/ThrottleRequests bawaan, yang melempar ValidationException dengan pesan
      di $errors->first() saat limit tercapai — makanya blok error di bawah generik,
      bisa nangkep pesan validasi biasa maupun pesan throttle sekaligus.
    - Halaman ini berdiri sendiri (bukan @extends) dengan alasan sama seperti login tenant:
      shell superadmin (dark, terpisah total dari tenant) belum tentu sudah punya layout khusus.
      Kalau resources/views/layouts/superadmin-guest.blade.php sudah ada, pindahkan <head> ke situ.
--}}
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Superadmin — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/superadmin.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; background:#12151A; }
        @view-transition { navigation: auto; }
    </style>
</head>
<body class="antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; background:#12151A;">

    <div class="min-h-screen flex">

        {{-- Panel kiri — form login (dimirror dari tenant, biar posisinya "ketuker") --}}
        <div class="w-full lg:w-[62%] flex items-center justify-center p-8 order-2 lg:order-1"
             style="view-transition-name: form-panel;">
            <div class="w-full max-w-sm">

                <h1 class="sr-only">Masuk sebagai superadmin EduZone</h1>

                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs mb-6 hover:underline" style="color:#6B7268;">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Kembali ke beranda
                </a>

                <div class="lg:hidden text-xl font-semibold mb-8" style="font-family:'Fraunces',serif; color:#F6F3EC;">
                    EduZone
                </div>

                <div class="text-xl font-semibold mb-1" style="font-family:'Fraunces',serif; color:#F6F3EC;">
                    Masuk sebagai superadmin
                </div>
                <p class="text-sm mb-7" style="color:#8A8F86;">
                    Akses terbatas untuk tim penyedia platform
                </p>

                @if ($errors->any())
                    <div class="mb-5 text-sm rounded-md px-3 py-2" style="background:#2E1414; color:#F09595; border:1px solid #4A1F1F;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('superadmin.login') }}" class="flex flex-col gap-4" x-data="{ showPassword: false }">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-medium mb-1.5" style="color:#A8ADA3;">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="admin@eduzone.id"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full h-10 rounded-md px-3 text-sm border focus:outline-none focus:ring-2"
                            style="border-color:#33372F; background:#1E221C; color:#F6F3EC; --tw-ring-color:#E0B23A;"
                        >
                        @error('email')
                            <p class="text-xs mt-1" style="color:#F09595;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium mb-1.5" style="color:#A8ADA3;">
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
                                style="border-color:#33372F; background:#1E221C; color:#F6F3EC; --tw-ring-color:#E0B23A;"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2"
                                style="color:#6B7268;"
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
                            <p class="text-xs mt-1" style="color:#F09595;">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full h-[42px] rounded-md text-sm font-medium mt-1.5 transition-opacity hover:opacity-90"
                        style="background:#E0B23A; color:#1A1500;"
                    >
                        Masuk
                    </button>
                </form>

                <div class="flex items-center gap-1.5 text-xs mt-5" style="color:#6B7268;">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" stroke-linecap="round"/>
                        <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Percobaan login dibatasi 5x per menit demi keamanan
                </div>
            </div>
        </div>

        {{-- Panel kanan — brand & signature "grid tenant" (dimirror dari tenant) --}}
        <div class="hidden lg:flex lg:w-[38%] flex-col justify-between p-10 xl:p-12 order-1 lg:order-2"
             style="background:#181C17; border-left:1px solid #2A2E28; view-transition-name: brand-panel;">
            <div class="text-left">
                <div class="text-2xl font-semibold" style="font-family:'Fraunces',serif; color:#F6F3EC;">EduZone</div>
                <div class="text-xs mt-1 tracking-wide" style="color:#E0B23A; font-family:'JetBrains Mono',monospace;">
                    SUPERADMIN
                </div>
            </div>

            <div>
                <div class="text-sm font-medium mb-3" style="color:#E0B23A;">Tenant terpantau</div>
                <div class="grid grid-cols-6 gap-1.5" style="max-width:150px;" aria-hidden="true">
                    @php
                        // Kotak yang "aktif" murni dekoratif, tidak terhubung data tenant nyata.
                        $activeCells = [3, 9, 16, 22];
                    @endphp
                    @for ($i = 0; $i < 24; $i++)
                        <span class="block rounded"
                              style="width:18px; height:18px; background: {{ in_array($i, $activeCells) ? '#E0B23A' : '#2A2E28' }};"></span>
                    @endfor
                </div>
            </div>

            <p class="text-xs leading-relaxed" style="color:#6B7268;">
                Akses lintas sekolah untuk subscription, audit, dan manajemen platform.
            </p>
        </div>
    </div>

</body>
</html>