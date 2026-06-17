@extends('superadmin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── Greeting ─────────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h1 class="text-xl font-bold text-white mb-1">
        Selamat datang, {{ auth()->user()->username ?? 'Superadmin' }} 👋
    </h1>
    <p class="text-sm" style="color:#64748b;">
        Berikut ringkasan sistem EduZone hari ini — {{ now()->translatedFormat('l, d F Y') }}
    </p>
</div>

{{-- ── Stat Cards ───────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Total Sekolah --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 style="background:rgba(99,102,241,0.15);">
                <svg class="w-4.5 h-4.5" style="width:18px;height:18px;color:#818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                </svg>
            </div>
            <span class="badge badge-green">Aktif {{ $stats['active_schools'] }}</span>
        </div>
        <p class="text-3xl font-extrabold text-white mb-0.5">{{ number_format($stats['total_schools']) }}</p>
        <p class="text-xs" style="color:#64748b;">Total Sekolah</p>
    </div>

    {{-- Total Pengguna --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 style="background:rgba(139,92,246,0.15);">
                <svg style="width:18px;height:18px;color:#c4b5fd;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <span class="badge badge-violet">Siswa {{ number_format($stats['total_students']) }}</span>
        </div>
        <p class="text-3xl font-extrabold text-white mb-0.5">{{ number_format($stats['total_users']) }}</p>
        <p class="text-xs" style="color:#64748b;">Total Pengguna</p>
    </div>

    {{-- Subscription --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 style="background:rgba(245,158,11,0.12);">
                <svg style="width:18px;height:18px;color:#fcd34d;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </div>
            @if($stats['expired_soon'] > 0)
                <span class="badge badge-red">⚠ {{ $stats['expired_soon'] }} expiring</span>
            @else
                <span class="badge badge-green">✓ Aman</span>
            @endif
        </div>
        <div class="flex items-end gap-3 mb-0.5">
            <p class="text-3xl font-extrabold text-white">{{ $stats['pro_schools'] }}</p>
            <p class="text-lg font-bold pb-0.5" style="color:#64748b;">/ {{ $stats['basic_schools'] }} / {{ $stats['trial_schools'] }}</p>
        </div>
        <p class="text-xs" style="color:#64748b;">Pro / Basic / Trial</p>
    </div>

    {{-- System --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 style="background:rgba(16,185,129,0.12);">
                <svg style="width:18px;height:18px;color:#6ee7b7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/>
                </svg>
            </div>
            <span class="badge badge-green">● Online</span>
        </div>
        <p class="text-3xl font-extrabold text-white mb-0.5">99.9%</p>
        <p class="text-xs" style="color:#64748b;">Uptime Sistem</p>
    </div>
</div>

{{-- ── Subscription breakdown bar ───────────────────────────────────── --}}
@if($stats['total_schools'] > 0)
<div class="sa-card p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm font-semibold text-white">Distribusi Paket Langganan</p>
        <p class="text-xs" style="color:#64748b;">{{ $stats['total_schools'] }} sekolah total</p>
    </div>
    <div class="flex rounded-xl overflow-hidden h-3 mb-3">
        @php
            $total = max($stats['total_schools'], 1);
            $proW  = round($stats['pro_schools']   / $total * 100);
            $basW  = round($stats['basic_schools']  / $total * 100);
            $triW  = 100 - $proW - $basW;
        @endphp
        @if($proW > 0)  <div style="width:{{$proW}}%;background:linear-gradient(90deg,#4f46e5,#7c3aed);"></div> @endif
        @if($basW > 0)  <div style="width:{{$basW}}%;background:linear-gradient(90deg,#0891b2,#6366f1);"></div> @endif
        @if($triW > 0)  <div style="width:{{$triW}}%;background:#1e293b;"></div> @endif
    </div>
    <div class="flex items-center gap-6 text-xs" style="color:#64748b;">
        <div class="flex items-center gap-1.5">
            <div class="w-2.5 h-2.5 rounded-sm" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);"></div>
            Pro ({{ $stats['pro_schools'] }})
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-2.5 h-2.5 rounded-sm" style="background:linear-gradient(135deg,#0891b2,#6366f1);"></div>
            Basic ({{ $stats['basic_schools'] }})
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-2.5 h-2.5 rounded-sm bg-slate-700"></div>
            Trial ({{ $stats['trial_schools'] }})
        </div>
    </div>
</div>
@endif

{{-- ── Two columns ──────────────────────────────────────────────────── --}}
<div class="grid lg:grid-cols-2 gap-4">

    {{-- Recent Schools --}}
    <div class="sa-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--sa-border);">
            <p class="text-sm font-semibold text-white">Sekolah Terbaru</p>
            <a href="{{ route('superadmin.schools.index') }}"
               class="text-xs font-semibold transition-colors" style="color:#6366f1;"
               onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#6366f1'">
                Lihat semua →
            </a>
        </div>
        @if($recent_schools->isEmpty())
        <div class="px-5 py-10 text-center">
            <p class="text-sm" style="color:#475569;">Belum ada sekolah terdaftar</p>
        </div>
        @else
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Sekolah</th>
                    <th>Paket</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_schools as $school)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                                {{ strtoupper(substr($school->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-white leading-tight truncate max-w-[140px]">{{ $school->name }}</p>
                                <p class="text-xs" style="color:#475569;">{{ $school->level }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $school->subscription_plan === 'pro' ? 'badge-violet' : ($school->subscription_plan === 'basic' ? 'badge-indigo' : 'badge-slate') }}">
                            {{ ucfirst($school->subscription_plan) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $school->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Recent Logs --}}
    <div class="sa-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--sa-border);">
            <p class="text-sm font-semibold text-white">Aktivitas Terakhir</p>
            <a href="{{ route('superadmin.logs') }}"
               class="text-xs font-semibold transition-colors" style="color:#6366f1;"
               onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#6366f1'">
                Semua log →
            </a>
        </div>
        @if($recent_logs->isEmpty())
        <div class="px-5 py-10 text-center">
            <p class="text-sm" style="color:#475569;">Belum ada aktivitas tercatat</p>
        </div>
        @else
        <div class="divide-y" style="divide-color:rgba(255,255,255,0.03);">
            @foreach($recent_logs as $log)
            <div class="flex items-start gap-3 px-5 py-3.5 hover:bg-white/[0.02] transition-colors">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                     style="background:rgba(99,102,241,0.12);">
                    <svg style="width:13px;height:13px;color:#818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ $log->action }}</p>
                    @if($log->description)
                    <p class="text-xs truncate mt-0.5" style="color:#475569;">{{ $log->description }}</p>
                    @endif
                    <p class="text-xs mt-1" style="color:#334155;">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@endsection
