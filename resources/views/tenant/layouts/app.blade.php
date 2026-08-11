<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        * { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        .font-serif-brand { font-family: 'Fraunces', serif; }

        :root {
            --t-bg:      #F6F3EC;
            --t-dark:    #1B3A34;
            --t-dark-2:  #234840;
            --t-gold:    #C9A227;
            --t-text:    #1C1C1A;
            --t-muted:   #6B6B63;
            --t-border:  #D8D4C6;
            --t-green-bg: #EAF3DE;
            --t-green-tx: #3B6D11;
            --t-red-bg:  #FCEBEB;
            --t-red-tx:  #A32D2D;
            --t-amber-bg: #FBF0DC;
            --t-amber-tx: #92620A;
            --t-slate-bg: #EDEBE3;
            --t-slate-tx: #6B6B63;
        }

        body { background-color: var(--t-bg); color: var(--t-text); }

        .sidebar {
            background: var(--t-dark);
            width: 250px;
            flex-shrink: 0;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            color: #9FB8AE;
            transition: all 0.18s ease;
            text-decoration: none;
        }
        .nav-item:hover { color: #F6F3EC; background: rgba(255,255,255,0.06); }
        .nav-item.active { color: var(--t-dark); background: var(--t-gold); font-weight: 600; }
        .nav-item .icon { width: 17px; height: 17px; flex-shrink: 0; opacity: 0.85; }
        .nav-item.active .icon { opacity: 1; }
        .nav-section {
            font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
            color: #5E7B72; padding: 0 14px; margin: 18px 0 6px;
        }

        .topbar { background: #fff; border-bottom: 1px solid var(--t-border); height: 56px; }
        .main-content { background: var(--t-bg); flex: 1; overflow-y: auto; }

        .t-card { background: #fff; border: 1px solid var(--t-border); border-radius: 14px; }
        .stat-card { background: #fff; border: 1px solid var(--t-border); border-radius: 14px; padding: 18px; }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 999px;
        }
        .badge-green { background: var(--t-green-bg); color: var(--t-green-tx); }
        .badge-red   { background: var(--t-red-bg);   color: var(--t-red-tx); }
        .badge-amber { background: var(--t-amber-bg); color: var(--t-amber-tx); }
        .badge-slate { background: var(--t-slate-bg); color: var(--t-slate-tx); }
        .badge-gold  { background: rgba(201,162,39,0.15); color: #8A6D1B; }

        .t-table { width: 100%; border-collapse: collapse; }
        .t-table th {
            text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; color: #8A8A80; padding: 10px 16px;
            border-bottom: 1px solid var(--t-border);
        }
        .t-table td {
            padding: 12px 16px; font-size: 13.5px; color: #3A3A34;
            border-bottom: 1px solid #EFEBE0;
        }
        .t-table tr:last-child td { border-bottom: none; }
        .t-table tr:hover td { background: #FBFAF6; }

        #sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
        #sidebar-overlay.active { display: block; }
        .sidebar { z-index: 50; }
        @media (max-width: 1023px) {
            .sidebar { position: fixed; top: 0; left: 0; bottom: 0; transform: translateX(-100%); transition: transform 0.25s ease; }
            .sidebar.open { transform: translateX(0); }
        }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(27,58,52,0.2); border-radius: 999px; }
    </style>
    @stack('styles')
</head>
<body class="h-full flex overflow-hidden">

<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<aside id="sidebar" class="sidebar flex flex-col h-full overflow-y-auto">
    <div class="px-5 py-5 border-b" style="border-color: rgba(255,255,255,0.08);">
        <div class="font-serif-brand text-lg" style="color:#F6F3EC;">EduZone</div>
        <div class="text-xs mt-0.5" style="color:#7C9A8E;">{{ auth()->user()->school->name ?? 'Sekolah' }}</div>
    </div>

    <nav class="flex-1 px-3 py-4">
        <p class="nav-section">Menu</p>

        @if (Route::has('guru.dashboard'))
        <a href="{{ route('guru.dashboard') }}" class="nav-item {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            Dashboard
        </a>
        @endif

        @if (auth()->user()->role === 'wali_kelas' && Route::has('wali_kelas.absensi.dashboard'))
        <a href="{{ route('wali_kelas.absensi.dashboard') }}" class="nav-item {{ request()->routeIs('wali_kelas.absensi.dashboard') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            Absensi Kelas
        </a>
        @endif
    </nav>

    <div class="px-3 py-4 border-t" style="border-color: rgba(255,255,255,0.08);">
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl" style="background: rgba(255,255,255,0.06);">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold flex-shrink-0"
                 style="background: var(--t-gold); color: var(--t-dark);">
                {{ strtoupper(substr(auth()->user()->username ?? auth()->user()->email ?? 'U', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold truncate" style="color:#F6F3EC;">{{ auth()->user()->username ?? auth()->user()->email }}</p>
                <p class="text-xs truncate" style="color:#9FB8AE;">Wali Kelas</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg" style="color:#9FB8AE;" title="Keluar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="flex flex-col flex-1 overflow-hidden">
    <header class="topbar flex items-center justify-between px-6 flex-shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="openSidebar()" class="lg:hidden p-1.5 rounded-lg" style="color:#6B6B63;">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <span class="font-semibold text-sm" style="color: var(--t-dark);">@yield('page-title', 'Dashboard')</span>
        </div>
        <span class="hidden md:block text-xs" style="color:#8A8A80;">{{ now()->translatedFormat('l, d M Y') }}</span>
    </header>

    <main class="main-content">
        <div class="p-6">
            @yield('content')
        </div>
    </main>
</div>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('active');
}
</script>

@stack('scripts')
</body>
</html>
