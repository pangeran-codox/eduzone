@extends('tenant.layouts.app')

@section('title', 'Absensi Kelas' . ($class ? ' — ' . $class->nama_kelas : ''))
@section('page-title', 'Absensi Kelas' . ($class ? ' — ' . $class->nama_kelas : ''))

@section('content')

@if ($noClass)

    {{-- ── Tidak ada kelas ──────────────────────────────────────────── --}}
    <div class="t-card p-10 text-center max-w-md mx-auto mt-10">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background: var(--t-slate-bg);">
            <svg class="w-7 h-7" style="color:var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
            </svg>
        </div>
        <p class="font-semibold text-sm mb-1" style="color: var(--t-dark);">Belum ada kelas yang bisa ditampilkan</p>
        <p class="text-sm mt-1" style="color: var(--t-muted);">{{ $reason }}</p>
        <a href="{{ route('guru.dashboard') }}" class="inline-block mt-5 text-xs font-semibold px-4 py-2 rounded-lg"
           style="background: var(--t-gold); color: var(--t-dark);">
            Kembali ke Dashboard
        </a>
    </div>

@else

    {{-- ── Header info kelas ────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
                {{ $class->nama_kelas }}
            </h1>
            <p class="text-sm" style="color: var(--t-muted);">
                {{ now()->translatedFormat('l, d F Y') }}
                @if ($class->major)
                    &nbsp;·&nbsp; {{ $class->major->name }}
                @endif
                &nbsp;·&nbsp; Tahun Ajaran {{ $class->academic_year }}
            </p>
        </div>
        <a href="{{ route('guru.dashboard') }}"
           class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border"
           style="color:var(--t-muted); border-color:var(--t-border);">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Dashboard
        </a>
    </div>

    {{-- ── Stat cards ───────────────────────────────────────────────── --}}
    @php
        $pctHadir = $totalSiswa > 0 ? round($stats['hadir'] / $totalSiswa * 100) : 0;
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">

        <div class="stat-card">
            <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--t-muted);">Total Siswa</p>
            <p class="text-3xl font-bold" style="color:var(--t-dark);">{{ $totalSiswa }}</p>
            <span class="badge badge-slate mt-2">Di kelas ini</span>
        </div>

        <div class="stat-card" style="background:var(--t-green-bg); border-color:rgba(59,109,17,0.15);">
            <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--t-green-tx);">Hadir</p>
            <p class="text-3xl font-bold" style="color:var(--t-green-tx);">{{ $stats['hadir'] }}</p>
            <span class="badge badge-green mt-2">{{ $pctHadir }}% kehadiran</span>
        </div>

        <div class="stat-card" style="background:var(--t-slate-bg); border-color:rgba(107,107,99,0.15);">
            <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--t-slate-tx);">Belum Absen</p>
            <p class="text-3xl font-bold" style="color:var(--t-slate-tx);">{{ $stats['belum'] }}</p>
            <span class="badge badge-slate mt-2">Belum ada event</span>
        </div>

        <div class="stat-card" style="background:var(--t-amber-bg); border-color:rgba(146,98,10,0.15);">
            <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--t-amber-tx);">Anomali</p>
            <p class="text-3xl font-bold" style="color:var(--t-amber-tx);">{{ $stats['anomali'] }}</p>
            <span class="badge badge-amber mt-2">Perlu dicek</span>
        </div>

    </div>

    {{-- ── Progress bar kehadiran ───────────────────────────────────── --}}
    @if ($totalSiswa > 0)
    <div class="t-card px-5 py-4 mb-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold" style="color:var(--t-muted);">Progres kehadiran hari ini</span>
            <span class="text-xs font-bold" style="color:var(--t-dark);">{{ $stats['hadir'] }} / {{ $totalSiswa }} siswa</span>
        </div>
        <div class="w-full rounded-full h-2.5" style="background:var(--t-border);">
            <div class="h-2.5 rounded-full transition-all"
                 style="width: {{ $pctHadir }}%; background: var(--t-gold);"></div>
        </div>
        <p class="text-xs mt-1.5" style="color:var(--t-muted);">
            {{ $pctHadir }}% hadir
            @if ($stats['belum'] > 0)
                — {{ $stats['belum'] }} siswa belum tercatat
            @else
                — Semua siswa sudah absen 🎉
            @endif
        </p>
    </div>
    @endif

    {{-- ── Tabel daftar siswa ───────────────────────────────────────── --}}
    <div class="t-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b" style="border-color:var(--t-border);">
            <span class="font-semibold text-sm" style="color:var(--t-dark);">Daftar Siswa</span>
            <span class="text-xs" style="color:var(--t-muted);">
                {{ $records->count() }} siswa
                &nbsp;·&nbsp;
                <span class="text-xs italic" style="color:var(--t-muted);">
                    Status Izin/Sakit/Alpa belum tersedia — hanya dari perangkat absensi
                </span>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="t-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Nama Siswa</th>
                        <th>Jam Masuk</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $i => $record)
                        <tr>
                            <td class="text-xs" style="color:var(--t-muted);">{{ $i + 1 }}</td>
                            <td>
                                <span class="font-medium" style="color:var(--t-dark);">
                                    {{ $record['nama'] }}
                                </span>
                                @if ($record['has_anomaly'])
                                    <span class="badge badge-amber ml-1.5">⚠ Anomali</span>
                                @endif
                            </td>
                            <td class="font-mono text-sm">{{ $record['waktu'] ?? '—' }}</td>
                            <td style="color:var(--t-muted);">{{ $record['metode'] ?? '—' }}</td>
                            <td>
                                @if ($record['status'] === 'Hadir')
                                    <span class="badge badge-green">Hadir</span>
                                @else
                                    <span class="badge badge-slate">Belum Absen</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm" style="color:var(--t-muted);">
                                Tidak ada siswa aktif yang terdaftar di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endif

@endsection
