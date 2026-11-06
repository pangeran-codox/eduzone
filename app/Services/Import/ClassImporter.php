<?php

namespace App\Services\Import;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use App\Support\EducationLevels;
use Illuminate\Support\Facades\DB;

/**
 * Impor Kelas. Fakta dari skema (db:table classes/majors, 11 Okt 2026):
 * - classes.major_id NOT NULL: setiap kelas wajib punya jurusan. Kolom jurusan kosong = jurusan "Umum" (dibuat otomatis).
 * - Jenjang (SD/MI/SMP/MTs/SMA/SMK/MA/MAK) diambil dari schools.level dan menentukan tingkat yang sah, istilah dan kewajiban jurusan (App\Support\EducationLevels).
 * - Kunci unik kelas = (school_id, grade, major_id, class_group, academic_year); nama_kelas hanya label (nullable di DB).
 *   Siswa dicocokkan ke kelas lewat nama_kelas, jadi importer ini menuntut nama_kelas unik per tahun ajaran.
 * - majors tidak punya unique index: pencocokan jurusan dilakukan di sini, lewat NAMA atau SINGKATAN (kolom abbreviation), tidak peka huruf besar/kecil.
 */
class ClassImporter extends BaseImporter
{
    public const DEFAULT_MAJOR = 'Umum';

    /** Jenjang sekolah (SD, MI, SMP, MTs, SMA, SMK, MA, MAK) dan profilnya; null bila jenjang kosong/tidak dikenal (aturan umum). */
    private ?string $levelCode = null;

    private ?array $profile = null;

    /** @var array<string, string> kunci pencarian (nama ATAU singkatan, huruf kecil) => id jurusan */
    private array $majors = [];

    /** @var array<string, string> id jurusan => nama huruf kecil (bentuk baku untuk kunci perbandingan kelas) */
    private array $majorCanonical = [];

    /** @var array<string, string> "grade|major_key|group|year" => id kelas */
    private array $existingKeys = [];

    /** @var array<string, string> "nama_kelas huruf kecil|tahun" => id kelas */
    private array $existingNames = [];

    public function type(): string
    {
        return 'kelas';
    }

    public function label(): string
    {
        return 'Kelas';
    }

    public function description(): string
    {
        return 'Daftar kelas (rombongan belajar). Impor ini harus dilakukan lebih dulu, karena siswa dan jadwal merujuk ke nama kelas.';
    }

    public function columns(): array
    {
        $p = $this->profile;

        $gradeHint = $p
            ? 'Tingkat jenjang ' . $this->levelCode . ': ' . implode(', ', $p['grades']) . '. Boleh ditulis angka (mis. 10 untuk X).'
            : 'Tingkat kelas, mis. X, XI, XII atau 7, 8, 9. Angka diubah otomatis ke angka Romawi baku.';

        if ($p === null) {
            $majorHint = 'Kosongkan bila tidak ada jurusan (otomatis "Umum"). Nama lengkap atau singkatan; yang belum ada dibuat otomatis.';
        } elseif ($p['major_label'] === null) {
            $majorHint = 'Tidak dipakai untuk jenjang ' . $this->levelCode . '. Kosongkan (otomatis "Umum").';
        } else {
            $majorHint = $p['major_label'] . ($p['major_required'] ? ' (WAJIB di jenjang ' . $this->levelCode . ')' : ' (boleh kosong)')
                . '. Nama lengkap atau singkatan, mis. ' . implode(', ', array_slice($p['major_examples'], 0, 3))
                . '. Yang belum ada dibuat otomatis.';
        }

        $firstGrade = $p['grades'][0] ?? 'X';
        $exampleMajor = ($p !== null && $p['major_label'] !== null) ? ($p['major_examples'][0] ?? '') : '';
        $exampleName = trim($firstGrade . ' ' . $exampleMajor . ' A');

        return [
            'nama_kelas' => ['required' => true, 'hint' => 'Nama yang tampil di aplikasi, harus unik per tahun ajaran. Contoh: ' . $exampleName, 'example' => $exampleName, 'width' => 22],
            'tingkat' => ['required' => true, 'hint' => $gradeHint, 'example' => $firstGrade, 'width' => 10],
            'jurusan' => ['required' => (bool) ($p['major_required'] ?? false), 'hint' => $majorHint, 'example' => $exampleMajor, 'width' => 22],
            'rombel' => ['required' => true, 'hint' => 'Rombongan belajar, maksimal 10 karakter. Contoh: A, B, 1, 2', 'example' => 'A', 'width' => 10],
            'tahun_ajaran' => ['required' => true, 'hint' => 'Format AAAA/BBBB. Contoh: 2026/2027', 'example' => '2026/2027', 'width' => 14],
            'kapasitas' => ['required' => false, 'hint' => 'Jumlah maksimal siswa. Kosong = 36', 'example' => '36', 'width' => 11],
            'aktif' => ['required' => false, 'hint' => 'ya atau tidak. Kosong = ya', 'example' => 'ya', 'options' => ['ya', 'tidak'], 'width' => 8],
        ];
    }

