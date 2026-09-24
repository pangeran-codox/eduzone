@extends('superadmin.layouts.app')

@section('title', 'Rekap Absensi')
@section('page-title', 'Rekap Kehadiran Hari Ini')

@section('content')
<div x-data="absensiRekap()" x-init="load(); autoRefresh = setInterval(load, 30000)">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Rekap Kehadiran Hari Ini</h1>
        <p class="text-sm" style="color:#64748b;">Ringkasan check-in per sekolah, lintas tenant</p>
    </div>
    <p class="text-xs" style="color:#475569;" x-text="lastChecked"></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-2xl font-extrabold text-white mb-0.5" x-text="loaded ? data.totals.percentage + '%' : '—'"></p>
        <p class="text-xs" style="color:#64748b;">Tingkat Kehadiran Global</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold text-white mb-0.5" x-text="loaded ? data.totals.checked_in : '—'"></p>
        <p class="text-xs" style="color:#64748b;">Total Sudah Absen</p>
    </div>
    <div class="stat-card">
        <p class="text-2xl font-extrabold text-white mb-0.5" x-text="loaded ? data.totals.anomali : '—'"></p>
        <p class="text-xs" style="color:#64748b;">Total Anomali</p>
    </div>
</div>

<div class="sa-card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--sa-border);">
        <p class="text-sm font-semibold text-white">Per Sekolah</p>
        <p class="text-xs" style="color:#64748b;" x-text="(data.schools?.length ?? 0) + ' sekolah aktif'"></p>
    </div>

    <div x-show="!loaded" class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Memuat rekap...</p>
    </div>

    <table class="sa-table" x-show="loaded && data.schools && data.schools.length > 0">
        <thead>
            <tr>
                <th>Sekolah</th>
                <th>Kehadiran</th>
                <th>Sudah / Total</th>
                <th>Anomali</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="school in data.schools" :key="school.school_id">
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed);"
                                 x-text="school.school_name.charAt(0).toUpperCase()">
                            </div>
                            <p class="text-xs font-semibold text-white leading-tight truncate max-w-[200px]" x-text="school.school_name"></p>
                        </div>
                    </td>
                    <td>
                        <span class="badge" :class="school.percentage >= 80 ? 'badge-green' : (school.percentage >= 50 ? 'badge-amber' : 'badge-red')">
                            <span x-text="school.percentage + '%'"></span>
                        </span>
                    </td>
                    <td>
                        <span class="text-xs" style="color:#94a3b8;">
                            <span x-text="school.checked_in"></span> / <span x-text="school.total_people"></span>
                        </span>
                    </td>
                    <td>
                        <span class="text-xs" :style="school.anomali > 0 ? 'color:#fca5a5;' : 'color:#64748b;'" x-text="school.anomali"></span>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

</div>

<script>
function absensiRekap() {
    return {
        data: {},
        loaded: false,
        lastChecked: '',
        autoRefresh: null,
        async load() {
            try {
                const res = await fetch('{{ route('superadmin.absensi.rekap.status') }}');
                this.data = await res.json();
                this.loaded = true;
                this.lastChecked = 'Terakhir dicek: ' + new Date(this.data.checked_at).toLocaleTimeString('id-ID');
            } catch (e) {
                this.lastChecked = 'Gagal memuat data terbaru';
            }
        },
    };
}
</script>
@endsection