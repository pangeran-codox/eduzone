<?php

namespace App\Services\Import;

use App\Http\Controllers\Tenant\Tu\StudentController;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor Siswa. Pola pembuatan mengikuti Tenant\Tu\StudentController@store (akun role `siswa`, username = NISN atau slug nama,
 * kata sandi awal DDMMYYYY dari tanggal lahir, field sensitif lewat proxy model), dengan tiga perbaikan yang SENGAJA berbeda dari form
 * (lihat PERENCANAAN_IMPOR_EXCEL.md bagian 2):
 * - tanggal lahir WAJIB (form memakai Carbon::parse(null) = hari ini bila kosong);
 * - keunikan username diperiksa lintas sekolah sebelum dipakai;
 * - satu DB::transaction() per baris (form tidak atomik).
 * grade, major_id, class_group diturunkan dari kelas (bukan diisi sendiri).
 * Keunikan NISN/NIS mengikuti UniqueHashedField: LINTAS sekolah.
 */
class StudentImporter extends BaseImporter
{
    private string $schoolId = '';

    /** @var array<string, list<array{id: string, grade: string, major_id: string, class_group: string, name: string}>> */
    private array $classesByName = [];

    public function type(): string
    {
        return 'siswa';
    }

    public function label(): string
    {
        return 'Siswa';
    }

    public function description(): string
    {
        return 'Data induk siswa. Akun login dibuat otomatis (username = NISN, kata sandi awal = tanggal lahir DDMMYYYY). Kelas harus sudah ada.';
    }

