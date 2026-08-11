@extends('tenant.layouts.app')

@section('title', 'Dashboard Kepala Sekolah')
@section('page-title', 'Dashboard Kepala Sekolah')

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ $user->username ?? 'Kepala Sekolah' }} 👋
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    {{--
        Semua card kepsek masih placeholder — belum ada satupun route/controller
        khusus kepsek yang dibangun (baru dashboard shell ini). Begitu modul
        terkait selesai, ganti pola card jadi <a href="{{ route(...) }}">
        seperti contoh "Absensi Kelas" di dashboard guru.
    --}}

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Rekap Absensi Sekolah</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Ringkasan Keuangan</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Ringkasan Akademik</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Data Kesiswaan</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Pengumuman & Aktivitas</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

</div>
@endsection