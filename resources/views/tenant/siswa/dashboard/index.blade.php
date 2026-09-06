@extends('tenant.layouts.app')

@section('title', 'Dashboard Siswa')
@section('page-title', 'Dashboard Siswa')

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ $studentName ?? $user->username ?? 'Siswa' }} 👋
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">
        {{ $className !== '-' ? 'Kelas ' . e($className) . ' • ' : '' }}
        {{ now()->translatedFormat('l, d F Y') }}
    </p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Mata Pelajaran</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalJadwalMapel }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Rata-rata Nilai</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $rataRataNilai }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Kehadiran Bulan Ini</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">
            {{ $kehadiranHadir }}
            <span class="text-sm font-normal" style="color: var(--t-muted);">/ {{ $totalKehadiranBulanIni }}</span>
        </p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold mb-2" style="color: var(--t-muted);">Total Prestasi</p>
        <p class="text-2xl font-bold" style="color: var(--t-dark);">{{ $totalPrestasiSiswa }}</p>
    </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Jadwal Pelajaran</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Lihat Nilai & Raport</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Kalender Akademik</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.422 48.422 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Tugas & Materi</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Profil & Data Diri</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Prestasi & Poin</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

</div>
@endsection
