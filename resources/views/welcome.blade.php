{{--
    Asumsi & catatan perubahan dari draft sebelumnya:
    - Entry Vite diganti ke resources/js/areas/tenant.js (bukan app.js), sesuai FRONTEND.md
      §3: "landing page" masuk area tenant, bukan bundle global.
    - Angka statistik ("500+ sekolah", dst), testimoni, dan harga spesifik di draft lama
      DIHAPUS — produk masih pra-peluncuran (lihat PRD.md §4 & §7), jadi klaim itu tidak akurat.
      Diganti status per modul (Tersedia/Segera) sesuai tabel PRD.md §4, dan CTA "Hubungi kami"
      untuk harga karena model subscription belum diputuskan.
    - Palet & tipografi mengikuti identitas yang sudah dipakai di login tenant/superadmin:
      pine #1B3A34, gold #C9A227, kertas #F6F3EC, Fraunces (display) + Plus Jakarta Sans (body).
    - Pola dekoratif "roll-call dots" dipakai lagi di hero supaya konsisten dengan login —
      bukan elemen baru yang tidak nyambung.
--}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduZone — Platform Manajemen Sekolah</title>
    <meta name="description" content="EduZone, platform manajemen sekolah untuk SMA/SMK Indonesia. Mulai dari absensi multi-metode, menyusul akademik, penilaian, kesiswaan, lab, dan keuangan.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        .voice { font-family: 'Fraunces', serif; }
        .dot-row { display: flex; align-items: center; gap: 6px; }
        .dot { width: 7px; height: 7px; border-radius: 50%; }
    </style>
</head>
<body class="antialiased" style="background:#F6F3EC; color:#1C1C1A;">

{{-- ======================================================== --}}
{{-- NAVBAR                                                    --}}
{{-- ======================================================== --}}
<nav class="sticky top-0 z-50 border-b" style="background:#F6F3EC; border-color:#E4E0D4;">
    <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="/" class="voice text-lg font-semibold" style="color:#1B3A34;">EduZone</a>

        <div class="hidden md:flex items-center gap-1">
            <a href="#modul" class="px-3 py-2 text-sm font-medium rounded-md hover:bg-black/5" style="color:#4A4A44;">Modul</a>
            <a href="#peran" class="px-3 py-2 text-sm font-medium rounded-md hover:bg-black/5" style="color:#4A4A44;">Peran pengguna</a>
            <a href="#roadmap" class="px-3 py-2 text-sm font-medium rounded-md hover:bg-black/5" style="color:#4A4A44;">Roadmap</a>
        </div>

        <div class="flex items-center gap-2">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="text-sm font-medium px-3 py-2" style="color:#1B3A34;">Masuk</a>
            @endif
            <a href="#kontak" class="text-sm font-medium px-4 py-2 rounded-md" style="background:#1B3A34; color:#F6F3EC;">Hubungi kami</a>
        </div>
    </div>
</nav>

{{-- ======================================================== --}}
{{-- HERO                                                      --}}
{{-- ======================================================== --}}
<section class="max-w-6xl mx-auto px-5 pt-16 pb-20 lg:pt-24 lg:pb-28">
    <div class="grid lg:grid-cols-2 gap-14 items-center">

        <div>
            <div class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full mb-6"
                 style="background:#EAF3DE; color:#3B6D11;">
                Modul Absensi sudah bisa dipakai
            </div>

            <h1 class="voice text-4xl sm:text-5xl font-semibold leading-[1.15] mb-5" style="color:#1B3A34;">
                Satu platform untuk<br>seluruh operasional sekolah
            </h1>

            <p class="text-base leading-relaxed mb-8 max-w-md" style="color:#5A5A52;">
                EduZone menyatukan absensi, akademik, keuangan, dan kesiswaan dalam satu sistem —
                dengan data terisolasi aman untuk setiap sekolah. Kami membangunnya bertahap,
                dimulai dari absensi supaya sekolah bisa mulai memakainya lebih cepat.
            </p>

            <div class="flex flex-wrap gap-3">
                <a href="#kontak" class="inline-flex items-center gap-2 px-5 py-3 rounded-md text-sm font-medium hover:opacity-90 transition-opacity"
                   style="background:#1B3A34; color:#F6F3EC;">
                    Hubungi kami
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a href="#modul" class="inline-flex items-center gap-2 px-5 py-3 rounded-md text-sm font-medium border hover:bg-black/[0.03] transition-colors"
                   style="border-color:#1B3A34; color:#1B3A34;">
                    Lihat modul
                </a>
            </div>
        </div>

        {{-- Visual kiosk absensi — merepresentasikan fitur yang memang sudah nyata jalan --}}
        <div class="rounded-2xl p-7" style="background:#1B3A34;">
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-medium tracking-wide" style="color:#C9A227;">KIOSK ABSENSI</span>
                <span class="text-xs px-2 py-0.5 rounded" style="background:rgba(201,162,39,0.15); color:#C9A227;">Aktif</span>
            </div>

            <div class="rounded-xl p-6 mb-5" style="background:#F6F3EC;">
                <div class="flex items-center justify-center mb-4">
                    <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="#1B3A34" stroke-width="1.6">
                        <rect x="3" y="6" width="18" height="12" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10h.01M11 10h6M7 14h10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="text-center text-sm font-medium" style="color:#1B3A34;">Tempelkan kartu RFID atau pindai QR</p>
                <p class="text-center text-xs mt-1" style="color:#8A8A80;">Terverifikasi dalam hitungan detik</p>
            </div>

            <div class="dot-row mb-2" aria-hidden="true">
                @for ($i = 0; $i < 8; $i++)
                    <span class="dot" style="background: {{ $i < 5 ? '#C9A227' : 'rgba(246,243,236,0.18)' }};"></span>
                @endfor
            </div>
            <p class="text-xs" style="color:#7C9A8E;">5 dari 8 siswa sudah absen hari ini</p>
        </div>

    </div>
</section>

{{-- ======================================================== --}}
{{-- MODUL — status jujur, bukan semua "tersedia"              --}}
{{-- ======================================================== --}}
<section id="modul" class="py-20" style="background:#FFFFFF;">
    <div class="max-w-6xl mx-auto px-5">
        <div class="max-w-lg mb-12">
            <h2 class="voice text-3xl font-semibold mb-3" style="color:#1B3A34;">Modul yang sedang dibangun</h2>
            <p class="text-sm leading-relaxed" style="color:#6B6B63;">
                Kami merilis satu modul dalam satu waktu supaya setiap fitur benar-benar matang
                sebelum dipakai sekolah. Absensi adalah yang pertama.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $iconStroke = fn ($active) => $active ? '#C9A227' : '#8A8A80';
                $modules = [
                    ['n' => 'Absensi', 'd' => 'RFID, QR, wajah, dan kiosk manual, plus geofencing check-in guru.', 'status' => 'tersedia',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(true).'" stroke-width="1.8"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                    ['n' => 'Akademik', 'd' => 'Jurusan, kelas, jadwal pelajaran, dan jurnal mengajar.', 'status' => 'segera',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(false).'" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                    ['n' => 'Penilaian', 'd' => 'Input nilai, konfigurasi bobot, dan bank soal ujian.', 'status' => 'segera',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(false).'" stroke-width="1.8"><path d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                    ['n' => 'Kesiswaan', 'd' => 'Sikap siswa, prestasi, rekam jejak, dan konseling BK.', 'status' => 'segera',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(false).'" stroke-width="1.8"><circle cx="9" cy="8" r="3.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 20a6.5 6.5 0 0113 0M16 8.5a3 3 0 013 3M15 20a5.5 5.5 0 016.5-5.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                    ['n' => 'Laboratorium', 'd' => 'Booking lab, inventaris, dan laporan kunjungan.', 'status' => 'segera',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(false).'" stroke-width="1.8"><path d="M9 2v6.5L4 18a2 2 0 001.8 3h12.4a2 2 0 001.8-3l-5-9.5V2M8 2h8" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                    ['n' => 'Keuangan', 'd' => 'Dana BOS, pemasukan, pengeluaran, dan pengajuan anggaran.', 'status' => 'segera',
                        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="'.$iconStroke(false).'" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
                ];
            @endphp

            @foreach ($modules as $m)
                <div class="rounded-xl p-5 border transition-transform hover:-translate-y-0.5" style="border-color:#EDEAE0;">
                    <div class="w-11 h-11 rounded-lg flex items-center justify-center mb-4"
                         style="background: {{ $m['status'] === 'tersedia' ? '#1B3A34' : '#F1EFE8' }};">
                        {!! $m['icon'] !!}
                    </div>
                    <div class="flex items-start justify-between mb-2">
                        <p class="font-medium text-sm" style="color:#1C1C1A;">{{ $m['n'] }}</p>
                        @if ($m['status'] === 'tersedia')
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:#EAF3DE; color:#3B6D11;">Tersedia</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:#F1EFE8; color:#6B6B63;">Segera</span>
                        @endif
                    </div>
                    <p class="text-sm leading-relaxed" style="color:#6B6B63;">{{ $m['d'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

 
{{-- ======================================================== --}}
{{-- PERAN PENGGUNA                                            --}}
{{-- ======================================================== --}}
<section id="peran" class="py-20 relative overflow-hidden" style="background:#1B3A34;">
    <div class="max-w-6xl mx-auto px-5 relative z-10">
        <div class="max-w-lg mb-14">
            <h2 class="voice text-3xl font-semibold mb-3" style="color:#F6F3EC;">Akses tepat untuk setiap peran</h2>
            <p class="text-sm leading-relaxed" style="color:#9FB8AE;">
                Sembilan peran di sisi sekolah, masing-masing hanya melihat yang relevan untuk tugasnya.
            </p>
        </div>
 
        @php
            $roles = [
                ['i' => 'KS', 'n' => 'Kepala Sekolah'],
                ['i' => 'KU', 'n' => 'Kurikulum'],
                ['i' => 'TU', 'n' => 'Tata Usaha'],
                ['i' => 'GM', 'n' => 'Guru Mapel'],
                ['i' => 'WK', 'n' => 'Wali Kelas'],
                ['i' => 'KSW', 'n' => 'Kesiswaan'],
                ['i' => 'BK', 'n' => 'Bimbingan Konseling'],
                ['i' => 'TM', 'n' => 'Toolman'],
                ['i' => 'SW', 'n' => 'Siswa'],
            ];
        @endphp
        <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-9 gap-x-3 gap-y-10 text-center">
            @foreach ($roles as $r)
                <div class="flex flex-col items-center group">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center font-semibold mb-3 transition-all duration-200 group-hover:scale-105 group-hover:bg-[rgba(201,162,39,0.2)] {{ strlen($r['i']) > 2 ? 'text-xs' : 'text-sm' }}"
                         style="background:rgba(201,162,39,0.12); border:1px solid rgba(201,162,39,0.35); color:#C9A227;">
                        {{ $r['i'] }}
                    </div>
                    <span class="text-xs leading-tight transition-colors group-hover:text-white" style="color:#C7D3CC;">{{ $r['n'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
 
{{-- ======================================================== --}}
{{-- ROADMAP — urutan rilis modul, apa adanya                  --}}
{{-- ======================================================== --}}
<section id="roadmap" class="py-20" style="background:#FFFFFF;">
    <div class="max-w-4xl mx-auto px-5">
        <div class="text-center mb-16">
            <h2 class="voice text-3xl font-semibold mb-3" style="color:#1B3A34;">Urutan rilis</h2>
            <p class="text-sm leading-relaxed max-w-md mx-auto" style="color:#6B6B63;">
                Urutan modul setelah Absensi belum final — akan disesuaikan seiring modul
                sebelumnya selesai dan dipakai sekolah.
            </p>
        </div>

        @php
            $timeline = [
                ['n' => 'Absensi', 'd' => 'Kiosk RFID & QR sudah live. Dashboard staff dan check-in guru via HP menyusul.', 'status' => 'Sedang berjalan', 'done' => true],
                ['n' => 'Akademik', 'd' => 'Skema database selesai, menyusul setelah Absensi.', 'status' => 'Menyusul', 'done' => false],
                ['n' => 'Penilaian', 'd' => 'Skema database selesai, menyusul setelah Absensi.', 'status' => 'Menyusul', 'done' => false],
                ['n' => 'Kesiswaan', 'd' => 'Skema database selesai, menyusul setelah Absensi.', 'status' => 'Menyusul', 'done' => false],
                ['n' => 'Laboratorium', 'd' => 'Skema database selesai, menyusul setelah Absensi.', 'status' => 'Menyusul', 'done' => false],
                ['n' => 'Keuangan', 'd' => 'Skema database selesai, menyusul setelah Absensi.', 'status' => 'Menyusul', 'done' => false],
            ];
        @endphp

        <div class="relative">
            <div class="hidden sm:block absolute left-1/2 -translate-x-1/2 top-0 bottom-0 w-px" style="background:#E4E0D4;"></div>
            <div class="flex flex-col gap-10 sm:gap-14">
                @foreach ($timeline as $i => $t)
                    @php $flip = $i % 2 !== 0; @endphp
                    <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-0 {{ $flip ? 'sm:flex-row-reverse' : '' }}">
                        <div class="w-full sm:w-1/2 text-center {{ $flip ? 'sm:text-left sm:pl-10' : 'sm:text-right sm:pr-10' }}">
                            <p class="text-sm font-medium" style="color:#1C1C1A;">{{ $t['n'] }}</p>
                            <p class="text-sm mt-0.5" style="color:#8A8A80;">{{ $t['d'] }}</p>
                        </div>

                        <span class="block w-3 h-3 rounded-full flex-shrink-0" style="background: {{ $t['done'] ? '#C9A227' : '#D8D4C6' }};"></span>

                        <div class="w-full sm:w-1/2 text-center {{ $flip ? 'sm:text-right sm:pr-10' : 'sm:text-left sm:pl-10' }}">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded"
                                  style="background: {{ $t['done'] ? '#EAF3DE' : '#F1EFE8' }}; color: {{ $t['done'] ? '#3B6D11' : '#6B6B63' }};">
                                {{ $t['status'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- CTA / KONTAK                                              --}}
{{-- ======================================================== --}}
<section id="kontak" class="py-20 px-5">
    <div class="max-w-3xl mx-auto text-center rounded-2xl p-12 lg:p-16 relative overflow-hidden" style="background:#1B3A34;">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true"
             style="background-image: radial-gradient(circle, rgba(201,162,39,0.35) 1px, transparent 1px); background-size: 22px 22px; opacity:0.25;"></div>

        <div class="relative z-10">
            <h2 class="voice text-3xl font-semibold mb-3" style="color:#F6F3EC;">
                Tertarik pakai EduZone di sekolah Anda?
            </h2>
            <p class="text-sm leading-relaxed mb-8 max-w-md mx-auto" style="color:#9FB8AE;">
                Kami masih di tahap awal, jadi setiap sekolah yang bergabung sekarang bisa langsung
                membentuk arah pengembangan modul selanjutnya.
            </p>
            <a href="mailto:halo@eduzone.id" class="inline-flex items-center gap-2 px-6 py-3 rounded-md text-sm font-medium hover:opacity-90 transition-opacity"
               style="background:#C9A227; color:#1A1500;">
                Hubungi kami
            </a>
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- FOOTER                                                    --}}
{{-- ======================================================== --}}
<footer class="py-10 border-t" style="border-color:#E4E0D4;">
    <div class="max-w-6xl mx-auto px-5 flex flex-col sm:flex-row items-center justify-between gap-3">
        <span class="voice text-sm font-semibold" style="color:#1B3A34;">EduZone</span>
        <p class="text-xs" style="color:#9A9A8E;">&copy; {{ date('Y') }} EduZone. Platform manajemen sekolah.</p>
    </div>
</footer>

</body>
</html>