@extends('tenant.layouts.app')

@section('title', $subject_name . ' — Absensi')
@section('page-title', $subject_name)

@section('content')
<div class="space-y-4">
    <a href="{{ route('guru_mapel.absensi.index') }}" class="text-sm text-gray-500 hover:underline">
        &larr; Kembali ke Absensi Mengajar
    </a>

    <div class="t-card p-5 flex items-center justify-between">
        <div>
            <div class="font-semibold">{{ $subject_name }}</div>
            <div class="text-xs text-gray-500">
                {{ $start_time }} – {{ $end_time }} · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d M Y') }}
            </div>
        </div>
        <span class="badge badge-green">{{ $hadir }} / {{ $total_siswa }} hadir</span>
    </div>

    <div class="t-card overflow-hidden">
        <table class="t-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ $r['nama'] }}</td>
                        <td>{{ $r['waktu'] ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $r['status'] === 'Hadir' ? 'badge-green' : 'badge-red' }}">
                                {{ $r['status'] }}
                            </span>
                            @if($r['has_anomaly'])
                                <span class="text-red-500 text-xs ml-1">⚠</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-400">Belum ada siswa di kelas ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection