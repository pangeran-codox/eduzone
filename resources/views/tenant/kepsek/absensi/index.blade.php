@extends('tenant.layouts.app')

@section('title', 'Monitoring Absensi')
@section('page-title', 'Monitoring Absensi Hari Ini')

@section('content')
<div
    x-data="{
        date: @js($date),
        loading: false,
        d: @js($initial),
        timer: null,
        statusColor(status) {
            return {
                Hadir: 'bg-green-100 text-green-700',
                Terlambat: 'bg-yellow-100 text-yellow-700',
                Sakit: 'bg-blue-100 text-blue-700',
                Izin: 'bg-indigo-100 text-indigo-700',
                Alpa: 'bg-red-100 text-red-700',
            }[status] ?? 'bg-gray-100 text-gray-700';
        },
        async refresh() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ date: this.date });
                const res = await fetch(`{{ route('kepsek.absensi.data') }}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    this.d = await res.json();
                }
            } catch (e) {
                console.error('Gagal memuat data absensi', e);
            } finally {
                this.loading = false;
            }
        },
        init() {
            this.timer = setInterval(() => this.refresh(), 8000);
        },
    }"
    x-init="init()"
    class="space-y-6"
>
    {{-- Filter tanggal --}}
    <div class="flex flex-wrap items-end gap-3 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
            <input type="date" x-model="date" @change="refresh()"
                   class="border rounded px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400 ml-auto">
            <span x-show="loading">Memuat...</span>
            <span>Update terakhir: <span x-text="d.generated_at"></span></span>
        </div>
    </div>

    {{-- Ringkasan persentase --}}
    <div class="bg-white p-6 rounded-lg shadow-sm flex items-center gap-6">
        <div>
            <div class="text-xs text-gray-500">Tingkat Kehadiran</div>
            <div class="text-4xl font-semibold" x-text="d.attendance_percentage + '%'"></div>
            <div class="text-xs text-gray-400 mt-1">
                <span x-text="d.checked_in_count"></span> dari <span x-text="d.total_people"></span> orang sudah absen
            </div>
        </div>
        <div class="ml-auto text-right">
            <div class="text-xs text-gray-500">Belum Absen</div>
            <div class="text-2xl font-semibold text-gray-700" x-text="d.not_checked_in_count"></div>
        </div>
    </div>

    {{-- Breakdown status --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <template x-for="status in ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa']" :key="status">
            <div class="p-4 rounded-lg shadow-sm" :class="statusColor(status)">
                <div class="text-xs" x-text="status"></div>
                <div class="text-2xl font-semibold" x-text="d.status_counts[status] ?? 0"></div>
            </div>
        </template>
    </div>

    {{-- Feed check-in terbaru --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b font-medium text-sm">Aktivitas Check-in Terbaru</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs">
                <tr>
                    <th class="text-left px-4 py-2">Nama</th>
                    <th class="text-left px-4 py-2">Tipe</th>
                    <th class="text-left px-4 py-2">Jam Masuk</th>
                    <th class="text-left px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in d.recent" :key="row.person_type + row.person_id">
                    <tr class="border-t">
                        <td class="px-4 py-2" x-text="row.full_name"></td>
                        <td class="px-4 py-2 text-gray-500" x-text="row.person_type"></td>
                        <td class="px-4 py-2" x-text="row.first_check_in"></td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded text-xs" :class="statusColor(row.status)" x-text="row.status"></span>
                            <span x-show="row.has_anomaly" class="ml-1 text-red-500 text-xs">⚠</span>
                        </td>
                    </tr>
                </template>
                <tr x-show="d.recent.length === 0">
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada check-in.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection