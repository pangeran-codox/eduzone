@extends('superadmin.layouts.app')

@section('title', 'Tambah Sekolah')
@section('page-title', 'Tambah Sekolah')

@section('content')

<div class="sa-card p-6 max-w-3xl">

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

    <form method="POST" action="{{ route('superadmin.schools.store') }}" class="space-y-8">
        @csrf

        {{-- ── Identitas ────────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Identitas Sekolah</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="field-label">Nama Sekolah</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="form-input">
                </div>
                <div>
                    <label class="field-label">Slug <span class="field-hint">(dipakai di URL, kosongkan untuk auto-generate)</span></label>
                    <input type="text" name="slug" value="{{ old('slug') }}" maxlength="255" placeholder="sma-negeri-1" class="form-input">
                </div>
                <div>
                    <label class="field-label">NPSN <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="npsn" value="{{ old('npsn') }}" maxlength="20" class="form-input">
                </div>
                <div>
                    <label class="field-label">NSS <span class="field-hint">(opsional)</span></label>
                    <input type="text" name="nss" value="{{ old('nss') }}" maxlength="20" class="form-input">
                </div>
                <div>
                    <label class="field-label">Jenjang</label>
                    <select name="level" class="form-input">
                        <option value="">— Pilih —</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level }}" {{ old('level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="">— Pilih —</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ old('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Akreditasi</label>
                    <select name="accreditation" class="form-input">
                        <option value="">— Pilih —</option>
                        @foreach ($accreditations as $acc)
                            <option value="{{ $acc }}" {{ old('accreditation') === $acc ? 'selected' : '' }}>{{ $acc }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Alamat ───────────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Alamat</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="field-label">Alamat Lengkap</label>
                    <textarea name="address" rows="2" class="form-input">{{ old('address') }}</textarea>
                </div>
                <div><label class="field-label">Desa/Kelurahan</label><input type="text" name="village" value="{{ old('village') }}" class="form-input"></div>
                <div><label class="field-label">Kecamatan</label><input type="text" name="district" value="{{ old('district') }}" class="form-input"></div>
                <div><label class="field-label">Kota/Kabupaten</label><input type="text" name="city" value="{{ old('city') }}" class="form-input"></div>
                <div><label class="field-label">Provinsi</label><input type="text" name="province" value="{{ old('province') }}" class="form-input"></div>
                <div><label class="field-label">Kode Pos</label><input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="10" class="form-input"></div>
            </div>
        </div>

        {{-- ── Kontak ───────────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Kontak</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="field-label">Telepon</label><input type="text" name="phone" value="{{ old('phone') }}" class="form-input"></div>
                <div><label class="field-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-input"></div>
                <div class="sm:col-span-2"><label class="field-label">Website</label><input type="url" name="website" value="{{ old('website') }}" placeholder="https://" class="form-input"></div>
            </div>
        </div>

        {{-- ── Kepemimpinan ─────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Kepemimpinan</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Nama Kepala Sekolah</label>
                    <input type="text" name="principal_name" value="{{ old('principal_name') }}" class="form-input">
                </div>
                <div>
                    <label class="field-label">NIP Kepala Sekolah</label>
                    <input type="text" disabled placeholder="Menunggu mesin enkripsi aktif" class="form-input" style="opacity:0.4; cursor: not-allowed;">
                    <p class="field-hint mt-1">🔒 Data sensitif — form ini aktif lagi setelah service enkripsi (gRPC) siap.</p>
                </div>
            </div>
        </div>

        {{-- ── Visi Misi ────────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Visi, Misi & Motto <span class="field-hint">(opsional)</span></p>
            <div class="space-y-4">
                <div><label class="field-label">Visi</label><textarea name="vision" rows="2" class="form-input">{{ old('vision') }}</textarea></div>
                <div><label class="field-label">Misi</label><textarea name="mission" rows="2" class="form-input">{{ old('mission') }}</textarea></div>
                <div><label class="field-label">Motto</label><input type="text" name="motto" value="{{ old('motto') }}" class="form-input"></div>
            </div>
        </div>

        {{-- ── Rekening (terkunci) ──────────────────────────────────── --}}
        <div>
            <p class="section-label">Rekening Bank</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Nama Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-input">
                </div>
                <div>
                    <label class="field-label">Nomor & Nama Rekening</label>
                    <input type="text" disabled placeholder="Menunggu mesin enkripsi aktif" class="form-input" style="opacity:0.4; cursor: not-allowed;">
                    <p class="field-hint mt-1">🔒 Data sensitif — form ini aktif lagi setelah service enkripsi (gRPC) siap.</p>
                </div>
            </div>
        </div>

        {{-- ── Langganan ────────────────────────────────────────────── --}}
        <div>
            <p class="section-label">Langganan</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="field-label">Paket</label>
                    <select name="subscription_plan" required class="form-input">
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" {{ old('subscription_plan', 'trial') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Berlaku Sampai <span class="field-hint">(opsional)</span></label>
                    <input type="date" name="subscription_until" value="{{ old('subscription_until') }}" class="form-input">
                </div>
                <div>
                    <label class="field-label">Maks. Jumlah User</label>
                    <input type="number" name="max_users" value="{{ old('max_users', 100) }}" required min="1" class="form-input">
                </div>
                <div class="sm:col-span-3">
                    <label class="field-label">Status</label>
                    <select name="is_active" required class="form-input">
                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
                Simpan Sekolah
            </button>
            <a href="{{ route('superadmin.schools.index') }}" class="text-sm" style="color:#64748b;">Batal</a>
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
    .form-input::placeholder { color: #475569; }
    textarea.form-input { resize: vertical; }
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
