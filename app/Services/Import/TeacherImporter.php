<?php

namespace App\Services\Import;

use App\Http\Controllers\Tenant\Tu\TeacherController;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor Guru. Pola mengikuti Tenant\Tu\TeacherController@store: akun role `guru_mapel`, username = NIP atau slug nama,
 * kata sandi awal DDMMYYYY dari tanggal lahir (atau `eduzone123` bila tanggal lahir kosong), field sensitif lewat proxy model.
 * Perbedaan dari form: keunikan username diperiksa lintas sekolah sebelum dipakai, dan satu DB::transaction() per baris.
 * Keunikan NIP/NUPTK mengikuti UniqueHashedField: LINTAS sekolah.
 *
 * Catatan: guru tanpa NIP dan NUPTK tidak bisa dibedakan dari nama kembar; baris dengan nama yang sudah ada di sekolah
 * dilewati (dengan keterangan), supaya mengunggah ulang file yang sama tidak menggandakan guru.
 */
class TeacherImporter extends BaseImporter
{
    private string $schoolId = '';

    public function type(): string
    {
        return 'guru';
    }

    public function label(): string
    {
        return 'Guru';
    }

    public function description(): string
    {
        return 'Data guru. Akun login dibuat otomatis (username = NIP, kata sandi awal = tanggal lahir DDMMYYYY, atau eduzone123 bila tanggal lahir kosong).';
    }

