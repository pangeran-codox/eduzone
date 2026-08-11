@extends('tenant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    $user = auth()->user();
    $isWaliKelas = $user->role === 'wali_kelas';
@endphp

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Selamat datang, {{ $user->username ?? 'Bapak/Ibu Guru' }} 👋
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    @if ($isWaliKelas && Route::has('wali_kelas.absensi.dashboard'))
    <a href="{{ route('wali_kelas.absensi.dashboard') }}" class="t-card p-5 block hover:opacity-90 transition-opacity">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: rgba(201,162,39,0.15);">
            <svg class="w-5 h-5" style="color: #8A6D1B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Absensi Kelas</p>
        <p class="text-xs" style="color: var(--t-muted);">Lihat status hadir siswa kelas Anda hari ini</p>
    </a>
    @endif

    {{-- Placeholder fitur lain yang belum digarap — tampil redup, bukan link aktif --}}
    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Jurnal Mengajar</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

    <div class="t-card p-5" style="opacity: 0.5;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
            <svg class="w-5 h-5" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Input Nilai</p>
        <p class="text-xs" style="color: var(--t-muted);">Segera hadir</p>
    </div>

</div>

@endsection
