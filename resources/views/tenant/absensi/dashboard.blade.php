@extends('tenant.layouts.app')

@section('title', 'Absensi Kelas')
@section('page-title', 'Absensi Kelas')

@section('content')

@if ($noClass)

<div class="t-card p-10 text-center max-w-lg mx-auto mt-10">
    <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: var(--t-amber-bg);">
        <svg class="w-6 h-6" style="color: var(--t-amber-tx);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
    </div>
    <p class="font-semibold mb-1" style="color: var(--t-dark);">Belum Ada Kelas</p>
    <p class="text-sm" style="color: var(--t-muted);">{{ $reason }}</p>
    <p class="text-xs mt-3" style="color: var(--t-muted);">Hubungi Tata Usaha kalau ini seharusnya sudah diatur.</p>
</div>

@else

<div class="mb-6">
    <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
        Absensi {{ $class->nama_kelas ?? 'Kelas' }} — Hari Ini
    </h1>
    <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

{{-- ── Stat Cards ───────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-2xl font-extrabold" style="color: var(--t-green-tx);">{{ $stats['hadir'] }}</p>
        <p class="text-xs mt-1" style="color: var(--t-muted);">Hadir</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold" style="color: var(--t-amber-tx);">{{ $stats['terlambat'] }}</p>
        <p class="text-xs mt-1" style="color: var(--t-muted);">Terlambat</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold" style="color: #4A6FA5;">{{ $stats['izin'] }}</p>
        <p class="text-xs mt-1" style="color: var(--t-muted);">Izin/Sakit</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold" style="color: var(--t-red-tx);">{{ $stats['alpa'] }}</p>
        <p class="text-xs mt-1" style="color: var(--t-muted);">Alpa</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold" style="color: var(--t-muted);">{{ $stats['belum'] }}</p>
        <p class="text-xs mt-1" style="color: var(--t-muted);">Belum Absen</p>
    </div>
</div>

{{-- ── Tabel Siswa ──────────────────────────────────────────────────── --}}
<div class="t-card overflow-hidden">
    @if ($records->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color: var(--t-muted);">Belum ada siswa terdaftar di kelas ini.</p>
    </div>
    @else
    <table class="t-table">
        <thead>
            <tr>
                <th>Nama Siswa</th>
                <th>Jam Masuk</th>
                <th>Metode</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                             style="background: var(--t-dark); color: #F6F3EC;">
                            {{ strtoupper(substr($record['nama'], 0, 1)) }}
                        </div>
                        <p class="font-medium">{{ $record['nama'] }}</p>
                        @if ($record['has_anomaly'])
                            <span title="Ada anomali terdeteksi pada absensi ini" style="color: var(--t-amber-tx);">⚠</span>
                        @endif
                    </div>
                </td>
                <td>{{ $record['waktu'] ?? '—' }}</td>
                <td>{{ $record['metode'] ?? '—' }}</td>
                <td>
                    @php
                        $statusBadge = match ($record['status']) {
                            'Hadir' => 'badge-green',
                            'Terlambat' => 'badge-amber',
                            'Izin', 'Sakit' => 'badge-slate',
                            'Alpa' => 'badge-red',
                            default => 'badge-slate',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ $record['status'] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endif

@endsection