    public function configure(string $schoolId): void
    {
        $level = School::withoutGlobalScopes()->where('id', $schoolId)->value('level');

        $this->levelCode = EducationLevels::canonicalCode($level);
        $this->profile = EducationLevels::profile($level);
    }

    public function prepare(string $schoolId): void
    {
        $this->configure($schoolId);

        $this->majors = [];
        $this->majorCanonical = [];

        $majors = Major::query()->where('school_id', $schoolId)->get(['id', 'name', 'abbreviation']);

        foreach ($majors as $major) {
            $canonical = mb_strtolower(trim($major->name));
            $this->majorCanonical[$major->id] = $canonical;
            $this->majors[$canonical] = $major->id;
        }

        // Singkatan (mis. "IPA" untuk "Ilmu Pengetahuan Alam") dipakai sebagai pintu masuk tambahan; nama lengkap menang bila bentrok.
        foreach ($majors as $major) {
            $abbreviation = $major->abbreviation ? mb_strtolower(trim($major->abbreviation)) : '';

            if ($abbreviation !== '' && ! isset($this->majors[$abbreviation])) {
                $this->majors[$abbreviation] = $major->id;
            }
        }

        $this->existingKeys = [];
        $this->existingNames = [];

        foreach (SchoolClass::query()->where('school_id', $schoolId)->get(['id', 'grade', 'major_id', 'class_group', 'academic_year', 'nama_kelas']) as $class) {
            $majorKey = $this->majorCanonical[$class->major_id] ?? (string) $class->major_id;
            $this->existingKeys[$this->key((string) $class->grade, $majorKey, (string) $class->class_group, (string) $class->academic_year)] = $class->id;

            if ($class->nama_kelas) {
                $this->existingNames[mb_strtolower($class->nama_kelas) . '|' . $class->academic_year] = $class->id;
            }
        }
    }

    public function validateRow(array $row, array &$state): array
    {
        $errors = [];
        $warnings = [];

        $name = $this->attempt($errors, 'nama_kelas', fn () => $this->text($row['nama_kelas'] ?? null, 255, true));
        $grade = $this->attempt($errors, 'tingkat', fn () => $this->grade($row['tingkat'] ?? null));
        $majorName = $this->attempt($errors, 'jurusan', fn () => $this->text($row['jurusan'] ?? null, 100));
        $group = $this->attempt($errors, 'rombel', fn () => $this->text($row['rombel'] ?? null, 10, true));
        $year = $this->attempt($errors, 'tahun_ajaran', fn () => $this->academicYear($row['tahun_ajaran'] ?? null));
        $capacity = $this->attempt($errors, 'kapasitas', fn () => $this->capacity($row['kapasitas'] ?? null));
        $active = $this->attempt($errors, 'aktif', fn () => $this->boolean($row['aktif'] ?? null, true));

        if (($this->profile['major_required'] ?? false) && $majorName === null) {
            $errors[] = ['column' => 'jurusan', 'message' => 'wajib diisi untuk jenjang ' . $this->levelCode . ' (' . strtolower($this->profile['major_label']) . ')'];
        }

        if ($errors !== []) {
            return [null, $errors, $warnings];
        }

        $group = mb_strtoupper($group);
        $majorName = $majorName ?? self::DEFAULT_MAJOR;
        $lookup = mb_strtolower($majorName);
        $knownMajorId = $this->majors[$lookup] ?? null;
        // Kunci baku: "IPA" dan "Ilmu Pengetahuan Alam" yang merujuk jurusan sama harus menghasilkan kunci sama.
        $majorKey = $knownMajorId !== null ? $this->majorCanonical[$knownMajorId] : $lookup;

        $line = $state['__line'] ?? '?';
        $key = $this->key($grade, $majorKey, $group, $year);
        $nameKey = mb_strtolower($name) . '|' . $year;

        // Duplikat di dalam file
        if (isset($state['keys'][$key])) {
            $errors[] = ['column' => 'nama_kelas', 'message' => 'kombinasi tingkat, jurusan, rombel, dan tahun ajaran sama dengan baris ' . $state['keys'][$key]];
        } elseif (isset($state['names'][$nameKey])) {
            $errors[] = ['column' => 'nama_kelas', 'message' => 'nama kelas sama dengan baris ' . $state['names'][$nameKey] . ' pada tahun ajaran yang sama'];
        }

        // Bentrok nama dengan kelas lain yang sudah ada (kombinasi berbeda)
        $existingId = $this->existingKeys[$key] ?? null;
        $sameNameId = $this->existingNames[$nameKey] ?? null;

        if ($errors === [] && $existingId === null && $sameNameId !== null) {
            $errors[] = ['column' => 'nama_kelas', 'message' => 'nama kelas sudah dipakai kelas lain pada tahun ajaran ' . $year];
        }

        if ($errors !== []) {
            return [null, $errors, $warnings];
        }

        $state['keys'][$key] = $line;
        $state['names'][$nameKey] = $line;

        if ($knownMajorId === null) {
            $state['new_majors'][$lookup] = $majorName;
        }

        return [[
            'nama_kelas' => $name,
            'grade' => $grade,
            'major_name' => $majorName,
            'class_group' => $group,
            'academic_year' => $year,
            'kapasitas' => $capacity,
            'is_active' => $active,
            'skip' => $existingId !== null,
        ], [], $warnings];
    }

    public function importRow(array $data, string $schoolId): array
    {
        if (! empty($data['skip'])) {
            return ['status' => 'skipped', 'message' => 'kelas sudah ada'];
        }

        return DB::transaction(function () use ($data, $schoolId) {
            $needle = mb_strtolower($data['major_name']);

            $major = Major::query()->where('school_id', $schoolId)->whereRaw('lower(name) = ?', [$needle])->first()
                ?? Major::query()->where('school_id', $schoolId)->whereRaw('lower(abbreviation) = ?', [$needle])->first();

            if (! $major) {
                $major = Major::create([
                    'school_id' => $schoolId,
                    'name' => $data['major_name'],
                    'is_active' => true,
                ]);
            }

            $exists = SchoolClass::query()
                ->where('school_id', $schoolId)
                ->where('grade', $data['grade'])
                ->where('major_id', $major->id)
                ->where('class_group', $data['class_group'])
                ->where('academic_year', $data['academic_year'])
                ->exists();

            if ($exists) {
                return ['status' => 'skipped', 'message' => 'kelas sudah ada'];
            }

            SchoolClass::create([
                'school_id' => $schoolId,
                'grade' => $data['grade'],
                'major_id' => $major->id,
                'class_group' => $data['class_group'],
                'academic_year' => $data['academic_year'],
                'nama_kelas' => $data['nama_kelas'],
                'kapasitas' => $data['kapasitas'],
                'is_active' => $data['is_active'],
            ]);

            return ['status' => 'created'];
        });
    }

    public function summaryNotes(array $state): array
    {
        $new = array_values($state['new_majors'] ?? []);

        return $new === [] ? [] : ['Jurusan baru yang akan dibuat otomatis: ' . implode(', ', $new)];
    }

    private function key(string $grade, string $majorKey, string $group, string $year): string
    {
        return mb_strtolower($grade) . '|' . mb_strtolower($majorKey) . '|' . mb_strtolower($group) . '|' . $year;
    }

    /** Normalkan ke angka Romawi baku dan cocokkan dengan tingkat yang sah untuk jenjang sekolah ini. */
    private function grade(mixed $value): string
    {
        $raw = $this->text($value, 50, true);
        $grade = EducationLevels::normalizeGrade($raw);

        if ($grade === null) {
            throw new \InvalidArgumentException("tingkat \"{$raw}\" tidak dikenali (contoh: X, XI, XII atau 10, 11, 12)");
        }

        if ($this->profile !== null && ! in_array($grade, $this->profile['grades'], true)) {
            throw new \InvalidArgumentException("tingkat {$grade} tidak sesuai jenjang {$this->levelCode} (" . implode(', ', $this->profile['grades']) . ')');
        }

        return $grade;
    }

    private function academicYear(mixed $value): string
    {
        $s = $this->text($value, 10, true);

        if (! preg_match('/^(\d{4})\/(\d{4})$/', $s, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            throw new \InvalidArgumentException('format AAAA/BBBB dengan BBBB = AAAA + 1, contoh 2026/2027');
        }

        return $s;
    }

    private function capacity(mixed $value): int
    {
        $s = $this->digits($value, null, 3);

        if ($s === null) {
            return 36;
        }

        $n = (int) $s;

        if ($n < 1 || $n > 100) {
            throw new \InvalidArgumentException('antara 1 dan 100');
        }

        return $n;
    }
}
