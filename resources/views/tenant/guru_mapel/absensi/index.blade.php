@extends('tenant.layouts.app')

@section('title', 'Absensi Mengajar')
@section('page-title', 'Absensi Mengajar Hari Ini')

@section('content')
@if($noTeacher)
    <div class="t-card p-6 text-center text-gray-500">{{ $reason }}</div>
@else
    <div class="space-y-6">
        @if(session('error'))
            <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

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
                {{-- Kartu bukan lagi satu link besar: ada dua aksi terpisah di bawah (Detail dan Isi absensi) --}}
                <div class="t-card p-5 flex flex-col hover:shadow-md transition">
                    <div class="font-semibold">
                        {{ $sch['subject_name'] }}
                        @if($sch['class_name'])
                            <span class="font-normal text-gray-500">— {{ $sch['class_name'] }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $sch['start_time'] }} – {{ $sch['end_time'] }}</div>

                    @if($sch['covering_for'])
                        <div class="mt-2"><span class="badge badge-gold">Menggantikan {{ $sch['covering_for'] }}</span></div>
                    @endif
                    @if($sch['substituted_by'])
                        <div class="mt-2"><span class="badge badge-amber">Digantikan {{ $sch['substituted_by'] }}</span></div>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="badge badge-green">{{ $sch['hadir'] }} / {{ $sch['total_siswa'] }} hadir</span>
                        @if($sch['sesi_tersimpan'] ?? false)
                            <span class="badge badge-slate">Absensi jam ini tersimpan</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3 pt-3 border-t border-gray-100">
                        <a href="{{ route('guru_mapel.absensi.detail', $sch['schedule_id']) }}"
                           class="text-xs text-gray-500 hover:underline">Lihat detail →</a>

                        @if(! $sch['substituted_by'])
                            <a href="{{ route('guru_mapel.absensi.sesi.edit', $sch['schedule_id']) }}"
                               class="rounded-md bg-[#1B3A34] px-3 py-1.5 text-xs font-medium text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2">
                                {{ ($sch['sesi_tersimpan'] ?? false) ? 'Ubah absensi' : 'Isi absensi' }}
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="t-card p-6 text-center text-gray-500 md:col-span-2 lg:col-span-3">
                    Tidak ada jadwal mengajar hari ini.
                </div>
            @endforelse
        </div>
    </div>
@endif
@endsection
