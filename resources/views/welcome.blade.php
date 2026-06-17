<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduZone — Platform Digital Sekolah Modern</title>
    <meta name="description" content="EduZone platform manajemen sekolah digital untuk SMA/SMK. Absensi, nilai, jadwal, keuangan — semua dalam satu aplikasi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* ── COLORS ── */
        :root {
            --blue:   #3b82f6;
            --indigo: #6366f1;
            --violet: #8b5cf6;
            --pink:   #ec4899;
            --cyan:   #06b6d4;
        }

        /* ── GRADIENT TEXT ── */
        .g-text {
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .g-text-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── HERO ── */
        .hero-section {
            background: #fafbff;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
            top: -200px; right: -100px;
            border-radius: 50%;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(236,72,153,0.08) 0%, transparent 70%);
            bottom: 0; left: -50px;
            border-radius: 50%;
        }

        /* ── NOISE TEXTURE ── */
        .noise {
            position: relative;
        }
        .noise::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.02'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        /* ── NAVBAR ── */
        #navbar {
            transition: all 0.3s ease;
        }
        .nav-glass {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(99,102,241,0.1);
            box-shadow: 0 8px 32px rgba(99,102,241,0.07);
        }

        /* ── BUTTON PRIMARY ── */
        .btn-grad {
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
            color: white;
            font-weight: 700;
            border-radius: 14px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-grad::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #4f46e5 0%, #db2777 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .btn-grad:hover::before { opacity: 1; }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(99,102,241,0.4); }
        .btn-grad span { position: relative; z-index: 1; }

        .btn-outline {
            background: white;
            border: 2px solid #e0e7ff;
            color: #4f46e5;
            font-weight: 700;
            border-radius: 14px;
            transition: all 0.3s ease;
        }
        .btn-outline:hover {
            border-color: #6366f1;
            background: #eef2ff;
            transform: translateY(-2px);
        }

        /* ── FLOATING CARD ── */
        @keyframes float-a {
            0%,100% { transform: translateY(0) rotate(-1deg); }
            50%      { transform: translateY(-16px) rotate(1deg); }
        }
        @keyframes float-b {
            0%,100% { transform: translateY(0) rotate(1deg); }
            50%      { transform: translateY(-12px) rotate(-0.5deg); }
        }
        @keyframes float-c {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-10px); }
        }
        .fa { animation: float-a 5s ease-in-out infinite; }
        .fb { animation: float-b 6s ease-in-out infinite 0.8s; }
        .fc { animation: float-c 4.5s ease-in-out infinite 1.5s; }

        /* ── FEATURE CARD ── */
        .feat-card {
            background: white;
            border: 1.5px solid #f0f0f8;
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .feat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feat-card:hover {
            transform: translateY(-6px);
            border-color: #e0e7ff;
            box-shadow: 0 20px 60px rgba(99,102,241,0.12);
        }
        .feat-card:hover::before { opacity: 1; }
        .fc-blue::before   { background: linear-gradient(90deg, #3b82f6, #6366f1); }
        .fc-violet::before { background: linear-gradient(90deg, #8b5cf6, #ec4899); }
        .fc-cyan::before   { background: linear-gradient(90deg, #06b6d4, #3b82f6); }
        .fc-pink::before   { background: linear-gradient(90deg, #ec4899, #f43f5e); }
        .fc-amber::before  { background: linear-gradient(90deg, #f59e0b, #f97316); }
        .fc-green::before  { background: linear-gradient(90deg, #10b981, #06b6d4); }

        /* ── ICON BOX ── */
        .icon-box {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── ROLE CARDS ── */
        .role-card {
            background: white;
            border: 1.5px solid #f0f0f8;
            border-radius: 16px;
            transition: all 0.25s ease;
        }
        .role-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(99,102,241,0.1);
        }

        /* ── PRICING ── */
        .price-card {
            background: white;
            border: 1.5px solid #f0f0f8;
            border-radius: 24px;
            transition: all 0.3s ease;
        }
        .price-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        }
        .price-popular {
            background: linear-gradient(145deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            border-color: transparent;
        }

        /* ── TESTIMONIAL ── */
        .testi-card {
            background: white;
            border: 1.5px solid #f0f0f8;
            border-radius: 20px;
            transition: all 0.25s ease;
        }
        .testi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(99,102,241,0.1);
            border-color: #e0e7ff;
        }

        /* ── STATS BAR ── */
        .stats-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        }

        /* ── SECTION BG ── */
        .bg-dots {
            background-color: #fafbff;
            background-image: radial-gradient(rgba(99,102,241,0.08) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 30%, #4c1d95 60%, #831843 100%);
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            top: -200px; right: -100px; border-radius: 50%;
        }
        .cta-section::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);
            bottom: -100px; left: 50px; border-radius: 50%;
        }

        /* ── TAG PILL ── */
        .tag { border-radius: 999px; font-size: 11px; font-weight: 600; padding: 3px 10px; }

        /* ── MOBILE MENU ── */
        #mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        #mobile-menu.open { max-height: 500px; }

        /* ── SCROLL REVEAL ── */
        .reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.65s ease, transform 0.65s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .reveal-l { opacity: 0; transform: translateX(-32px); transition: opacity 0.65s ease, transform 0.65s ease; }
        .reveal-l.visible { opacity: 1; transform: translateX(0); }
        .reveal-r { opacity: 0; transform: translateX(32px); transition: opacity 0.65s ease, transform 0.65s ease; }
        .reveal-r.visible { opacity: 1; transform: translateX(0); }

        /* ── BADGE ── */
        .section-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #eef2ff, #fdf2f8);
            border: 1px solid #c7d2fe;
            color: #4f46e5;
            font-size: 12px; font-weight: 700;
            padding: 6px 14px; border-radius: 999px;
            letter-spacing: 0.02em;
        }

        /* ── AVATAR STACK ── */
        .avatar-stack .av {
            width: 36px; height: 36px; border-radius: 50%;
            border: 2.5px solid white;
            margin-left: -10px;
        }
        .avatar-stack .av:first-child { margin-left: 0; }

        /* ── MARQUEE ── */
        @keyframes marquee {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track { display: flex; width: max-content; animation: marquee 22s linear infinite; }
        .marquee-track:hover { animation-play-state: paused; }
    </style>
</head>
<body class="antialiased text-slate-800 bg-white">

{{-- ======================================================== --}}
{{-- NAVBAR                                                    --}}
{{-- ======================================================== --}}
<nav id="navbar" class="fixed top-0 inset-x-0 z-50 px-4 sm:px-6 pt-4">
    <div class="max-w-6xl mx-auto">
        {{-- Main bar --}}
        <div class="nav-glass rounded-2xl px-6 h-14 flex items-center justify-between gap-4">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5 flex-shrink-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm" style="background:linear-gradient(135deg,#6366f1,#ec4899);">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0118 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <span class="text-base font-extrabold text-slate-900">Edu<span class="g-text">Zone</span></span>
            </a>

            {{-- Desktop links — center --}}
            <div class="hidden md:flex items-center gap-1 flex-1 justify-center">
                <a href="#fitur"     class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">Fitur</a>
                <a href="#modul"     class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">Modul</a>
                <a href="#harga"     class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">Harga</a>
                <a href="#testimoni" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">Testimoni</a>
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden md:flex items-center gap-2 flex-shrink-0">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 px-3 py-2">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 px-3 py-2 transition-colors">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-grad text-sm px-4 py-2 inline-flex items-center gap-1.5 rounded-xl">
                                <span>Coba Gratis</span>
                                <svg style="width:13px;height:13px;position:relative;z-index:1;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @endif
                    @endauth
                @endif
            </div>

            {{-- Hamburger --}}
            <button id="menu-btn" class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition-colors flex-shrink-0">
                <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div id="mobile-menu" class="md:hidden mt-2">
            <div class="nav-glass rounded-2xl p-4 flex flex-col gap-1">
                <a href="#fitur"     class="py-2.5 px-3 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">Fitur</a>
                <a href="#modul"     class="py-2.5 px-3 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">Modul</a>
                <a href="#harga"     class="py-2.5 px-3 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">Harga</a>
                <a href="#testimoni" class="py-2.5 px-3 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">Testimoni</a>
                <hr class="my-1 border-slate-100">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="py-2.5 px-3 text-sm font-semibold text-indigo-600">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="py-2.5 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-xl">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-grad text-sm px-4 py-3 text-center mt-1 rounded-xl block"><span>Coba Gratis →</span></a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </div>
</nav>

{{-- ======================================================== --}}
{{-- HERO                                                      --}}
{{-- ======================================================== --}}
<section class="hero-section min-h-screen flex items-center pt-32 pb-24 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 w-full relative z-10">
        <div class="grid lg:grid-cols-[1fr_1.1fr] gap-12 xl:gap-20 items-center">

            {{-- ── Left ── --}}
            <div class="reveal-l">
                <div class="section-badge mb-6 w-fit">
                    <span>🎓</span> Platform Digital Sekolah #1 Indonesia
                </div>

                <h1 class="text-5xl sm:text-6xl xl:text-7xl font-extrabold text-slate-900 leading-[1.08] mb-6 tracking-tight">
                    Sekolah<br>
                    <span class="g-text">Lebih Keren</span><br>
                    <span class="text-slate-800">dengan Teknologi</span>
                </h1>

                <p class="text-lg text-slate-500 leading-relaxed mb-8 max-w-md">
                    EduZone menghubungkan guru, siswa, dan orang tua dalam satu platform yang seru. Absensi, nilai, jadwal — semua ada di sini.
                </p>

                <div class="flex flex-wrap gap-3 mb-10">
                    <a href="#" class="btn-grad px-6 py-3.5 text-sm inline-flex items-center gap-2">
                        <span>Mulai Sekarang — Gratis</span><span>🚀</span>
                    </a>
                    <a href="#fitur" class="btn-outline px-6 py-3.5 text-sm inline-flex items-center gap-2">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Lihat Demo
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center avatar-stack">
                        @foreach([['AS','from-indigo-400 to-violet-500'],['RD','from-pink-400 to-rose-500'],['BW','from-cyan-400 to-blue-500'],['ND','from-emerald-400 to-teal-500']] as $av)
                        <div class="av w-9 h-9 rounded-full border-2 border-white flex items-center justify-center text-white font-bold text-xs bg-gradient-to-br {{ $av[1] }}" style="margin-left:{{ $loop->first ? '0' : '-10px' }}">{{ $av[0] }}</div>
                        @endforeach
                    </div>
                    <div>
                        <div class="flex items-center gap-0.5 mb-0.5">
                            @for($i=0;$i<5;$i++)<svg style="width:13px;height:13px;" fill="#f59e0b" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Dipercaya <strong class="text-slate-700">500+ sekolah</strong> di Indonesia</p>
                    </div>
                </div>
            </div>

            {{-- ── Right — Dashboard mockup ── --}}
            <div class="hidden lg:block relative reveal-r" style="height:520px;">

                {{-- Main dashboard card --}}
                <div class="fa absolute inset-x-0 top-0 bg-white rounded-3xl p-6 z-10" style="box-shadow:0 32px 80px rgba(99,102,241,0.18);border:1px solid #f0f0f8;">

                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-xs text-slate-400 font-medium">Senin, 13 Juni 2026</p>
                            <p class="text-sm font-bold text-slate-800">Selamat Pagi, Pak Ahmad 👋</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="relative w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center">
                                <svg style="width:17px;height:17px;" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center font-bold leading-none">3</span>
                            </div>
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">AP</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="bg-indigo-50 rounded-2xl p-3.5 text-center">
                            <p class="text-2xl font-extrabold text-indigo-600">842</p>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium">Siswa</p>
                        </div>
                        <div class="bg-pink-50 rounded-2xl p-3.5 text-center">
                            <p class="text-2xl font-extrabold text-pink-500">64</p>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium">Guru</p>
                        </div>
                        <div class="bg-cyan-50 rounded-2xl p-3.5 text-center">
                            <p class="text-2xl font-extrabold text-cyan-500">24</p>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium">Kelas</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-2xl p-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-slate-700">Kehadiran Minggu Ini</p>
                            <span class="tag" style="background:#d1fae5;color:#065f46;">↑ 94.2%</span>
                        </div>
                        <div class="flex items-end gap-1.5 h-12">
                            @php $bars = [72,85,68,92,78,96,88,76,91,95,82,98]; @endphp
                            @foreach($bars as $i => $h)
                            <div class="flex-1 rounded-lg" style="height:{{$h}}%;background:linear-gradient(to top,{{ $i%3==0?'#6366f1,#8b5cf6':($i%3==1?'#ec4899,#f43f5e':'#06b6d4,#3b82f6') }});"></div>
                            @endforeach
                        </div>
                        <div class="flex justify-between mt-2">
                            @foreach(['Sen','Sel','Rab','Kam','Jum'] as $d)
                            <span class="text-xs text-slate-400 font-medium">{{$d}}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate">Nilai UAS XII IPA difinalisasi</p>
                                <p class="text-xs text-slate-400">2 menit lalu</p>
                            </div>
                            <span class="tag flex-shrink-0" style="background:#e0e7ff;color:#4338ca;">Nilai</span>
                        </div>
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50">
                            <div class="w-8 h-8 rounded-xl bg-pink-100 flex items-center justify-center flex-shrink-0">
                                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="#ec4899" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate">Dana BOS Triwulan III diterima</p>
                                <p class="text-xs text-slate-400">1 jam lalu</p>
                            </div>
                            <span class="tag flex-shrink-0" style="background:#fce7f3;color:#9d174d;">Keuangan</span>
                        </div>
                    </div>
                </div>

                {{-- Floating badge — top right, tidak overlap hero kiri --}}
                <div class="fb absolute -top-6 right-0 bg-white rounded-2xl p-4 w-44 z-20" style="box-shadow:0 12px 40px rgba(99,102,241,0.18);border:1px solid #f0f0f8;">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-xl">🏆</span>
                        <p class="text-xs font-bold text-slate-700">Prestasi</p>
                    </div>
                    <p class="text-2xl font-extrabold g-text">+23</p>
                    <p class="text-xs text-slate-400 mt-0.5">penghargaan bulan ini</p>
                </div>

                {{-- Floating schedule — bottom right, tidak overlap ke kiri --}}
                <div class="fc absolute -bottom-6 right-0 bg-white rounded-2xl p-4 w-52 z-20" style="box-shadow:0 12px 40px rgba(99,102,241,0.15);border:1px solid #f0f0f8;">
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="text-xl">📚</span>
                        <p class="text-xs font-bold text-slate-700">Jadwal Hari Ini</p>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#6366f1;"></div>
                            <p class="text-xs text-slate-600">07:30 — Matematika XII IPA</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#ec4899;"></div>
                            <p class="text-xs text-slate-600">09:00 — Fisika XI IPA</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#06b6d4;"></div>
                            <p class="text-xs text-slate-600">10:30 — Kimia X IPA</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- MARQUEE — sekolah partner                                 --}}
{{-- ======================================================== --}}
<div class="py-10 border-y border-slate-100 bg-white overflow-hidden">
    <p class="text-center text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Dipercaya sekolah terbaik di Indonesia</p>
    <div class="relative overflow-hidden">
        <div class="marquee-track">
            @php
            $schools = ['SMA Negeri 1 Bandung','SMK Negeri 2 Surabaya','SMA Tarakanita Jakarta','SMK Telkom Purwokerto','SMA Negeri 3 Medan','SMK PGRI Tangerang','SMA Al-Azhar Yogyakarta','SMA Negeri 5 Semarang','SMK Negeri 1 Makassar','SMA Negeri 2 Palembang'];
            @endphp
            @foreach(array_merge($schools,$schools) as $s)
            <div class="flex items-center gap-3 mx-8 flex-shrink-0">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-400 to-pink-400 flex items-center justify-center">
                    <svg style="width:12px;height:12px;" fill="white" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/></svg>
                </div>
                <span class="text-sm font-semibold text-slate-500 whitespace-nowrap">{{ $s }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ======================================================== --}}
{{-- STATS                                                     --}}
{{-- ======================================================== --}}
<section class="stats-section py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 reveal">
            @php
            $stats = [
                ['num'=>'500+',  'label'=>'Sekolah Aktif',    'icon'=>'🏫'],
                ['num'=>'120K+', 'label'=>'Siswa Terdaftar',  'icon'=>'🎓'],
                ['num'=>'15K+',  'label'=>'Guru & Staff',     'icon'=>'👨‍🏫'],
                ['num'=>'99.9%', 'label'=>'Uptime Sistem',    'icon'=>'⚡'],
            ];
            @endphp
            @foreach($stats as $s)
            <div class="text-center">
                <div class="text-3xl mb-2">{{ $s['icon'] }}</div>
                <p class="text-3xl lg:text-4xl font-extrabold text-white mb-1">{{ $s['num'] }}</p>
                <p class="text-sm font-medium text-indigo-200">{{ $s['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- FEATURES                                                  --}}
{{-- ======================================================== --}}
<section id="fitur" class="bg-dots py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16 reveal">
            <div class="section-badge mb-5">✨ Kenapa EduZone?</div>
            <h2 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                Semua Kebutuhan Sekolah<br>
                <span class="g-text">Dalam Satu Platform</span>
            </h2>
            <p class="text-slate-500 max-w-xl mx-auto text-lg">
                Dari absensi harian sampai laporan dana BOS — EduZone punya semua yang kamu butuhkan.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $feats = [
                ['cls'=>'fc-blue',   'bg'=>'bg-indigo-50', 'ic'=>'text-indigo-600', 'title'=>'Absensi Digital',        'desc'=>'Rekap kehadiran siswa & guru secara real-time. Notifikasi otomatis langsung ke orang tua kalau anaknya tidak hadir.', 'emoji'=>'📋', 'tags'=>[['Siswa','bg-indigo-100 text-indigo-700'],['Guru','bg-blue-100 text-blue-700']]],
                ['cls'=>'fc-violet', 'bg'=>'bg-violet-50', 'ic'=>'text-violet-600', 'title'=>'Nilai & Raport',          'desc'=>'Input nilai harian, tugas, UTS, dan UAS. Generate raport digital otomatis sesuai kurikulum K13 dan Merdeka.', 'emoji'=>'📊', 'tags'=>[['K13','bg-violet-100 text-violet-700'],['Merdeka','bg-purple-100 text-purple-700']]],
                ['cls'=>'fc-cyan',   'bg'=>'bg-cyan-50',   'ic'=>'text-cyan-600',   'title'=>'Jadwal Pelajaran',        'desc'=>'Buat dan kelola jadwal kelas otomatis. Guru dan siswa bisa lihat jadwal mereka kapanpun lewat dashboard.', 'emoji'=>'🗓️', 'tags'=>[['Auto-generate','bg-cyan-100 text-cyan-700']]],
                ['cls'=>'fc-pink',   'bg'=>'bg-pink-50',   'ic'=>'text-pink-600',   'title'=>'Keuangan Transparan',     'desc'=>'Kelola dana BOS, pemasukan, pengeluaran, dan buat laporan keuangan sekolah dengan mudah dan akurat.', 'emoji'=>'💰', 'tags'=>[['Dana BOS','bg-pink-100 text-pink-700'],['Laporan','bg-rose-100 text-rose-700']]],
                ['cls'=>'fc-amber',  'bg'=>'bg-amber-50',  'ic'=>'text-amber-600',  'title'=>'Manajemen Lab',           'desc'=>'Booking laboratorium, pantau penggunaan peralatan, dan catat laporan kunjungan lab secara sistematis.', 'emoji'=>'🔬', 'tags'=>[['Booking','bg-amber-100 text-amber-700'],['Inventaris','bg-orange-100 text-orange-700']]],
                ['cls'=>'fc-green',  'bg'=>'bg-emerald-50','ic'=>'text-emerald-600','title'=>'Multi-Role & Multi-Sekolah','desc'=>'10 role pengguna berbeda dengan akses yang tepat. Satu platform untuk ratusan sekolah secara bersamaan.', 'emoji'=>'👥', 'tags'=>[['10 Role','bg-emerald-100 text-emerald-700'],['SaaS','bg-teal-100 text-teal-700']]],
            ];
            @endphp

            @foreach($feats as $i => $f)
            <div class="feat-card {{ $f['cls'] }} p-6 reveal" style="transition-delay: {{ $i * 80 }}ms">
                <div class="text-3xl mb-4">{{ $f['emoji'] }}</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-slate-500 leading-relaxed mb-4">{{ $f['desc'] }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($f['tags'] as $tag)
                    <span class="tag {{ $tag[1] }}">{{ $tag[0] }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- MODULES / ROLES                                           --}}
{{-- ======================================================== --}}
<section id="modul" class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16 reveal">
            <div class="section-badge mb-5">👥 Role Pengguna</div>
            <h2 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                Akses Tepat untuk<br>
                <span class="g-text">Setiap Peran</span>
            </h2>
            <p class="text-slate-500 max-w-lg mx-auto text-lg">
                Dari kepala sekolah sampai siswa — setiap orang punya dashboard yang dirancang khusus.
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
            $roles = [
                ['e'=>'👑', 'n'=>'Superadmin',     'd'=>'Kelola semua sekolah',  'g'=>'from-slate-500 to-slate-700'],
                ['e'=>'🏫', 'n'=>'Kepala Sekolah', 'd'=>'Dashboard & laporan',   'g'=>'from-indigo-500 to-indigo-700'],
                ['e'=>'📚', 'n'=>'Kurikulum',      'd'=>'Jadwal & nilai',        'g'=>'from-violet-500 to-violet-700'],
                ['e'=>'💰', 'n'=>'Tata Usaha',     'd'=>'Keuangan sekolah',      'g'=>'from-pink-500 to-rose-600'],
                ['e'=>'👨‍🏫', 'n'=>'Guru Mapel',     'd'=>'Mengajar & input nilai','g'=>'from-blue-500 to-indigo-600'],
                ['e'=>'🏠', 'n'=>'Wali Kelas',     'd'=>'Absensi & raport',      'g'=>'from-cyan-500 to-blue-600'],
                ['e'=>'🎓', 'n'=>'Kesiswaan',      'd'=>'Data & prestasi',       'g'=>'from-emerald-500 to-teal-600'],
                ['e'=>'💬', 'n'=>'BK',             'd'=>'Konseling siswa',       'g'=>'from-amber-500 to-orange-600'],
                ['e'=>'🔧', 'n'=>'Toolman',        'd'=>'Lab & inventaris',      'g'=>'from-rose-500 to-pink-600'],
                ['e'=>'🧑‍🎓', 'n'=>'Siswa',          'd'=>'Jadwal & nilai saya',  'g'=>'from-purple-500 to-violet-600'],
            ];
            @endphp
            @foreach($roles as $i => $r)
            <div class="role-card p-5 text-center reveal" style="transition-delay:{{$i*60}}ms">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $r['g'] }} flex items-center justify-center text-2xl mx-auto mb-3 shadow-sm">
                    {{ $r['e'] }}
                </div>
                <p class="text-sm font-bold text-slate-800 leading-tight">{{ $r['n'] }}</p>
                <p class="text-xs text-slate-400 mt-1 leading-tight">{{ $r['d'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Feature highlight --}}
        <div class="mt-16 grid md:grid-cols-3 gap-6">
            @php
            $highlights = [
                ['icon'=>'⚡', 'title'=>'Setup 5 Menit', 'desc'=>'Onboarding super cepat. Sekolah kamu langsung bisa jalan tanpa perlu training panjang.'],
                ['icon'=>'🔒', 'title'=>'Data Aman & Terenkripsi', 'desc'=>'Data setiap sekolah terisolasi penuh. Enkripsi end-to-end untuk semua informasi sensitif.'],
                ['icon'=>'📱', 'title'=>'Responsive di Semua Device', 'desc'=>'Bisa diakses dari laptop, tablet, atau HP. Tampilan otomatis menyesuaikan layar.'],
            ];
            @endphp
            @foreach($highlights as $h)
            <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl reveal">
                <div class="text-3xl flex-shrink-0">{{ $h['icon'] }}</div>
                <div>
                    <p class="font-bold text-slate-800 mb-1">{{ $h['title'] }}</p>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $h['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- PRICING                                                   --}}
{{-- ======================================================== --}}
<section id="harga" class="py-24 bg-dots">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16 reveal">
            <div class="section-badge mb-5">💎 Harga</div>
            <h2 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                Harga Transparan,<br>
                <span class="g-text">Tanpa Biaya Tersembunyi</span>
            </h2>
            <p class="text-slate-500 max-w-md mx-auto text-lg">Mulai gratis. Upgrade kapanpun sekolah kamu siap.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 items-start">

            {{-- Trial --}}
            <div class="price-card p-8 reveal">
                <div class="mb-8">
                    <span class="tag bg-slate-100 text-slate-600 mb-3 inline-block">Trial</span>
                    <p class="text-4xl font-extrabold text-slate-900 mb-1">Gratis</p>
                    <p class="text-sm text-slate-400">30 hari pertama</p>
                </div>
                <ul class="space-y-3.5 mb-8">
                    @foreach(['3 kelas aktif','100 akun pengguna','Modul akademik dasar','Laporan sederhana','Support via email'] as $item)
                    <li class="flex items-center gap-2.5 text-sm text-slate-600">
                        <div class="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg style="width:10px;height:10px;" fill="#10b981" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="#" class="btn-outline w-full py-3 text-sm text-center block">Mulai Trial</a>
            </div>

            {{-- Basic — Popular --}}
            <div class="price-popular price-card p-8 relative reveal" style="transform:scale(1.04);">
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="bg-amber-400 text-amber-900 text-xs font-bold px-4 py-1.5 rounded-full shadow-sm">⭐ Terpopuler</span>
                </div>
                <div class="mb-8">
                    <span class="tag bg-white/20 text-white mb-3 inline-block">Basic</span>
                    <div class="flex items-end gap-1 mb-1">
                        <p class="text-4xl font-extrabold text-white">499K</p>
                        <p class="text-white/70 text-sm mb-1.5">/bulan</p>
                    </div>
                    <p class="text-sm text-white/60">per sekolah</p>
                </div>
                <ul class="space-y-3.5 mb-8">
                    @foreach(['Kelas tidak terbatas','500 akun pengguna','Semua modul akademik','Keuangan & Dana BOS','Laporan & analitik','Support prioritas','Notifikasi orang tua'] as $item)
                    <li class="flex items-center gap-2.5 text-sm text-white/90">
                        <div class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                            <svg style="width:10px;height:10px;" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="#" class="bg-white text-indigo-700 font-bold w-full py-3 text-sm text-center block rounded-xl hover:bg-indigo-50 transition-colors">Mulai Sekarang →</a>
            </div>

            {{-- Pro --}}
            <div class="price-card p-8 reveal">
                <div class="mb-8">
                    <span class="tag bg-violet-100 text-violet-700 mb-3 inline-block">Pro</span>
                    <div class="flex items-end gap-1 mb-1">
                        <p class="text-4xl font-extrabold text-slate-900">999K</p>
                        <p class="text-slate-400 text-sm mb-1.5">/bulan</p>
                    </div>
                    <p class="text-sm text-slate-400">per sekolah</p>
                </div>
                <ul class="space-y-3.5 mb-8">
                    @foreach(['Semua fitur Basic','Pengguna tidak terbatas','API & integrasi pihak ketiga','Custom domain sekolah','Realtime WebSocket','Dedicated support 24/7','SLA uptime 99.9%'] as $item)
                    <li class="flex items-center gap-2.5 text-sm text-slate-600">
                        <div class="w-4 h-4 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                            <svg style="width:10px;height:10px;" fill="#7c3aed" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="#" class="btn-outline w-full py-3 text-sm text-center block" style="border-color:#e9d5ff;color:#7c3aed;" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='white'">Hubungi Kami</a>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- TESTIMONIALS                                              --}}
{{-- ======================================================== --}}
<section id="testimoni" class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16 reveal">
            <div class="section-badge mb-5">💬 Testimoni</div>
            <h2 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                Kata Mereka yang<br>
                <span class="g-text">Sudah Pakai EduZone</span>
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @php
            $testis = [
                ['n'=>'Drs. Ahmad Fauzi, M.Pd', 'r'=>'Kepala SMAN 1 Bandung',        'av'=>'AF', 'g'=>'from-indigo-400 to-violet-500', 'q'=>'EduZone benar-benar mengubah cara kami mengelola sekolah. Laporan keuangan yang dulu 3 hari kini selesai dalam hitungan menit!', 's'=>5],
                ['n'=>'Hj. Siti Rahma, S.Pd',  'r'=>'Kepala SMKN 2 Surabaya',       'av'=>'SR', 'g'=>'from-pink-400 to-rose-500',     'q'=>'Fitur absensi digital dan notifikasi ke orang tua sangat membantu. Guru-guru yang tidak melek teknologi pun bisa pakai dengan mudah.', 's'=>5],
                ['n'=>'Budi Santoso, S.E',      'r'=>'Bendahara SMA Maju Jaya',      'av'=>'BS', 'g'=>'from-cyan-400 to-blue-500',     'q'=>'Dana BOS jadi lebih transparan dan mudah dilaporkan ke dinas. Audit keuangan bisa dilakukan kapan saja karena semua tercatat rapi.', 's'=>5],
            ];
            @endphp
            @foreach($testis as $i => $t)
            <div class="testi-card p-6 reveal" style="transition-delay:{{$i*120}}ms">
                <div class="flex items-center gap-0.5 mb-4">
                    @for($s=0;$s<$t['s'];$s++)
                    <svg style="width:16px;height:16px;" fill="#f59e0b" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mb-6 italic">"{{ $t['q'] }}"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $t['g'] }} flex items-center justify-center text-white font-bold text-sm flex-shrink-0">{{ $t['av'] }}</div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $t['n'] }}</p>
                        <p class="text-xs text-slate-400">{{ $t['r'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- CTA                                                       --}}
{{-- ======================================================== --}}
<section class="py-20 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto">
        <div class="cta-section rounded-3xl p-12 lg:p-20 text-center reveal">
            <div class="relative z-10">
                <div class="text-5xl mb-6">🚀</div>
                <h2 class="text-3xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
                    Siap Bawa Sekolahmu<br>ke Level Berikutnya?
                </h2>
                <p class="text-indigo-200 text-lg mb-10 max-w-xl mx-auto">
                    Bergabunglah dengan 500+ sekolah yang sudah lebih cerdas dan efisien bersama EduZone. Gratis 30 hari, tidak perlu kartu kredit.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#" class="inline-flex items-center justify-center gap-2 bg-white text-indigo-700 font-bold px-8 py-4 rounded-xl hover:bg-indigo-50 transition-colors text-sm shadow-xl">
                        <span>🎯 Coba Gratis 30 Hari</span>
                    </a>
                    <a href="#" class="inline-flex items-center justify-center gap-2 bg-white/10 border border-white/20 text-white font-bold px-8 py-4 rounded-xl hover:bg-white/20 transition-colors text-sm">
                        <span>📞 Jadwalkan Demo</span>
                    </a>
                </div>
                <p class="text-indigo-300 text-xs mt-6">Tidak perlu kartu kredit · Setup 5 menit · Support 24/7</p>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================== --}}
{{-- FOOTER                                                    --}}
{{-- ======================================================== --}}
<footer class="bg-slate-950 text-slate-400 pt-16 pb-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#ec4899);">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <span class="text-white font-bold text-lg">EduZone</span>
                </div>
                <p class="text-sm leading-relaxed mb-4">Platform SaaS manajemen sekolah modern untuk Indonesia. Lebih cerdas, lebih efisien.</p>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-xs text-emerald-400 font-medium">Semua sistem berjalan normal</span>
                </div>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-5">Produk</p>
                <ul class="space-y-3 text-sm">
                    @foreach(['Fitur','Harga','Demo','Changelog','Roadmap'] as $l)
                    <li><a href="#" class="hover:text-white transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-5">Perusahaan</p>
                <ul class="space-y-3 text-sm">
                    @foreach(['Tentang Kami','Blog','Karir','Kontak','Press Kit'] as $l)
                    <li><a href="#" class="hover:text-white transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-5">Bantuan</p>
                <ul class="space-y-3 text-sm">
                    @foreach(['Dokumentasi','Panduan Penggunaan','Status Sistem','Kebijakan Privasi','Syarat & Ketentuan'] as $l)
                    <li><a href="#" class="hover:text-white transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs">© {{ date('Y') }} EduZone. Hak Cipta Dilindungi.</p>
            <p class="text-xs flex items-center gap-1.5">
                Dibuat dengan <svg style="width:12px;height:12px;" fill="#ec4899" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                untuk pendidikan Indonesia
            </p>
        </div>
    </div>
</footer>

<script>
// Navbar
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 10) navbar.classList.add('scrolled-nav');
    else navbar.classList.remove('scrolled-nav');
});

// Mobile menu
document.getElementById('menu-btn').addEventListener('click', () => {
    document.getElementById('mobile-menu').classList.toggle('open');
});
document.querySelectorAll('#mobile-menu a').forEach(a => {
    a.addEventListener('click', () => document.getElementById('mobile-menu').classList.remove('open'));
});

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal, .reveal-l, .reveal-r').forEach(el => observer.observe(el));
</script>
</body>
</html>
