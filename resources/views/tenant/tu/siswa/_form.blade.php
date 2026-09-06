@php $student = $student ?? null; @endphp

{{-- ── Data Pokok ───────────────────────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pokok</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="field-label">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name', $student?->full_name ?? '') }}" required maxlength="255" class="form-input">
        </div>
        <div>
            <label class="field-label">Email <span class="field-hint">(opsional, buat notifikasi)</span></label>
            <input type="email" name="email" value="{{ old('email', $student?->email ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Jenis Kelamin</label>
            <select name="gender" required class="form-input">
                <option value="">— Pilih —</option>
                @foreach ($genders as $value => $label)
                    <option value="{{ $value }}" {{ old('gender', $student?->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Tingkat/Kelas <span class="field-hint">(mis. 10, XI)</span></label>
            <input type="text" name="grade" value="{{ old('grade', $student?->grade ?? '') }}" maxlength="50" class="form-input">
        </div>
        <div>
            <label class="field-label">Rombel <span class="field-hint">(mis. A, IPA 1)</span></label>
            <input type="text" name="class_group" value="{{ old('class_group', $student?->class_group ?? '') }}" maxlength="50" class="form-input">
        </div>
        <div>
            <label class="field-label">Kelas</label>
            <select name="class_id" class="form-input">
                <option value="">— Belum ditentukan —</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" {{ old('class_id', $student?->class_id ?? '') === $class->id ? 'selected' : '' }}>
                        {{ $class->nama_kelas }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Jurusan <span class="field-hint">(opsional)</span></label>
            <select name="major_id" class="form-input">
                <option value="">— Tidak ada —</option>
                @foreach ($majors as $major)
                    <option value="{{ $major->id }}" {{ old('major_id', $student?->major_id ?? '') === $major->id ? 'selected' : '' }}>
                        {{ $major->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Tanggal Masuk</label>
            <input type="date" name="joined_date" value="{{ old('joined_date', $student?->joined_date?->format('Y-m-d') ?? '') }}" class="form-input">
        </div>
        <div>
            <label class="field-label">Status</label>
            <select name="status" required class="form-input">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $student?->status ?? 'aktif') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Data Pribadi (Sensitif) ─────────────────────────────────────── --}}
<div>
    <p class="section-label">Data Pribadi <span class="field-hint" style="text-transform:none; font-weight:400;">🔒 Terenkripsi</span></p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="field-label">NIS</label><input type="text" name="nis" value="{{ old('nis', $student?->nis ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">NISN</label><input type="text" name="nisn" value="{{ old('nisn', $student?->nisn ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">Tempat Lahir</label><input type="text" name="birth_place" value="{{ old('birth_place', $student?->birth_place ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $student?->birth_date ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Agama</label><input type="text" name="religion" value="{{ old('religion', $student?->religion ?? '') }}" maxlength="50" class="form-input"></div>
        <div><label class="field-label">No. Telepon</label><input type="text" name="phone" value="{{ old('phone', $student?->phone ?? '') }}" maxlength="30" class="form-input"></div>
        <div class="sm:col-span-2"><label class="field-label">Alamat</label><textarea name="address" rows="2" class="form-input">{{ old('address', $student?->address ?? '') }}</textarea></div>
    </div>
</div>

{{-- ── Data Orang Tua (Sensitif) ───────────────────────────────────── --}}
<div>
    <p class="section-label">Data Orang Tua <span class="field-hint" style="text-transform:none; font-weight:400;">🔒 Terenkripsi</span></p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="field-label">Nama Ayah</label><input type="text" name="father_name" value="{{ old('father_name', $student?->father_name ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Nama Ibu</label><input type="text" name="mother_name" value="{{ old('mother_name', $student?->mother_name ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Pekerjaan Ayah</label><input type="text" name="father_job" value="{{ old('father_job', $student?->father_job ?? '') }}" class="form-input"></div>
        <div><label class="field-label">Pekerjaan Ibu</label><input type="text" name="mother_job" value="{{ old('mother_job', $student?->mother_job ?? '') }}" class="form-input"></div>
        <div><label class="field-label">No. Telepon Orang Tua</label><input type="text" name="parent_phone" value="{{ old('parent_phone', $student?->parent_phone ?? '') }}" maxlength="30" class="form-input"></div>
        <div class="sm:col-span-2"><label class="field-label">Alamat Orang Tua <span class="field-hint">(kalau beda dari alamat siswa)</span></label><textarea name="parent_address" rows="2" class="form-input">{{ old('parent_address', $student?->parent_address ?? '') }}</textarea></div>
    </div>
</div>