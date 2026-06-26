<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'students';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'nis_hash',
        'nisn_hash',
        'full_name',
        'email', // plain - dipakai aktif untuk notifikasi
        'gender',
        'grade',
        'major_id',
        'class_group',
        'class_id',
        'joined_date',
        'status',
        'photo',
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    /**
     * Field sensitif yang sebenarnya disimpan di tabel student_sensitive_data,
     * tapi diakses transparan lewat $student->nisn, $student->address, dst.
     *
     * Catatan: 'email' SENGAJA TIDAK masuk di sini - email siswa dipakai aktif
     * untuk notifikasi otomatis, jadi disimpan plain di kolom students.email
     * supaya tidak ada overhead gRPC tiap kirim notifikasi massal.
     */
    protected static array $proxiedSensitiveFields = [
        'nis',
        'nisn',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
        'father_name',
        'mother_name',
        'father_job',
        'mother_job',
        'parent_address',
        'parent_phone',
    ];

    /** Field yang juga punya kolom hash di tabel students untuk searchable exact-match */
    protected static array $hashedFields = ['nis', 'nisn'];

    /** Penampung sementara field sensitif yang di-set lewat __set, baru benar2 disimpan saat save() */
    protected array $pendingSensitiveData = [];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function sensitiveData(): HasOne
    {
        return $this->hasOne(StudentSensitiveData::class, 'student_id');
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class, 'student_id');
    }

    public function studentSikap(): HasMany
    {
        return $this->hasMany(StudentSikap::class, 'student_id');
    }

    public function studentAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class, 'student_id');
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentRecord::class, 'student_id');
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'student_id');
    }

    public function studentSubjectAttendances(): HasMany
    {
        return $this->hasMany(StudentSubjectAttendance::class, 'student_id');
    }

    /**
     * Proxy GET untuk field sensitif: $student->nisn, $student->address, dst.
     * Cek dulu pending (belum disimpan), baru fallback ke data yang sudah tersimpan.
     */
    public function __get($key)
    {
        if (in_array($key, static::$proxiedSensitiveFields, true)) {
            if (array_key_exists($key, $this->pendingSensitiveData)) {
                return $this->pendingSensitiveData[$key];
            }

            return $this->sensitiveData?->{$key};
        }

        return parent::__get($key);
    }

    /**
     * Proxy SET untuk field sensitif: $student->nisn = '0012345678'.
     * TIDAK langsung save ke DB - cuma ditampung di $pendingSensitiveData.
     * Baru benar2 di-encrypt & disimpan saat $student->save() dipanggil,
     * supaya banyak field sekaligus jadi 1x save = 1 baris audit log.
     *
     * Kolom hash (nisn_hash/nis_hash) langsung di-set ke attributes (murah, tanpa gRPC),
     * supaya ikut tersimpan otomatis saat parent::save().
     */
    public function __set($key, $value)
    {
        if (in_array($key, static::$proxiedSensitiveFields, true)) {
            $this->pendingSensitiveData[$key] = $value;

            if (in_array($key, static::$hashedFields, true)) {
                $hashColumn = "{$key}_hash";
                $this->attributes[$hashColumn] = $value ? hash('sha256', $value) : null;
            }

            return;
        }

        parent::__set($key, $value);
    }

    /**
     * Override save(): simpan dulu data utama (tabel students), lalu flush
     * pending sensitive data ke tabel student_sensitive_data dalam 1x save.
     */
    public function save(array $options = []): bool
    {
        $saved = parent::save($options);

        if ($saved && !empty($this->pendingSensitiveData)) {
            $sensitive = $this->sensitiveData ?? $this->sensitiveData()->make([
                'school_id' => $this->school_id,
            ]);

            foreach ($this->pendingSensitiveData as $field => $value) {
                $sensitive->{$field} = $value; // trigger encrypt di StudentSensitiveData::__set
            }

            $sensitive->student_id = $this->id;
            $sensitive->school_id = $this->school_id;
            $sensitive->save(); // 1x save = 1 baris audit log untuk semua field yang berubah

            $this->setRelation('sensitiveData', $sensitive);
            $this->pendingSensitiveData = [];
        }

        return $saved;
    }

    /**
     * Helper untuk pencarian exact-match by NISN (memakai kolom hash, bukan ciphertext).
     *
     * Contoh: Student::findByNisn('0012345678');
     */
    public static function findByNisn(string $nisn): ?self
    {
        return static::where('nisn_hash', hash('sha256', $nisn))->first();
    }

    /**
     * Helper untuk pencarian exact-match by NIS.
     */
    public static function findByNis(string $nis): ?self
    {
        return static::where('nis_hash', hash('sha256', $nis))->first();
    }
}