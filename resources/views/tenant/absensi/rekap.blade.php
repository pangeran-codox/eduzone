@extends('tenant.layouts.app')

@section('title', 'Rekap Absensi Sekolah')
@section('page-title', 'Rekap Absensi Sekolah')

@section('content')

    <div class="mb-6">
        <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
            Rekap Absensi — Hari Ini
        </h1>
        <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Total Siswa</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['total'] }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Hadir</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['hadir'] }}</p>
            <span class="badge badge-green mt-2">{{ $totals['persen'] }}%</span>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Belum Absen</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['belum'] }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Anomali</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['anomali'] }}</p>
            @if ($totals['anomali'] > 0)
                <span class="badge badge-amber mt-2">Perlu dicek</span>
            @endif
        </div>
    </div>

    <div class="t-card">
        <div class="px-5 py-4 border-b" style="border-color: var(--t-border);">
            <h2 class="text-sm font-semibold" style="color: var(--t-dark);">Per Kelas</h2>
        </div>
        <table class="t-table">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Total Siswa</th>
                    <th>Hadir</th>
                    <th>Belum Absen</th>
                    <th>Anomali</th>
                    <th>% Hadir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="font-medium" style="color: var(--t-dark);">{{ $row['nama_kelas'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['hadir'] }}</td>
                        <td>{{ $row['belum'] }}</td>
                        <td>
                            @if ($row['anomali'] > 0)
                                <span class="badge badge-amber">{{ $row['anomali'] }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div style="width: 60px; height: 6px; background: var(--t-slate-bg); border-radius: 999px; overflow: hidden;">
                                    <div style="width: {{ $row['persen'] }}%; height: 100%; background: var(--t-green-tx);"></div>
                                </div>
                                <span class="text-xs" style="color: var(--t-muted);">{{ $row['persen'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8" style="color: var(--t-muted);">
                            Belum ada kelas aktif terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection