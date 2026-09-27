@php $teacher = $teacher ?? null; @endphp

{{-- ── Data Pokok ────────────────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pokok</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="field-label">Foto <span class="field-hint">(opsional, JPG/PNG maks 2MB)</span></label>
            @if ($teacher?->photo_access_token)
                <div class="mb-2">
                    <img src="{{ route('media.person-photo', ['token' => $teacher->photo_access_token]) }}"
                         alt="Foto {{ $teacher->full_name }}"
                         class="w-20 h-20 rounded-lg object-cover border"
                         style="border-color: var(--t-border);">
                </div>
            @endif
            <input type="file" name="photo" accept="image/jpeg,image/png" class="form-input">
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name', $teacher?->full_name ?? '') }}" required maxlength="255" class="form-input">
        </div>
        <div>
            <label class="field-label">Email <span class="field-hint">(opsional, buat notifikasi)</span></label>
            <input type="email" name="email" value="{{ old('email', $teacher?->email ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Jenis Kelamin</label>
            <select name="gender" required class="form-input">
                <option value="">— Pilih —</option>
                @foreach ($genders as $value => $label)
                    <option value="{{ $value }}" {{ old('gender', $teacher?->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Pendidikan Terakhir <span class="field-hint">(mis. S1, S2)</span></label>
            <input type="text" name="last_education" value="{{ old('last_education', $teacher?->last_education ?? '') }}" maxlength="50" class="form-input">
        </div>
        <div>
            <label class="field-label">Jurusan Pendidikan</label>
            <input type="text" name="education_major" value="{{ old('education_major', $teacher?->education_major ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Status Kepegawaian</label>
            <select name="employment_status" class="form-input">
                <option value="">— Pilih —</option>
                @foreach ($employmentStatuses as $status)
                    <option value="{{ $status }}" {{ old('employment_status', $teacher?->employment_status ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Mata Pelajaran / Jurusan</label>
            <select name="major_id" class="form-input">
                <option value="">— Tidak ada —</option>
                @foreach ($majors as $major)
                    <option value="{{ $major->id }}" {{ old('major_id', $teacher?->major_id ?? '') === $major->id ? 'selected' : '' }}>
                        {{ $major->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Tanggal Masuk</label>
            <input type="date" name="joined_date" value="{{ old('joined_date', $teacher?->joined_date?->format('Y-m-d') ?? '') }}" class="form-input">
        </div>
        <div class="flex items-center gap-2 pt-6">
            <input type="hidden" name="is_homeroom" value="0">
            <input type="checkbox" id="is_homeroom" name="is_homeroom" value="1"
                   {{ old('is_homeroom', $teacher?->is_homeroom ?? false) ? 'checked' : '' }}>
            <label for="is_homeroom" class="field-label mb-0">Wali Kelas</label>
        </div>
        <div class="flex items-center gap-2 pt-6">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $teacher?->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="field-label mb-0">Aktif</label>
        </div>
    </div>
</div>

{{-- ── Data Pribadi (Sensitif) ────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pribadi <span class="field-hint" style="text-transform:none; font-weight:400;">🔒 Terenkripsi</span></p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="field-label">NIP</label><input type="text" name="nip" value="{{ old('nip', $teacher?->nip ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">NUPTK</label><input type="text" name="nuptk" value="{{ old('nuptk', $teacher?->nuptk ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">Tempat Lahir</label><input type="text" name="birth_place" value="{{ old('birth_place', $teacher?->birth_place ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $teacher?->birth_date ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Agama</label><input type="text" name="religion" value="{{ old('religion', $teacher?->religion ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">No. Telepon</label><input type="text" name="phone" value="{{ old('phone', $teacher?->phone ?? '') }}" maxlength="30" class="form-input"></div>
        <div class="sm:col-span-2"><label class="field-label">Alamat</label><textarea name="address" rows="2" class="form-input">{{ old('address', $teacher?->address ?? '') }}</textarea></div>
    </div>
</div>