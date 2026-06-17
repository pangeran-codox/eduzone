<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') — EduZone Control Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        :root {
            --sa-bg:        #080c14;
            --sa-surface:   #0d1525;
            --sa-surface-2: #111827;
            --sa-border:    rgba(99,102,241,0.12);
            --sa-text:      #e2e8f0;
            --sa-muted:     #64748b;
            --sa-indigo:    #6366f1;
            --sa-violet:    #8b5cf6;
        }

        body {
            background-color: var(--sa-bg);
            color: var(--sa-text);
        }

        /* Sidebar */
        .sidebar {
            background: var(--sa-surface);
            border-right: 1px solid var(--sa-border);
            width: 260px;
            flex-shrink: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--sa-muted);
            transition: all 0.18s ease;
            text-decoration: none;
        }
        .nav-item:hover {
            color: #c7d2fe;
            background: rgba(99,102,241,0.08);
        }
        .nav-item.active {
            color: #a5b4fc;
            background: rgba(99,102,241,0.12);
            font-weight: 600;
        }
        .nav-item .icon {
            width: 18px; height: 18px;
            flex-shrink: 0;
            opacity: 0.75;
        }
        .nav-item.active .icon { opacity: 1; }

        .nav-section {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #334155;
            padding: 0 14px;
            margin: 20px 0 6px;
        }

        /* Topbar */
        .topbar {
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            height: 56px;
        }

        /* Content */
        .main-content {
            background: var(--sa-bg);
            flex: 1;
            overflow-y: auto;
        }

        /* Card */
        .sa-card {
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 14px;
        }

        /* Stat card */
        .stat-card {
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 14px;
            padding: 20px;
            transition: border-color 0.2s ease;
        }
        .stat-card:hover {
            border-color: rgba(99,102,241,0.3);
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .badge-indigo { background: rgba(99,102,241,0.15); color: #a5b4fc; }
        .badge-green  { background: rgba(16,185,129,0.12); color: #6ee7b7; }
        .badge-amber  { background: rgba(245,158,11,0.12); color: #fcd34d; }
        .badge-red    { background: rgba(239,68,68,0.12);  color: #fca5a5; }
        .badge-violet { background: rgba(139,92,246,0.15); color: #c4b5fd; }
        .badge-slate  { background: rgba(100,116,139,0.15);color: #94a3b8; }

        /* Table */
        .sa-table { width: 100%; border-collapse: collapse; }
        .sa-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #475569;
            padding: 10px 16px;
            border-bottom: 1px solid var(--sa-border);
        }
        .sa-table td {
            padding: 12px 16px;
            font-size: 13.5px;
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .sa-table tr:last-child td { border-bottom: none; }
        .sa-table tr:hover td { background: rgba(99,102,241,0.04); }

        /* Grid bg */
        .grid-bg {
            background-image:
                linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Mobile sidebar */
        #sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 40;
        }
        #sidebar-overlay.active { display: block; }
        .sidebar { z-index: 50; }
        @media (max-width: 1023px) {
            .sidebar {
                position: fixed;
                top: 0; left: 0; bottom: 0;
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }
            .sidebar.open { transform: translateX(0); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.2); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99,102,241,0.4); }
    </style>
    @stack('styles')
</head>
<body class="h-full flex overflow-hidden">

{{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<aside id="sidebar" class="sidebar flex flex-col h-full overflow-y-auto">

    {{-- Logo --}}
    <div class="px-5 py-5 border-b" style="border-color: var(--sa-border);">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-white leading-none">EduZone</p>
                <p class="text-xs mt-0.5" style="color:#6366f1;">Control Panel</p>
            </div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4">

        <p class="nav-section">Overview</p>
        <a href="{{ route('superadmin.dashboard') }}"
           class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            Dashboard
        </a>

        <p class="nav-section">Manajemen</p>
        <a href="{{ route('superadmin.schools.index') }}"
           class="nav-item {{ request()->routeIs('superadmin.schools.*') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
            </svg>
            Sekolah
            <span class="ml-auto badge badge-indigo">{{ \App\Models\School::count() }}</span>
        </a>

        <a href="{{ route('superadmin.users.index') }}"
           class="nav-item {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            Pengguna
        </a>

        <a href="{{ route('superadmin.subscriptions.index') }}"
           class="nav-item {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
            </svg>
            Langganan
        </a>

        <p class="nav-section">Sistem</p>
        <a href="{{ route('superadmin.logs') }}"
           class="nav-item {{ request()->routeIs('superadmin.logs') ? 'active' : '' }}">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
            Audit Log
        </a>

    </nav>

    {{-- User info --}}
    <div class="px-3 py-4 border-t" style="border-color:var(--sa-border);">
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl" style="background:rgba(99,102,241,0.06);">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                {{ strtoupper(substr(auth()->user()->username ?? auth()->user()->email, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->username ?? 'Superadmin' }}</p>
                <p class="text-xs truncate" style="color:#6366f1;">Superadmin</p>
            </div>
            <form method="POST" action="{{ route('superadmin.logout') }}">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg transition-colors hover:bg-red-500/10 text-slate-500 hover:text-red-400"
                        title="Logout">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ── Main ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-col flex-1 overflow-hidden">

    {{-- Topbar --}}
    <header class="topbar flex items-center justify-between px-6 flex-shrink-0">
        <div class="flex items-center gap-4">
            {{-- Mobile hamburger --}}
            <button onclick="openSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-sm">
                <span style="color:#475569;">Control Panel</span>
                <span style="color:#334155;">/</span>
                <span class="font-semibold text-white">@yield('page-title', 'Dashboard')</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Status indicator --}}
            <div class="hidden sm:flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full"
                 style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#6ee7b7;">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Sistem Normal
            </div>

            {{-- Date --}}
            <span class="hidden md:block text-xs" style="color:#475569;">
                {{ now()->translatedFormat('l, d M Y') }}
            </span>
        </div>
    </header>

    {{-- Page content --}}
    <main class="main-content grid-bg">
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
