@extends('superadmin.layouts.app')

@section('title', 'Tambah Device')
@section('page-title', 'Tambah Device Absensi')

@section('content')

<div class="sa-card p-6 max-w-2xl">

    @if ($errors->any())
    <div class="mb-5 p-4 rounded-xl" style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25);">
        <p class="text-sm font-semibold mb-1" style="color:#fca5a5;">Ada input yang perlu diperbaiki:</p>
        <ul class="text-xs list-disc list-inside" style="color:#fca5a5;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('superadmin.absensi.devices.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">Sekolah</label>
            <select name="school_id" required class="form-input">
                <option value="">— Pilih sekolah —</option>
                @foreach ($schools as $id => $name)
                    <option value="{{ $id }}" {{ old('school_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">Kode Device</label>
            <input type="text" name="device_code" value="{{ old('device_code') }}" required maxlength="50"
                   placeholder="mis. GATE-01" class="form-input">
            <p class="text-xs mt-1" style="color:#475569;">
                Dipakai di URL kiosk (/kiosk/{kode-ini}) — pastikan unik, tidak bisa diubah setelah dibuat.
            </p>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">Nama Device</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                   placeholder="mis. Gerbang 1" class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">Tipe Device</label>
            <select name="device_type" required class="form-input">
                <option value="">— Pilih tipe —</option>
                @foreach ($deviceTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('device_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">Lokasi <span style="color:#475569;">(opsional)</span></label>
            <input type="text" name="location" value="{{ old('location') }}" maxlength="255"
                   placeholder="mis. Gerbang Utama, Lantai 1" class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">
                ID Kelas Default <span style="color:#475569;">(opsional, UUID)</span>
            </label>
            <input type="text" name="default_class_id" value="{{ old('default_class_id') }}"
                   placeholder="Kosongkan kalau device tidak terikat kelas tertentu" class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5" style="color:#94a3b8;">IP Address <span style="color:#475569;">(opsional)</span></label>
            <input type="text" name="ip_address" value="{{ old('ip_address') }}"
                   placeholder="mis. 192.168.1.50" class="form-input">
        </div>

        <div class="p-4 rounded-xl" style="background: rgba(99,102,241,0.06); border: 1px solid var(--sa-border);">
            <p class="text-xs" style="color:#94a3b8;">
                Device key akan digenerate otomatis setelah disimpan, dan cuma ditampilkan
                <strong class="text-white">satu kali</strong> — pastikan siap untuk langsung menyalinnya.
            </p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
                Simpan & Generate Key
            </button>
            <a href="{{ route('superadmin.absensi.devices.index') }}"
               class="text-sm" style="color:#64748b;">
                Batal
            </a>
        </div>
    </form>
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
    .form-input:focus {
        outline: none;
        border-color: #6366f1;
    }
    .form-input::placeholder {
        color: #475569;
    }
</style>
@endpush
