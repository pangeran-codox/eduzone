@extends('tenant.layouts.app')

@section('title', 'Dashboard Tata Usaha')
@section('page-title', 'Dashboard Tata Usaha')

@section('content')

<div class="mb-6">
    <h1 class="font-serif-brand text-2xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ auth()->user()->username ?? 'Tata Usaha' }}
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Siswa Aktif</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalSiswaAktif }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Guru</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalGuru }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Staff</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalStaff }}</p>
    </div>
</div>

<div class="t-card p-6">
    <p class="section-label" style="border:none; padding:0; margin-bottom:14px;">Menu Cepat</p>
    <div class="grid sm:grid-cols-2 gap-3">
        <a href="{{ route('tu.siswa.index') }}" class="flex items-center gap-3 p-4 rounded-xl border" style="border-color: var(--t-border);">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--t-slate-bg);">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="var(--t-dark)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold" style="color: var(--t-text);">Data Siswa</p>
                <p class="text-xs" style="color: var(--t-muted);">Kelola data induk siswa</p>
            </div>
        </a>

        @if (Route::has('absensi.devices.index'))
        <a href="{{ route('absensi.devices.index') }}" class="flex items-center gap-3 p-4 rounded-xl border" style="border-color: var(--t-border);">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--t-slate-bg);">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="var(--t-dark)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold" style="color: var(--t-text);">Device Absensi</p>
                <p class="text-xs" style="color: var(--t-muted);">Kelola perangkat absensi sekolah</p>
            </div>
        </a>
        @endif
    </div>
</div>

@endsection