    public function createsAccounts(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            'nama_lengkap' => ['required' => true, 'hint' => 'Nama lengkap siswa', 'example' => 'Contoh Siswa Satu', 'width' => 26],
            'jenis_kelamin' => ['required' => true, 'hint' => 'L atau P', 'example' => 'L', 'options' => ['L', 'P'], 'width' => 14],
            'tanggal_lahir' => ['required' => true, 'hint' => 'DD/MM/YYYY atau YYYY-MM-DD. Wajib: dipakai untuk kata sandi awal', 'example' => '17/05/2010', 'width' => 15],
            'kelas' => ['required' => true, 'hint' => 'Nama kelas persis seperti di impor Kelas (pilih dari daftar)', 'example' => 'X IPA A', 'list' => 'kelas_aktif', 'width' => 16],
            'status' => ['required' => false, 'hint' => 'aktif, pindah, lulus, atau keluar. Kosong = aktif', 'example' => 'aktif', 'options' => ['aktif', 'pindah', 'lulus', 'keluar'], 'width' => 10],
            'nisn' => ['required' => false, 'hint' => 'Hanya angka (10 digit). Format kolom Teks agar angka 0 di depan tidak hilang', 'example' => '0012345678', 'width' => 14],
            'nis' => ['required' => false, 'hint' => 'Nomor induk sekolah, hanya angka. Catatan: NIS harus unik lintas sekolah', 'example' => '12345', 'width' => 12],
            'tempat_lahir' => ['required' => false, 'hint' => 'Kota/kabupaten lahir', 'example' => 'Surabaya', 'width' => 16],
            'agama' => ['required' => false, 'hint' => 'Teks bebas', 'example' => 'Islam', 'width' => 12],
            'alamat' => ['required' => false, 'hint' => 'Alamat siswa', 'example' => 'Jl. Contoh No. 1', 'width' => 28],
            'telepon' => ['required' => false, 'hint' => 'Format kolom Teks agar angka 0 di depan tidak hilang', 'example' => '081234567890', 'width' => 16],
            'email' => ['required' => false, 'hint' => 'Email siswa (opsional)', 'example' => '', 'width' => 22],
            'tanggal_masuk' => ['required' => false, 'hint' => 'Tanggal mulai bersekolah', 'example' => '15/07/2026', 'width' => 15],
            'nama_ayah' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 20],
            'nama_ibu' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 20],
            'pekerjaan_ayah' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 18],
            'pekerjaan_ibu' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 18],
            'alamat_ortu' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 26],
            'telepon_ortu' => ['required' => false, 'hint' => 'Format kolom Teks', 'example' => '', 'width' => 16],
        ];
    }

    public function lists(string $schoolId): array
    {
        $names = SchoolClass::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return ['kelas_aktif' => $names];
    }

    public function prepare(string $schoolId): void
    {
        $this->schoolId = $schoolId;
        $this->classesByName = [];

        $classes = SchoolClass::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->get(['id', 'grade', 'major_id', 'class_group', 'nama_kelas']);

        foreach ($classes as $class) {
            if (! $class->nama_kelas) {
                continue;
            }

            $this->classesByName[mb_strtolower(trim($class->nama_kelas))][] = [
                'id' => $class->id,
                'grade' => (string) $class->grade,
                'major_id' => $class->major_id,
                'class_group' => (string) $class->class_group,
                'name' => $class->nama_kelas,
            ];
        }
    }

    public function validateRow(array $row, array &$state): array
    {
        $errors = [];
        $line = $state['__line'] ?? '?';

        $name = $this->attempt($errors, 'nama_lengkap', fn () => $this->text($row['nama_lengkap'] ?? null, 255, true));
        $gender = $this->attempt($errors, 'jenis_kelamin', fn () => $this->gender($row['jenis_kelamin'] ?? null));
        $birth = $this->attempt($errors, 'tanggal_lahir', fn () => $this->date($row['tanggal_lahir'] ?? null, true, '1985-01-01', now()->toDateString()));
        $className = $this->attempt($errors, 'kelas', fn () => $this->text($row['kelas'] ?? null, 255, true));
        $status = $this->attempt($errors, 'status', fn () => $this->choice($row['status'] ?? null, array_keys(StudentController::STATUSES), 'aktif'));
        $nisn = $this->attempt($errors, 'nisn', fn () => $this->digits($row['nisn'] ?? null, 10, 50));
        $nis = $this->attempt($errors, 'nis', fn () => $this->digits($row['nis'] ?? null, null, 50));
        $email = $this->attempt($errors, 'email', fn () => $this->email($row['email'] ?? null));
        $joined = $this->attempt($errors, 'tanggal_masuk', fn () => $this->date($row['tanggal_masuk'] ?? null, false, '1990-01-01', null));

        $sensitive = [
            'birth_place' => $this->attempt($errors, 'tempat_lahir', fn () => $this->text($row['tempat_lahir'] ?? null, 255)),
            'religion' => $this->attempt($errors, 'agama', fn () => $this->text($row['agama'] ?? null, 50)),
            'address' => $this->attempt($errors, 'alamat', fn () => $this->text($row['alamat'] ?? null, 1000)),
            'phone' => $this->attempt($errors, 'telepon', fn () => $this->text($row['telepon'] ?? null, 30)),
            'father_name' => $this->attempt($errors, 'nama_ayah', fn () => $this->text($row['nama_ayah'] ?? null, 255)),
            'mother_name' => $this->attempt($errors, 'nama_ibu', fn () => $this->text($row['nama_ibu'] ?? null, 255)),
            'father_job' => $this->attempt($errors, 'pekerjaan_ayah', fn () => $this->text($row['pekerjaan_ayah'] ?? null, 255)),
            'mother_job' => $this->attempt($errors, 'pekerjaan_ibu', fn () => $this->text($row['pekerjaan_ibu'] ?? null, 255)),
            'parent_address' => $this->attempt($errors, 'alamat_ortu', fn () => $this->text($row['alamat_ortu'] ?? null, 1000)),
            'parent_phone' => $this->attempt($errors, 'telepon_ortu', fn () => $this->text($row['telepon_ortu'] ?? null, 30)),
        ];

        // Kelas
        $class = null;
        if ($className !== null) {
            $matches = $this->classesByName[mb_strtolower($className)] ?? [];

            if (count($matches) === 0) {
                $errors[] = ['column' => 'kelas', 'message' => "kelas \"{$className}\" tidak ditemukan atau tidak aktif (impor Kelas lebih dulu)"];
            } elseif (count($matches) > 1) {
                $errors[] = ['column' => 'kelas', 'message' => "nama kelas \"{$className}\" dipakai lebih dari satu kelas aktif (beda tahun ajaran); nonaktifkan salah satunya"];
            } else {
                $class = $matches[0];
            }
        }

        if ($errors !== []) {
            return [null, $errors, []];
        }

        // Duplikat di dalam file
        if ($nisn !== null && isset($state['nisn'][$nisn])) {
            $errors[] = ['column' => 'nisn', 'message' => 'NISN sama dengan baris ' . $state['nisn'][$nisn]];
        }
        if ($nis !== null && isset($state['nis'][$nis])) {
            $errors[] = ['column' => 'nis', 'message' => 'NIS sama dengan baris ' . $state['nis'][$nis]];
        }
        if ($email !== null && isset($state['email'][mb_strtolower($email)])) {
            $errors[] = ['column' => 'email', 'message' => 'email sama dengan baris ' . $state['email'][mb_strtolower($email)]];
        }

        // Keberadaan di database
        $skip = false;
        $skipMessage = null;

        if ($nisn !== null) {
            $hash = hash('sha256', $nisn);

            if (Student::query()->where('school_id', $this->schoolId)->where('nisn_hash', $hash)->exists()) {
                $skip = true;
                $skipMessage = 'NISN sudah ada di sekolah ini';
            } elseif (Student::withoutGlobalScopes()->where('nisn_hash', $hash)->exists()) {
                $errors[] = ['column' => 'nisn', 'message' => 'NISN sudah terdaftar di sekolah lain'];
            } elseif (User::withoutGlobalScopes()->where('username', $nisn)->exists()) {
                $errors[] = ['column' => 'nisn', 'message' => 'NISN sudah dipakai sebagai username akun lain'];
            }
        } elseif (Student::query()
            ->where('school_id', $this->schoolId)
            ->where('class_id', $class['id'])
            ->whereRaw('lower(full_name) = ?', [mb_strtolower($name)])
            ->exists()) {
            $skip = true;
            $skipMessage = 'siswa bernama sama sudah ada di kelas ini (isi NISN agar bisa dibedakan)';
        }

        if (! $skip) {
            if ($nis !== null && Student::withoutGlobalScopes()->where('nis_hash', hash('sha256', $nis))->exists()) {
                $errors[] = ['column' => 'nis', 'message' => 'NIS sudah terdaftar (keunikan NIS berlaku lintas sekolah)'];
            }

            if ($email !== null && Student::query()->where('school_id', $this->schoolId)->where('email', $email)->exists()) {
                $errors[] = ['column' => 'email', 'message' => 'email sudah dipakai siswa lain'];
            }
        }

        if ($errors !== []) {
            return [null, $errors, []];
        }

        if ($nisn !== null) {
            $state['nisn'][$nisn] = $line;
        }
        if ($nis !== null) {
            $state['nis'][$nis] = $line;
        }
        if ($email !== null) {
            $state['email'][mb_strtolower($email)] = $line;
        }

        $sensitive['nis'] = $nis;
        $sensitive['nisn'] = $nisn;
        $sensitive['birth_date'] = $birth;

        return [[
            'full_name' => $name,
            'gender' => $gender,
            'status' => $status,
            'email' => $email,
            'joined_date' => $joined,
            'class_id' => $class['id'],
            'class_name' => $class['name'],
            'grade' => $class['grade'],
            'major_id' => $class['major_id'],
            'class_group' => $class['class_group'],
            'birth_date' => $birth,
            'nisn' => $nisn,
            'sensitive' => array_filter($sensitive, fn ($v) => $v !== null),
            'skip' => $skip,
            'skip_message' => $skipMessage,
        ], [], []];
    }

    public function importRow(array $data, string $schoolId): array
    {
        if (! empty($data['skip'])) {
            return ['status' => 'skipped', 'message' => $data['skip_message'] ?? 'sudah ada'];
        }

        return DB::transaction(function () use ($data, $schoolId) {
            $username = $this->uniqueUsername($data['nisn'], $data['full_name']);
            $plainPassword = Carbon::parse($data['birth_date'])->format('dmY');

            $user = User::create([
                'school_id' => $schoolId,
                'username' => $username,
                'email' => "{$username}@siswa.eduzone.local",
                'role' => 'siswa',
                'password' => bcrypt($plainPassword),
                'is_active' => true,
            ]);

            $student = new Student();
            $student->school_id = $schoolId;
            $student->user_id = $user->id;
            $student->full_name = $data['full_name'];
            $student->email = $data['email'];
            $student->gender = $data['gender'];
            $student->grade = $data['grade'];
            $student->major_id = $data['major_id'];
            $student->class_group = $data['class_group'];
            $student->class_id = $data['class_id'];
            $student->joined_date = $data['joined_date'];
            $student->status = $data['status'];

            foreach ($data['sensitive'] as $field => $value) {
                $student->{$field} = $value;
            }

            // Bisa melempar SensitiveDataEncryptionException; transaksi dibatalkan dan ditangani ImportRunner.
            $student->save();

            return [
                'status' => 'created',
                'credential' => [
                    'peran' => 'Siswa',
                    'nama' => $data['full_name'],
                    'kelas' => $data['class_name'],
                    'username' => $username,
                    'password' => $plainPassword,
                ],
            ];
        });
    }

    private function email(mixed $value): ?string
    {
        $s = $this->text($value, 255);

        if ($s === null) {
            return null;
        }

        if (! filter_var($s, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('format email tidak valid');
        }

        return mb_strtolower($s);
    }

    /** username = NISN bila ada (harus unik lintas sekolah), kalau tidak slug nama dengan sufiks angka bila bentrok. */
    private function uniqueUsername(?string $nisn, string $fullName): string
    {
        if ($nisn !== null) {
            if (User::withoutGlobalScopes()->where('username', $nisn)->exists()) {
                throw new \RuntimeException('NISN sudah dipakai sebagai username akun lain');
            }

            return $nisn;
        }

        $base = Str::limit(Str::slug($fullName, '.') ?: 'siswa', 40, '');
        $username = $base;
        $suffix = 1;

        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $username = "{$base}{$suffix}";
            $suffix++;
        }

        return $username;
    }
}
