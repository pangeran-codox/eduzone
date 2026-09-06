@extends('tenant.layouts.app')

@section('title', 'Dashboard Toolman')
@section('page-title', 'Dashboard Toolman')

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ $user->username ?? 'Toolman' }} 👋
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Item Inventaris</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalItemInventaris }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Total Unit Aset</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalUnitInventaris }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Laporan Hari Ini</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $laporanHariIni }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Laporan Bulan Ini</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $laporanBulanIni }}</p>
    </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Daftar Inventaris & Aset</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Pemeliharaan & Perbaikan</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Tiket Permintaan Sarana</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Kelola Ruangan & Gedung</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Pengadaan Barang</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Checklist Kebersihan & Keamanan</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

</div>
@endsection
