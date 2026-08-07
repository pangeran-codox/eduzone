@extends('superadmin.layouts.app')

@section('title', 'Status Absensi')
@section('page-title', 'Status Layanan Absensi')

@section('content')

<div x-data="absensiHealth()" x-init="load(); autoRefresh = setInterval(load, 30000)">

{{-- ── Header ───────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Status Layanan Absensi</h1>
        <p class="text-sm" style="color:#64748b;">
            Pantau kesiapan gateway, database, dan device kiosk di semua sekolah
        </p>
    </div>
    <p class="text-xs" style="color:#475569;" x-text="lastChecked"></p>
</div>

{{-- ── Stat Cards: Gateway & Database ──────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

    {{-- Gateway --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 :style="loaded ? (data.gateway?.ok ? 'background:rgba(16,185,129,0.12);' : 'background:rgba(239,68,68,0.12);') : 'background:rgba(100,116,139,0.12);'">
                <svg style="width:18px;height:18px;" :style="loaded ? (data.gateway?.ok ? 'color:#6ee7b7;' : 'color:#fca5a5;') : 'color:#64748b;'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                </svg>
            </div>
            <span class="badge" :class="!loaded ? 'badge-slate' : (data.gateway?.ok ? 'badge-green' : 'badge-red')">
                <span x-text="!loaded ? 'Memeriksa...' : (data.gateway?.ok ? '● Terhubung' : '✕ Bermasalah')"></span>
            </span>
        </div>
        <p class="text-2xl font-extrabold text-white mb-0.5">
            <span x-show="loaded && data.gateway?.ok" x-text="data.gateway?.latency_ms + ' ms'"></span>
            <span x-show="loaded && !data.gateway?.ok" x-text="data.gateway?.error ?? 'Tidak diketahui'"></span>
            <span x-show="!loaded">—</span>
        </p>
        <p class="text-xs" style="color:#64748b;">Gateway (absensi-gateway)</p>
    </div>

    {{-- Database --}}
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 :style="loaded ? (data.database?.ok ? 'background:rgba(16,185,129,0.12);' : 'background:rgba(239,68,68,0.12);') : 'background:rgba(100,116,139,0.12);'">
                <svg style="width:18px;height:18px;" :style="loaded ? (data.database?.ok ? 'color:#6ee7b7;' : 'color:#fca5a5;') : 'color:#64748b;'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                </svg>
            </div>
            <span class="badge" :class="!loaded ? 'badge-slate' : (data.database?.ok ? 'badge-green' : 'badge-red')">
                <span x-text="!loaded ? 'Memeriksa...' : (data.database?.ok ? '● Terhubung' : '✕ Bermasalah')"></span>
            </span>
        </div>
        <p class="text-2xl font-extrabold text-white mb-0.5">
            <span x-text="loaded ? (data.database?.ok ? 'Normal' : (data.database?.error ?? 'Tidak diketahui')) : '—'"></span>
        </p>
        <p class="text-xs" style="color:#64748b;">Database eduzone_absensi</p>
    </div>
</div>

{{-- ── Tabel Per Sekolah ────────────────────────────────────────────── --}}
<div class="sa-card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--sa-border);">
        <p class="text-sm font-semibold text-white">Status Per Sekolah</p>
        <p class="text-xs" style="color:#64748b;" x-text="(data.schools?.length ?? 0) + ' sekolah'"></p>
    </div>

    <div x-show="!loaded" class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Memuat status...</p>
    </div>

    <div x-show="loaded && (!data.schools || data.schools.length === 0)" class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Belum ada sekolah tersinkron ke layanan absensi</p>
    </div>

    <table class="sa-table" x-show="loaded && data.schools && data.schools.length > 0">
        <thead>
            <tr>
                <th>Sekolah</th>
                <th>Sinkronisasi Data</th>
                <th>Device Aktif</th>
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
                        <span class="badge" :class="school.sync_fresh ? 'badge-green' : 'badge-red'">
                            <span x-text="school.sync_fresh ? 'Terbaru' : 'Basi'"></span>
                        </span>
                    </td>
                    <td>
                        <span class="text-xs" style="color:#94a3b8;">
                            <span x-text="school.devices_online"></span> / <span x-text="school.devices_total"></span> online
                        </span>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

</div>

<script>
function absensiHealth() {
    return {
        data: {},
        loaded: false,
        lastChecked: '',
        autoRefresh: null,
        async load() {
            try {
                const res = await fetch('{{ route('superadmin.absensi.health.status') }}');
                this.data = await res.json();
                this.loaded = true;
                this.lastChecked = 'Terakhir dicek: ' + new Date(this.data.checked_at).toLocaleTimeString('id-ID');
            } catch (e) {
                this.lastChecked = 'Gagal memuat status terbaru';
            }
        },
    };
}
</script>

@endsection