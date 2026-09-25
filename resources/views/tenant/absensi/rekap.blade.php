{{-- resources/views/tenant/absensi/rekap.blade.php --}}
@extends('tenant.layouts.app')

@section('title', 'Rekap Absensi Sekolah')
@section('page-title', 'Rekap Absensi Sekolah')

@section('content')

    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif-brand text-xl mb-1" style="color: var(--t-dark);">
                Rekap Absensi
            </h1>
            <p class="text-sm" style="color: var(--t-muted);">
                {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}
            </p>
        </div>

        <form method="GET" class="flex items-end gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Tanggal</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                       class="border rounded-lg px-3 py-2 text-sm"
                       style="border-color:var(--t-border);">
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Total Siswa</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['total'] }}</p>
        </div>
        <div class="stat-card" style="background:var(--t-green-bg); border-color:rgba(59,109,17,0.15);">
            <p class="text-xs font-medium" style="color: var(--t-green-tx);">Hadir</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-green-tx);">{{ $totals['hadir'] }}</p>
            <span class="badge badge-green mt-2">{{ $totals['persen'] }}%</span>
        </div>
        <div class="stat-card" style="background:var(--t-amber-bg); border-color:rgba(146,98,10,0.15);">
            <p class="text-xs font-medium" style="color: var(--t-amber-tx);">Terlambat</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-amber-tx);">{{ $totals['terlambat'] }}</p>
        </div>
        <div class="stat-card" style="background:var(--t-slate-bg); border-color:rgba(107,107,99,0.15);">
            <p class="text-xs font-medium" style="color: var(--t-slate-tx);">Sakit</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-slate-tx);">{{ $totals['sakit'] }}</p>
        </div>
        <div class="stat-card" style="background:rgba(201,162,39,0.1); border-color:rgba(201,162,39,0.2);">
            <p class="text-xs font-medium" style="color:#8A6D1B;">Izin</p>
            <p class="text-2xl font-semibold mt-1" style="color:#8A6D1B;">{{ $totals['izin'] }}</p>
        </div>
        <div class="stat-card" style="background:var(--t-red-bg); border-color:rgba(163,45,45,0.15);">
            <p class="text-xs font-medium" style="color: var(--t-red-tx);">Alpa</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-red-tx);">{{ $totals['alpa'] }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium" style="color: var(--t-muted);">Belum Absen</p>
            <p class="text-2xl font-semibold mt-1" style="color: var(--t-dark);">{{ $totals['belum'] }}</p>
        </div>
    </div>

    <div class="t-card overflow-hidden">
        <div class="px-5 py-4 border-b" style="border-color: var(--t-border);">
            <h2 class="text-sm font-semibold" style="color: var(--t-dark);">Per Kelas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="t-table">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Total</th>
                        <th>Hadir</th>
                        <th>Terlambat</th>
                        <th>Sakit</th>
                        <th>Izin</th>
                        <th>Alpa</th>
                        <th>Belum</th>
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
                            <td>{{ $row['terlambat'] > 0 ? $row['terlambat'] : '—' }}</td>
                            <td>{{ $row['sakit'] > 0 ? $row['sakit'] : '—' }}</td>
                            <td>{{ $row['izin'] > 0 ? $row['izin'] : '—' }}</td>
                            <td>{{ $row['alpa'] > 0 ? $row['alpa'] : '—' }}</td>
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
                            <td colspan="10" class="text-center py-8" style="color: var(--t-muted);">
                                Belum ada kelas aktif terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection