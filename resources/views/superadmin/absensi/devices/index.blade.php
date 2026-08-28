@extends('superadmin.layouts.app')

@section('title', 'Device Absensi')
@section('page-title', 'Device Absensi')

@section('content')

@if (session('generated_key'))
<div class="sa-card p-5 mb-6" style="border-color: rgba(245,158,11,0.35); background: rgba(245,158,11,0.06);">
    <p class="text-sm font-bold mb-2" style="color:#fbbf24;">
        API Key untuk "{{ session('generated_device_code') }}" — catat sekarang!
    </p>
    <p class="text-xs mb-3" style="color:#fbbf24;">
        Key ini cuma ditampilkan SEKALI. Setelah tinggalkan halaman ini, tidak bisa dilihat ulang —
        harus regenerate kalau hilang. Salin ke konfigurasi device sekarang juga.
    </p>
    <code class="block px-4 py-3 rounded-lg text-sm font-mono break-all" style="background: #0f172a; border: 1px solid rgba(245,158,11,0.35); color:#fbbf24;">{{ session('generated_key') }}</code>
</div>
@endif

@if (session('success'))
<div class="sa-card p-4 mb-6" style="border-color: rgba(16,185,129,0.3);">
    <p class="text-sm" style="color:#6ee7b7;">{{ session('success') }}</p>
</div>
@endif

@if (session('error'))
<div class="sa-card p-4 mb-6" style="border-color: rgba(239,68,68,0.3);">
    <p class="text-sm" style="color:#fca5a5;">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Device Absensi</h1>
        <p class="text-sm" style="color:#64748b;">{{ $devices->total() }} device terdaftar, lintas semua sekolah</p>
    </div>
    <a href="{{ route('superadmin.absensi.devices.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
       style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
        + Tambah Device
    </a>
</div>

{{-- ── Search & Filter ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('superadmin.absensi.devices.index') }}" class="sa-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}"
           placeholder="Cari nama atau kode device..."
           class="form-input flex-1">
    <select name="school" class="form-input sm:w-56">
        <option value="">Semua Sekolah</option>
        @foreach ($schools as $id => $name)
            <option value="{{ $id }}" {{ $schoolFilter === $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: rgba(99,102,241,0.2);">
        Cari
    </button>
    @if ($search || $schoolFilter)
    <a href="{{ route('superadmin.absensi.devices.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color:#64748b;">
        Reset
    </a>
    @endif
</form>

<div class="sa-card overflow-hidden">
    @if ($devices->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">
            {{ $search || $schoolFilter ? 'Tidak ada device yang cocok dengan pencarian.' : 'Belum ada device terdaftar.' }}
        </p>
    </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Device</th>
                <th>Sekolah</th>
                <th>Metode</th>
                <th>Lokasi</th>
                <th>Terakhir Aktif</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devices as $device)
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                            {{ strtoupper(substr($device->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white leading-tight">{{ $device->name }}</p>
                            <p class="text-xs font-mono" style="color:#475569;">{{ $device->device_code }}</p>
                        </div>
                    </div>
                </td>
                <td><p class="text-xs" style="color:#94a3b8;">{{ $schools[$device->school_id] ?? '—' }}</p></td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        @forelse (($device->capabilities ?? []) as $cap)
                            <span class="badge badge-indigo">{{ \App\Models\Absensi\Device::CAPABILITIES[$cap] ?? $cap }}</span>
                        @empty
                            <span class="text-xs" style="color:#475569;">—</span>
                        @endforelse
                    </div>
                </td>
                <td><p class="text-xs" style="color:#94a3b8;">{{ $device->location ?? '—' }}</p></td>
                <td><p class="text-xs" style="color:#94a3b8;">{{ $device->last_seen_at?->diffForHumans() ?? 'Belum pernah' }}</p></td>
                <td>
                    <span class="badge {{ $device->is_active ? 'badge-green' : 'badge-slate' }}">
                        {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('superadmin.absensi.devices.edit', $device) }}" class="text-xs font-semibold" style="color:#818cf8;">Edit</a>
                        <form method="POST" action="{{ route('superadmin.absensi.devices.regenerate-key', $device) }}"
                              onsubmit="return confirm('Regenerate API key untuk {{ $device->name }}? Key lama otomatis tidak berlaku.');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold" style="color:#fbbf24;">Regenerate Key</button>
                        </form>
                        <form method="POST" action="{{ route('superadmin.absensi.devices.destroy', $device) }}"
                              onsubmit="return confirm('Hapus device {{ $device->name }}? Tindakan ini tidak bisa dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold" style="color:#fca5a5;">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="px-5 py-4 border-t" style="border-color: var(--sa-border);">
        {{ $devices->links() }}
    </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 10px;
        background: var(--sa-surface-2);
        border: 1px solid var(--sa-border);
        color: #e2e8f0;
        font-size: 13.5px;
    }
    .form-input:focus { outline: none; border-color: #6366f1; }
</style>
@endpush