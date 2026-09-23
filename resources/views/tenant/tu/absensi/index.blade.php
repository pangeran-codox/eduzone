@extends('tenant.layouts.app')

@section('title', 'Dashboard Absensi')
@section('page-title', 'Dashboard Absensi Hari Ini')

@section('content')
<div
    x-data="{
        date: @js($date),
        personType: @js($personType ?? ''),
        loading: false,
        d: @js($initial),
        timer: null,

        statusBadge(status) {
            return {
                Hadir:     'badge badge-green',
                Terlambat: 'badge badge-amber',
                Sakit:     'badge badge-slate',
                Izin:      'badge',
                Alpa:      'badge badge-red',
            }[status] ?? 'badge badge-slate';
        },

        personTypeLabel(type) {
            return { student: 'Siswa', teacher: 'Guru', staff: 'Staff' }[type] ?? type;
        },

        async refresh() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ date: this.date, person_type: this.personType });
                const res = await fetch(`{{ route('tu.absensi.data') }}?${params.toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) this.d = await res.json();
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
    class="space-y-5"
>

    {{-- ── Filter bar ──────────────────────────────────────────────────── --}}
    <div class="t-card flex flex-wrap items-end gap-4 px-5 py-4">
        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Tanggal</label>
            <input type="date" x-model="date" @change="refresh()"
                   class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color:var(--t-border); focus:ring-color:var(--t-gold);">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Filter Tipe</label>
            <select x-model="personType" @change="refresh()"
                    class="border rounded-lg px-3 py-2 text-sm focus:outline-none"
                    style="border-color:var(--t-border);">
                <option value="">Semua</option>
                <option value="student">Siswa</option>
                <option value="teacher">Guru</option>
                <option value="staff">Staff</option>
            </select>
        </div>
        <div class="ml-auto flex items-center gap-3 text-xs" style="color:var(--t-muted);">
            <span x-show="loading" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Memperbarui…
            </span>
            <span>Diperbarui: <span x-text="d.generated_at" class="font-medium"></span></span>
        </div>
    </div>

    {{-- ── Stat cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">

        {{-- Total --}}
        <div class="stat-card flex flex-col gap-1 col-span-1">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-muted);">Total</span>
            <span class="text-3xl font-bold" style="color:var(--t-dark);" x-text="d.total_people"></span>
            <span class="text-xs" style="color:var(--t-muted);">Orang</span>
        </div>

        {{-- Hadir --}}
        <div class="stat-card flex flex-col gap-1" style="background:var(--t-green-bg); border-color:rgba(59,109,17,0.15);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-green-tx);">Hadir</span>
            <span class="text-3xl font-bold" style="color:var(--t-green-tx);" x-text="d.status_counts['Hadir'] ?? 0"></span>
        </div>

        {{-- Terlambat --}}
        <div class="stat-card flex flex-col gap-1" style="background:var(--t-amber-bg); border-color:rgba(146,98,10,0.15);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-amber-tx);">Terlambat</span>
            <span class="text-3xl font-bold" style="color:var(--t-amber-tx);" x-text="d.status_counts['Terlambat'] ?? 0"></span>
        </div>

        {{-- Sakit --}}
        <div class="stat-card flex flex-col gap-1" style="background:var(--t-slate-bg); border-color:rgba(107,107,99,0.15);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-slate-tx);">Sakit</span>
            <span class="text-3xl font-bold" style="color:var(--t-slate-tx);" x-text="d.status_counts['Sakit'] ?? 0"></span>
        </div>

        {{-- Izin --}}
        <div class="stat-card flex flex-col gap-1" style="background:rgba(201,162,39,0.1); border-color:rgba(201,162,39,0.2);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:#8A6D1B;">Izin</span>
            <span class="text-3xl font-bold" style="color:#8A6D1B;" x-text="d.status_counts['Izin'] ?? 0"></span>
        </div>

        {{-- Alpa --}}
        <div class="stat-card flex flex-col gap-1" style="background:var(--t-red-bg); border-color:rgba(163,45,45,0.15);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-red-tx);">Alpa</span>
            <span class="text-3xl font-bold" style="color:var(--t-red-tx);" x-text="d.status_counts['Alpa'] ?? 0"></span>
        </div>

        {{-- Belum Absen --}}
        <div class="stat-card flex flex-col gap-1" style="background:#F0EDE3; border-color:var(--t-border);">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--t-muted);">Belum Absen</span>
            <span class="text-3xl font-bold" style="color:var(--t-text);" x-text="d.not_checked_in_count"></span>
        </div>

    </div>

    {{-- ── Tabel bawah: check-in terbaru + belum absen ─────────────────── --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Check-in terbaru --}}
        <div class="t-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b" style="border-color:var(--t-border);">
                <span class="font-semibold text-sm" style="color:var(--t-dark);">Check-in Terbaru</span>
                <span class="badge badge-slate" x-text="'20 terakhir'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="t-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Jam Masuk</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in d.recent" :key="row.person_type + row.person_id">
                            <tr>
                                <td>
                                    <span class="font-medium" x-text="row.full_name"></span>
                                    <span x-show="row.grade" class="ml-1 text-xs" style="color:var(--t-muted);" x-text="row.grade"></span>
                                </td>
                                <td>
                                    <span class="badge badge-slate text-xs" x-text="personTypeLabel(row.person_type)"></span>
                                </td>
                                <td class="font-mono text-sm" x-text="row.first_check_in ?? '—'"></td>
                                <td>
                                    <span :class="statusBadge(row.status)" x-text="row.status"></span>
                                    <span x-show="row.has_anomaly" class="ml-1 text-xs" style="color:var(--t-red-tx);" title="Terdeteksi anomali">⚠</span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="d.recent.length === 0">
                            <td colspan="4" class="py-10 text-center text-sm" style="color:var(--t-muted);">
                                Belum ada check-in hari ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Belum absen --}}
        <div class="t-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b" style="border-color:var(--t-border);">
                <span class="font-semibold text-sm" style="color:var(--t-dark);">Belum Absen</span>
                <span class="badge badge-red" x-text="d.not_checked_in_count + ' orang'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="t-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Kelas / Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in d.not_checked_in" :key="row.person_type + row.person_id">
                            <tr>
                                <td class="font-medium" x-text="row.full_name"></td>
                                <td>
                                    <span class="badge badge-slate text-xs" x-text="personTypeLabel(row.person_type)"></span>
                                </td>
                                <td style="color:var(--t-muted);" x-text="row.grade ?? '—'"></td>
                            </tr>
                        </template>
                        <tr x-show="d.not_checked_in.length === 0">
                            <td colspan="3" class="py-10 text-center text-sm" style="color:var(--t-green-tx);">
                                🎉 Semua sudah absen!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div x-show="d.not_checked_in_count > 50"
                 class="px-5 py-2.5 text-xs border-t" style="color:var(--t-muted); border-color:var(--t-border);">
                Menampilkan 50 dari <span x-text="d.not_checked_in_count" class="font-semibold"></span> orang yang belum absen.
            </div>
        </div>

    </div>
</div>
@endsection
