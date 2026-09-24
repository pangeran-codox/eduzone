@extends('tenant.layouts.app')

@section('title', 'Absensi Mengajar')
@section('page-title', 'Absensi Mengajar Hari Ini')

@section('content')
@if($noTeacher)
    <div class="t-card p-6 text-center text-gray-500">{{ $reason }}</div>
@else
    <div class="space-y-6">
        <div class="t-card p-5 flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500">Absensi Anda Hari Ini</div>
                @if($ownAttendance)
                    <div class="text-lg font-semibold mt-1">
                        {{ $ownAttendance->status }}
                        <span class="text-sm font-normal text-gray-500">
                            — masuk jam {{ $ownAttendance->first_check_in }}
                        </span>
                    </div>
                @else
                    <div class="text-lg font-semibold mt-1 text-gray-400">Belum Absen</div>
                @endif
            </div>
            <a href="{{ request()->fullUrl() }}" class="badge badge-slate">Refresh</a>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($schedules as $sch)
                <a href="{{ route('guru_mapel.absensi.detail', $sch['schedule_id']) }}"
                   class="t-card p-5 block hover:shadow-md transition">
                    <div class="font-semibold">{{ $sch['subject_name'] }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $sch['start_time'] }} – {{ $sch['end_time'] }}</div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="badge badge-green">{{ $sch['hadir'] }} / {{ $sch['total_siswa'] }} hadir</span>
                        <span class="text-xs text-gray-400">Lihat detail →</span>
                    </div>
                </a>
            @empty
                <div class="t-card p-6 text-center text-gray-500 md:col-span-2 lg:col-span-3">
                    Tidak ada jadwal mengajar hari ini.
                </div>
            @endforelse
        </div>
    </div>
@endif
@endsection