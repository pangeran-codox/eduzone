@extends('tenant.layouts.app')

@section('title', 'Dashboard Kesiswaan')
@section('page-title', 'Dashboard Kesiswaan')

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ $user->username ?? 'Staff Kesiswaan' }} 👋
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Siswa Aktif</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalSiswaAktif }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Total Prestasi</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalPrestasi }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Kasus Sikap</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalKasusSikap }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Prestasi Bulan Ini</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $prestasiBulanIni }}</p>
    </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Data Siswa & OSIS</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Ekstrakurikuler</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Pelanggaran & Sanksi</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Prestasi Siswa</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Kalender Kegiatan</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Pengaturan Seragam & Atribut</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

</div>
@endsection
