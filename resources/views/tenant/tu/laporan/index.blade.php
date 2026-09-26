@extends('tenant.layouts.app')

@section('title', 'Laporan Absensi')
@section('page-title', 'Rekap Absensi Periode')

@section('content')
<div class="space-y-4">
    <form method="GET" class="t-card p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tipe</label>
            <select name="person_type" class="border rounded px-3 py-2 text-sm">
                <option value="student" {{ $personType === 'student' ? 'selected' : '' }}>Siswa</option>
                <option value="teacher" {{ $personType === 'teacher' ? 'selected' : '' }}>Guru</option>
            </select>
        </div>
        @if($personType === 'student')
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kelas</label>
            <select name="class_id" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>{{ $c->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="badge badge-gold px-4 py-2">Tampilkan</button>

        <div class="ml-auto flex gap-2">
            <a href="{{ route('tu.laporan.export-excel', request()->query()) }}" class="text-sm text-green-700 hover:underline">Export Excel</a>
            <a href="{{ route('tu.laporan.export-pdf', request()->query()) }}" class="text-sm text-red-700 hover:underline">Export PDF</a>
        </div>
    </form>

    <div class="t-card overflow-hidden">
        <table class="t-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alpa</th>
                    <th>Tidak Tercatat</th>
                    <th>% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['nama'] }}</td>
                        <td>{{ $r['counts']['Hadir'] }}</td>
                        <td>{{ $r['counts']['Terlambat'] }}</td>
                        <td>{{ $r['counts']['Sakit'] }}</td>
                        <td>{{ $r['counts']['Izin'] }}</td>
                        <td>{{ $r['counts']['Alpa'] }}</td>
                        <td class="text-gray-400">{{ $r['tidak_tercatat'] }}</td>
                        <td>
                            <span class="badge {{ $r['percentage'] >= 90 ? 'badge-green' : ($r['percentage'] >= 75 ? 'badge-amber' : 'badge-red') }}">
                                {{ $r['percentage'] }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-6">Tidak ada data untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection