{{--
    Layout halaman publik pendaftaran sekolah. Mengikuti pola auth/login.blade.php:
    split panel (hijau tua kiri, krem kanan), font Fraunces + Plus Jakarta Sans dimuat
    lewat <link> di head (bukan app.css), entry Vite area tenant (sama dengan landing/login).

    Section yang dipakai halaman anak:
    - title    : judul tab
    - panel    : (opsional) isi panel kiri; kalau kosong, tampil ornamen titik "Absen hari ini" seperti login
    - centered : (opsional) isi apa saja untuk menengahkan konten secara vertikal (halaman pendek)
    - content  : isi panel kanan
--}}
@php
    $centered = $__env->hasSection('centered');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daftarkan sekolah') — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        .font-display { font-family: 'Fraunces', serif; }
        @view-transition { navigation: auto; }
    </style>
</head>
<body class="antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui;">

    <div class="min-h-screen lg:flex">

        {{-- Panel kiri: brand + isi khusus halaman --}}
        <aside class="hidden lg:flex lg:w-[38%] lg:sticky lg:top-0 lg:h-screen lg:self-start flex-col justify-between p-10 xl:p-12 bg-[#1B3A34] text-[#F6F3EC]"
               style="view-transition-name: brand-panel;">
            <div>
                <div class="font-display text-2xl font-semibold">EduZone</div>
                <div class="text-sm mt-1 text-[#9FB8AE]">Platform manajemen sekolah</div>
            </div>

            <div>
                @hasSection('panel')
                    @yield('panel')
                @else
                    <div class="text-sm font-medium mb-3 text-[#C9A227]">Absen hari ini</div>
                    <div class="flex flex-col gap-2" aria-hidden="true">
                        @php
                            // Ornamen dekoratif yang sama dengan halaman login, tidak terhubung data nyata.
                            $rows = [false, false, true, false, false];
                        @endphp
                        @foreach ($rows as $isPresent)
                            <div class="flex items-center gap-1.5">
                                @for ($i = 0; $i < 6; $i++)
                                    <span class="block rounded-full"
                                          style="width:7px; height:7px; background: {{ $isPresent && $i < 4 ? '#C9A227' : 'rgba(246,243,236,0.18)' }};"></span>
                                @endfor
                                @if ($isPresent)
                                    <svg class="w-3 h-3 ml-1 text-[#C9A227]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="text-xs leading-relaxed text-[#7C9A8E]">
                Satu platform untuk akademik, absensi, keuangan, dan kesiswaan —
                terisolasi aman per sekolah.
            </p>
        </aside>

        {{-- Panel kanan: konten halaman --}}
        <div class="w-full lg:w-[62%] min-h-screen flex justify-center px-6 py-10 sm:px-10 lg:px-16 lg:py-14 bg-[#F6F3EC] {{ $centered ? 'items-center' : 'items-start' }}"
             style="view-transition-name: form-panel;">
            <main class="w-full max-w-xl">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs mb-6 hover:underline text-[#8A8A80]">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Kembali ke beranda
                </a>

                {{-- Wordmark hanya di mobile, karena panel kiri disembunyikan di layar kecil --}}
                <div class="lg:hidden font-display text-xl font-semibold mb-8 text-[#1B3A34]">EduZone</div>

                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
