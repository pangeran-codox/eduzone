@extends('superadmin.layouts.app')

@section('title', 'Tambah Device')
@section('page-title', 'Tambah Device')

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

    <form method="POST" action="{{ route('superadmin.absensi.devices.store') }}" class="space-y-8">
        @csrf

        <div>
            <p class="section-label">Sekolah</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="field-label">Sekolah</label>
                    <select name="school_id" required class="form-input">
                        <option value="">— Pilih sekolah —</option>
                        @foreach ($schools as $id => $name)
                            <option value="{{ $id }}" {{ old('school_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="field-hint mt-1">Cuma sekolah yang sudah tersinkron ke layanan absensi yang muncul di sini.</p>
                </div>
            </div>
        </div>

        <div>
            <p class="section-label">Identitas Device</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Kode Device</label>
                    <input type="text" name="device_code" value="{{ old('device_code') }}" required maxlength="50" placeholder="mis. GATE-01" class="form-input">
                    <p class="field-hint mt-1">Dipakai di URL kiosk (/kiosk/{kode}) — tidak bisa diubah setelah dibuat.</p>
                </div>
                <div>
                    <label class="field-label">Nama Device</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="mis. Gerbang Utama" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Lokasi <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="location" value="{{ old('location') }}" maxlength="255" placeholder="mis. Lobby Gedung A" class="form-input">
                </div>
                <div>
                    <label class="field-label">Alamat IP <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="ip_address" value="{{ old('ip_address') }}" placeholder="mis. 192.168.1.50" class="form-input">
                </div>
            </div>
        </div>

        <div>
            <p class="section-label">Metode Input</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach ($capabilityOptions as $value => $label)
                    <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl cursor-pointer" style="background: var(--sa-surface-2); border: 1px solid var(--sa-border);">
                        <input type="checkbox" name="capabilities[]" value="{{ $value }}" {{ in_array($value, old('capabilities', [])) ? 'checked' : '' }} class="w-4 h-4">
                        <span class="text-sm" style="color:#e2e8f0;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="field-hint mt-2">Bisa pilih lebih dari 1 — device fisik sering support beberapa metode sekaligus.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
                Simpan Device
            </button>
            <a href="{{ route('superadmin.absensi.devices.index') }}" class="text-sm" style="color:#64748b;">Batal</a>
        </div>

        <p class="text-xs" style="color:#475569;">API key otomatis dibuat setelah disimpan, dan ditampilkan sekali di halaman berikutnya.</p>
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
    .form-input::placeholder { color: #475569; }
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