    public function createsAccounts(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            'nama_lengkap' => ['required' => true, 'hint' => 'Nama lengkap beserta gelar', 'example' => 'Contoh Guru Satu, S.Pd.', 'width' => 28],
            'jenis_kelamin' => ['required' => true, 'hint' => 'L atau P', 'example' => 'P', 'options' => ['L', 'P'], 'width' => 14],
            'nip' => ['required' => false, 'hint' => 'Hanya angka. Format kolom Teks agar angka tidak berubah', 'example' => '198001012005011001', 'width' => 22],
            'nuptk' => ['required' => false, 'hint' => 'Hanya angka. Format kolom Teks', 'example' => '1234567890123456', 'width' => 20],
            'tanggal_lahir' => ['required' => false, 'hint' => 'DD/MM/YYYY atau YYYY-MM-DD. Kosong = kata sandi awal eduzone123', 'example' => '01/01/1980', 'width' => 15],
            'tempat_lahir' => ['required' => false, 'hint' => '', 'example' => 'Surabaya', 'width' => 16],
            'agama' => ['required' => false, 'hint' => 'Teks bebas', 'example' => 'Islam', 'width' => 12],
            'alamat' => ['required' => false, 'hint' => '', 'example' => '', 'width' => 28],
            'telepon' => ['required' => false, 'hint' => 'Format kolom Teks agar angka 0 di depan tidak hilang', 'example' => '081234567890', 'width' => 16],
            'email' => ['required' => false, 'hint' => 'Email guru (dipakai untuk notifikasi)', 'example' => '', 'width' => 22],
            'pendidikan_terakhir' => ['required' => false, 'hint' => 'Contoh: S1, S2', 'example' => 'S1', 'width' => 18],
            'jurusan_pendidikan' => ['required' => false, 'hint' => 'Contoh: Pendidikan Matematika', 'example' => '', 'width' => 24],
            'status_kepegawaian' => ['required' => false, 'hint' => implode(', ', TeacherController::EMPLOYMENT_STATUSES), 'example' => 'PNS', 'options' => TeacherController::EMPLOYMENT_STATUSES, 'width' => 18],
            'tanggal_masuk' => ['required' => false, 'hint' => 'Tanggal mulai mengajar', 'example' => '', 'width' => 15],
            'wali_kelas' => ['required' => false, 'hint' => 'ya atau tidak. Kosong = tidak', 'example' => 'tidak', 'options' => ['ya', 'tidak'], 'width' => 11],
            'aktif' => ['required' => false, 'hint' => 'ya atau tidak. Kosong = ya', 'example' => 'ya', 'options' => ['ya', 'tidak'], 'width' => 8],
        ];
    }

    public function prepare(string $schoolId): void
    {
        $this->schoolId = $schoolId;
    }

    public function validateRow(array $row, array &$state): array
    {
        $errors = [];
        $line = $state['__line'] ?? '?';

        $name = $this->attempt($errors, 'nama_lengkap', fn () => $this->text($row['nama_lengkap'] ?? null, 255, true));
        $gender = $this->attempt($errors, 'jenis_kelamin', fn () => $this->gender($row['jenis_kelamin'] ?? null));
        $nip = $this->attempt($errors, 'nip', fn () => $this->digits($row['nip'] ?? null, null, 50));
        $nuptk = $this->attempt($errors, 'nuptk', fn () => $this->digits($row['nuptk'] ?? null, null, 50));
        $birth = $this->attempt($errors, 'tanggal_lahir', fn () => $this->date($row['tanggal_lahir'] ?? null, false, '1930-01-01', now()->toDateString()));
        $email = $this->attempt($errors, 'email', fn () => $this->email($row['email'] ?? null));
        $lastEducation = $this->attempt($errors, 'pendidikan_terakhir', fn () => $this->text($row['pendidikan_terakhir'] ?? null, 50));
        $educationMajor = $this->attempt($errors, 'jurusan_pendidikan', fn () => $this->text($row['jurusan_pendidikan'] ?? null, 255));
        $employment = $this->attempt($errors, 'status_kepegawaian', fn () => $this->choice($row['status_kepegawaian'] ?? null, TeacherController::EMPLOYMENT_STATUSES));
        $joined = $this->attempt($errors, 'tanggal_masuk', fn () => $this->date($row['tanggal_masuk'] ?? null, false, '1960-01-01', null));
        $homeroom = $this->attempt($errors, 'wali_kelas', fn () => $this->boolean($row['wali_kelas'] ?? null, false));
        $active = $this->attempt($errors, 'aktif', fn () => $this->boolean($row['aktif'] ?? null, true));

        $sensitive = [
            'birth_place' => $this->attempt($errors, 'tempat_lahir', fn () => $this->text($row['tempat_lahir'] ?? null, 255)),
            'religion' => $this->attempt($errors, 'agama', fn () => $this->text($row['agama'] ?? null, 50)),
            'address' => $this->attempt($errors, 'alamat', fn () => $this->text($row['alamat'] ?? null, 1000)),
            'phone' => $this->attempt($errors, 'telepon', fn () => $this->text($row['telepon'] ?? null, 30)),
        ];

        if ($errors !== []) {
            return [null, $errors, []];
        }

        // Duplikat di dalam file
        if ($nip !== null && isset($state['nip'][$nip])) {
            $errors[] = ['column' => 'nip', 'message' => 'NIP sama dengan baris ' . $state['nip'][$nip]];
        }
        if ($nuptk !== null && isset($state['nuptk'][$nuptk])) {
            $errors[] = ['column' => 'nuptk', 'message' => 'NUPTK sama dengan baris ' . $state['nuptk'][$nuptk]];
        }
        if ($email !== null && isset($state['email'][mb_strtolower($email)])) {
            $errors[] = ['column' => 'email', 'message' => 'email sama dengan baris ' . $state['email'][mb_strtolower($email)]];
        }
        if ($nip === null && $nuptk === null && isset($state['name'][mb_strtolower($name)])) {
            $errors[] = ['column' => 'nama_lengkap', 'message' => 'nama sama dengan baris ' . $state['name'][mb_strtolower($name)] . ' dan tidak ada NIP/NUPTK untuk membedakan'];
        }

        // Keberadaan di database
        $skip = false;
        $skipMessage = null;

        if ($nip !== null) {
            $hash = hash('sha256', $nip);

            if (Teacher::query()->where('school_id', $this->schoolId)->where('nip_hash', $hash)->exists()) {
                $skip = true;
                $skipMessage = 'NIP sudah ada di sekolah ini';
            } elseif (Teacher::withoutGlobalScopes()->where('nip_hash', $hash)->exists()) {
                $errors[] = ['column' => 'nip', 'message' => 'NIP sudah terdaftar di sekolah lain'];
            } elseif (User::withoutGlobalScopes()->where('username', $nip)->exists()) {
                $errors[] = ['column' => 'nip', 'message' => 'NIP sudah dipakai sebagai username akun lain'];
            }
        } elseif ($nuptk === null && Teacher::query()
            ->where('school_id', $this->schoolId)
            ->whereRaw('lower(full_name) = ?', [mb_strtolower($name)])
            ->exists()) {
            $skip = true;
            $skipMessage = 'guru bernama sama sudah ada (isi NIP agar bisa dibedakan)';
        }

        if (! $skip) {
            if ($nuptk !== null && Teacher::withoutGlobalScopes()->where('nuptk_hash', hash('sha256', $nuptk))->exists()) {
                $errors[] = ['column' => 'nuptk', 'message' => 'NUPTK sudah terdaftar'];
            }

            if ($email !== null && Teacher::query()->where('school_id', $this->schoolId)->where('email', $email)->exists()) {
                $errors[] = ['column' => 'email', 'message' => 'email sudah dipakai guru lain'];
            }
        }

        if ($errors !== []) {
            return [null, $errors, []];
        }

        if ($nip !== null) {
            $state['nip'][$nip] = $line;
        }
        if ($nuptk !== null) {
            $state['nuptk'][$nuptk] = $line;
        }
        if ($email !== null) {
            $state['email'][mb_strtolower($email)] = $line;
        }
        if ($nip === null && $nuptk === null) {
            $state['name'][mb_strtolower($name)] = $line;
        }

        $sensitive['nip'] = $nip;
        $sensitive['nuptk'] = $nuptk;
        $sensitive['birth_date'] = $birth;

        return [[
            'full_name' => $name,
            'gender' => $gender,
            'email' => $email,
            'last_education' => $lastEducation,
            'education_major' => $educationMajor,
            'employment_status' => $employment,
            'joined_date' => $joined,
            'is_homeroom' => $homeroom,
            'is_active' => $active,
            'birth_date' => $birth,
            'nip' => $nip,
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
            $username = $this->uniqueUsername($data['nip'], $data['full_name']);
            $plainPassword = $data['birth_date'] !== null
                ? Carbon::parse($data['birth_date'])->format('dmY')
                : 'eduzone123';

            $user = User::create([
                'school_id' => $schoolId,
                'username' => $username,
                'email' => "{$username}@guru.eduzone.local",
                'role' => 'guru_mapel',
                'password' => bcrypt($plainPassword),
                'is_active' => true,
            ]);

            $teacher = new Teacher();
            $teacher->school_id = $schoolId;
            $teacher->user_id = $user->id;
            $teacher->full_name = $data['full_name'];
            $teacher->email = $data['email'];
            $teacher->gender = $data['gender'];
            $teacher->last_education = $data['last_education'];
            $teacher->education_major = $data['education_major'];
            $teacher->employment_status = $data['employment_status'];
            $teacher->joined_date = $data['joined_date'];
            $teacher->is_homeroom = $data['is_homeroom'];
            $teacher->is_active = $data['is_active'];

            foreach ($data['sensitive'] as $field => $value) {
                $teacher->{$field} = $value;
            }

            // Bisa melempar SensitiveDataEncryptionException; transaksi dibatalkan dan ditangani ImportRunner.
            $teacher->save();

            return [
                'status' => 'created',
                'credential' => [
                    'peran' => 'Guru',
                    'nama' => $data['full_name'],
                    'kelas' => '',
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

    private function uniqueUsername(?string $nip, string $fullName): string
    {
        if ($nip !== null) {
            if (User::withoutGlobalScopes()->where('username', $nip)->exists()) {
                throw new \RuntimeException('NIP sudah dipakai sebagai username akun lain');
            }

            return $nip;
        }

        $base = Str::limit(Str::slug($fullName, '.') ?: 'guru', 40, '');
        $username = $base;
        $suffix = 1;

        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $username = "{$base}{$suffix}";
            $suffix++;
        }

        return $username;
    }
}
