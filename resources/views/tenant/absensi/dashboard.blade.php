@extends('tenant.layouts.app')

@section('title', 'Absensi Kelas')
@section('page-title', 'Absensi Kelas' . ($class ? ' — ' . $class->nama_kelas : ''))

@section('content')

    @if ($noClass)
        <div class="t-card p-8 text-center">
            <p class="text-sm font-semibold mb-1" style="color: var(--t-dark);">Belum ada kelas yang bisa ditampilkan</p>
            <p class="text-sm" style="color: var(--t-muted);">{{ $reason }}</p>
        </div>
    @else

        <div class="mb-6">
            <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
                Absensi {{ $class->nama_kelas }} — Hari Ini
            </h1>
            <p class="text-sm" style="color: var(--t-muted);">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>

        {{--
            Cuma 3 kategori — Hadir/Belum Absen dari event mentah, Anomali
            dari flag is_valid/flagged_reason. Izin/Sakit/Alpa/Terlambat
            belum bisa ditampilkan (lihat catatan di controller).
        --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <p class="text-xs font-medium" style="color: var(--t-muted);">Hadir</p>
                <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $stats['hadir'] }}</p>
                <span class="badge badge-green mt-2">Sudah check-in</span>
            </div>
            <div class="stat-card">
                <p class="text-xs font-medium" style="color: var(--t-muted);">Belum Absen</p>
                <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $stats['belum'] }}</p>
                <span class="badge badge-slate mt-2">Belum ada event</span>
            </div>
            <div class="stat-card">
                <p class="text-xs font-medium" style="color: var(--t-muted);">Anomali</p>
                <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $stats['anomali'] }}</p>
                <span class="badge badge-amber mt-2">Perlu dicek</span>
            </div>
        </div>

        <div class="t-card">
            <div class="px-5 py-4 border-b" style="border-color: var(--t-border);">
                <h2 class="text-sm font-semibold" style="color: var(--t-dark);">Daftar Siswa</h2>
            </div>
            <table class="t-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jam Masuk</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="font-medium" style="color: var(--t-dark);">
                                {{ $record['nama'] }}
                                @if ($record['has_anomaly'])
                                    <span class="badge badge-amber ml-1">Anomali</span>
                                @endif
                            </td>
                            <td>{{ $record['waktu'] ?? '—' }}</td>
                            <td>{{ $record['metode'] ?? '—' }}</td>
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
                            <td colspan="4" class="text-center py-8" style="color: var(--t-muted);">
                                Tidak ada siswa aktif di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @endif

@endsection