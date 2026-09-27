@php $staff = $staff ?? null; @endphp

{{-- ── Data Pokok ────────────────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pokok</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="field-label">Foto <span class="field-hint">(opsional, JPG/PNG maks 2MB)</span></label>
            @if ($staff?->photo_access_token)
                <div class="mb-2">
                    <img src="{{ route('media.person-photo', ['token' => $staff->photo_access_token]) }}"
                         alt="Foto {{ $staff->full_name }}"
                         class="w-20 h-20 rounded-lg object-cover border"
                         style="border-color: var(--t-border);">
                </div>
            @endif
            <input type="file" name="photo" accept="image/jpeg,image/png" class="form-input">
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name', $staff?->full_name ?? '') }}" required maxlength="255" class="form-input">
        </div>
        <div>
            <label class="field-label">Email <span class="field-hint">(opsional, buat notifikasi)</span></label>
            <input type="email" name="email" value="{{ old('email', $staff?->email ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Jenis Kelamin</label>
            <select name="gender" required class="form-input">
                <option value="">— Pilih —</option>
                @foreach ($genders as $value => $label)
                    <option value="{{ $value }}" {{ old('gender', $staff?->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Posisi/Jabatan <span class="field-hint">(mis. Petugas Perpustakaan)</span></label>
            <input type="text" name="position" value="{{ old('position', $staff?->position ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Tanggal Masuk</label>
            <input type="date" name="joined_date" value="{{ old('joined_date', $staff?->joined_date?->format('Y-m-d') ?? '') }}" class="form-input">
        </div>
        <div class="flex items-center gap-2 pt-6">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $staff?->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="field-label mb-0">Aktif</label>
        </div>
    </div>
</div>

{{-- ── Data Pribadi (Sensitif) ────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pribadi <span class="field-hint" style="text-transform:none; font-weight:400;">🔒 Terenkripsi</span></p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="field-label">NIP</label><input type="text" name="nip" value="{{ old('nip', $staff?->nip ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">Tempat Lahir</label><input type="text" name="birth_place" value="{{ old('birth_place', $staff?->birth_place ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $staff?->birth_date ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Agama</label><input type="text" name="religion" value="{{ old('religion', $staff?->religion ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">No. Telepon</label><input type="text" name="phone" value="{{ old('phone', $staff?->phone ?? '') }}" maxlength="30" class="form-input"></div>
        <div class="sm:col-span-2"><label class="field-label">Alamat</label><textarea name="address" rows="2" class="form-input">{{ old('address', $staff?->address ?? '') }}</textarea></div>
    </div>
</div>