@extends('superadmin.layouts.app')

@section('title', 'Kelola Device Absensi')
@section('page-title', 'Kelola Device Absensi')

@section('content')

{{-- ── Banner device key baru (one-time, tidak bisa dilihat lagi setelah ini) ── --}}
@if (session('generated_key'))
<div
    x-data="{ copied: false, key: '{{ session('generated_key') }}' }"
    class="sa-card p-5 mb-6"
    style="border-color: rgba(16,185,129,0.35); background: rgba(16,185,129,0.06);"
>
    <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(16,185,129,0.15);">
            <svg style="width:18px;height:18px;color:#6ee7b7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white mb-1">
                Device Key untuk <span style="color:#6ee7b7;">{{ session('generated_device_code') }}</span> berhasil dibuat
            </p>
            <p class="text-xs mb-3" style="color:#94a3b8;">
                Simpan/copy sekarang — key ini <strong>tidak akan ditampilkan lagi</strong> setelah halaman ini ditinggalkan.
                Masukkan key ini di form "Setup Device" pas pertama kali buka halaman kiosk di device fisiknya.
            </p>
            <div class="flex items-center gap-2">
                <code
                    class="flex-1 px-3 py-2 rounded-lg text-sm"
                    style="background: var(--sa-surface-2); border: 1px solid var(--sa-border); color: #6ee7b7; font-family: monospace;"
                    x-text="key"
                ></code>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText(key); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-3 py-2 rounded-lg text-xs font-semibold flex-shrink-0"
                    style="background: rgba(16,185,129,0.15); color: #6ee7b7;"
                >
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied">Tersalin ✓</span>
                </button>
            </div>
        </div>
    </div>
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

{{-- ── Header ───────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Kelola Device Absensi</h1>
        <p class="text-sm" style="color:#64748b;">{{ $devices->count() }} device terdaftar di semua sekolah</p>
    </div>
    <a
        href="{{ route('superadmin.absensi.devices.create') }}"
        class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
        style="background: linear-gradient(135deg,#4f46e5,#7c3aed);"
    >
        + Tambah Device
    </a>
</div>

{{-- ── Tabel Device ─────────────────────────────────────────────────── --}}
<div class="sa-card overflow-hidden">
    @if ($devices->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Belum ada device terdaftar.</p>
    </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Device</th>
                <th>Sekolah</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Terakhir Aktif</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devices as $device)
            @php
                $isOnline = $device->last_seen_at && $device->last_seen_at->gt(now()->subMinutes(5));
            @endphp
            <tr>
                <td>
                    <p class="text-xs font-semibold text-white">{{ $device->name }}</p>
                    <p class="text-xs" style="color:#475569;">{{ $device->device_code }}</p>
                </td>
                <td>
                    <p class="text-xs" style="color:#94a3b8;">{{ $schools[$device->school_id] ?? '—' }}</p>
                </td>
                <td>
                    <span class="badge badge-indigo">{{ $deviceTypes[$device->device_type] ?? $device->device_type }}</span>
                </td>
                <td>
                    @if (! $device->is_active)
                        <span class="badge badge-slate">Nonaktif</span>
                    @elseif ($isOnline)
                        <span class="badge badge-green">● Online</span>
                    @else
                        <span class="badge badge-amber">Offline</span>
                    @endif
                </td>
                <td>
                    <p class="text-xs" style="color:#64748b;">
                        {{ $device->last_seen_at?->diffForHumans() ?? 'Belum pernah' }}
                    </p>
                </td>
                <td>
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('superadmin.absensi.devices.edit', $device) }}"
                           class="text-xs font-semibold" style="color:#818cf8;">
                            Edit
                        </a>

                        <form method="POST" action="{{ route('superadmin.absensi.devices.regenerate-key', $device) }}"
                              onsubmit="return confirm('Generate ulang device key untuk {{ $device->name }}? Key lama langsung tidak berlaku.');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold" style="color:#fcd34d;">
                                Reset Key
                            </button>
                        </form>

                        <form method="POST" action="{{ route('superadmin.absensi.devices.destroy', $device) }}"
                              onsubmit="return confirm('Hapus device {{ $device->name }}? Tindakan ini tidak bisa dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold" style="color:#fca5a5;">
                                Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
