@extends('superadmin.layouts.app')

@section('title', 'Edit Device')
@section('page-title', 'Edit Device — ' . $device->name)

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

    <form method="POST" action="{{ route('superadmin.absensi.devices.update', $device) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <div>
            <p class="section-label">Sekolah</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="field-label">Sekolah</label>
                    <select name="school_id" required class="form-input">
                        @foreach ($schools as $id => $name)
                            <option value="{{ $id }}" {{ old('school_id', $device->school_id) === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="field-hint mt-1">⚠️ Mengganti sekolah device yang sudah punya riwayat absensi berisiko mencampur data lintas sekolah. Ubah hanya kalau memang perlu.</p>
                </div>
            </div>
        </div>

        <div>
            <p class="section-label">Identitas Device</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Kode Device</label>
                    <input type="text" value="{{ $device->device_code }}" disabled class="form-input" style="opacity:0.4; cursor: not-allowed;">
                    <p class="field-hint mt-1">Kode device tidak bisa diubah setelah dibuat.</p>
                </div>
                <div>
                    <label class="field-label">Nama Device</label>
                    <input type="text" name="name" value="{{ old('name', $device->name) }}" required maxlength="255" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Lokasi <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="location" value="{{ old('location', $device->location) }}" maxlength="255" class="form-input">
                </div>
                <div>
                    <label class="field-label">Alamat IP <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}" class="form-input">
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <select name="is_active" required class="form-input">
                        <option value="1" {{ old('is_active', (int) $device->is_active) === 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active', (int) $device->is_active) === 0 ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <p class="section-label">Metode Input</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach ($capabilityOptions as $value => $label)
                    <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl cursor-pointer" style="background: var(--sa-surface-2); border: 1px solid var(--sa-border);">
                        <input type="checkbox" name="capabilities[]" value="{{ $value }}" {{ in_array($value, old('capabilities', $device->capabilities ?? [])) ? 'checked' : '' }} class="w-4 h-4">
                        <span class="text-sm" style="color:#e2e8f0;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="field-hint mt-2">Bisa pilih lebih dari 1 — device fisik sering support beberapa metode sekaligus.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
                Simpan Perubahan
            </button>
            <a href="{{ route('superadmin.absensi.devices.index') }}" class="text-sm" style="color:#64748b;">Batal</a>
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
    .form-input:focus { outline: none; border-color: #6366f1; }
    .section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6366f1;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--sa-border);
    }
    .field-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 6px;
    }
    .field-hint {
        font-size: 11px;
        font-weight: 400;
        color: #475569;
    }
</style>
@endpush