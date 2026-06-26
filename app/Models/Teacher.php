<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Teacher extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'teachers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'nip_hash',
        'nuptk_hash',
        'full_name',
        'email', // plain - dipakai aktif untuk notifikasi
        'gender',
        'last_education',
        'education_major',
        'employment_status',
        'joined_date',
        'major_id',
        'is_homeroom',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'joined_date' => 'date',
        'is_homeroom' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Field sensitif yang sebenarnya disimpan di tabel teacher_sensitive_data,
     * tapi diakses transparan lewat $teacher->nip, $teacher->address, dst.
     */
    protected static array $proxiedSensitiveFields = [
        'nip',
        'nuptk',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
    ];

    /** Field yang juga punya kolom hash di tabel teachers untuk searchable exact-match */
    protected static array $hashedFields = ['nip', 'nuptk'];

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

    public function sensitiveData(): HasOne
    {
        return $this->hasOne(TeacherSensitiveData::class, 'teacher_id');
    }

    /**
     * Proxy GET untuk field sensitif: $teacher->nip, $teacher->address, dst.
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
     * Proxy SET untuk field sensitif: $teacher->nip = '...'.
     * TIDAK langsung save ke DB - ditampung di $pendingSensitiveData,
     * baru benar2 disimpan saat $teacher->save() dipanggil (1x save = 1 baris audit log).
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
     * Override save(): simpan dulu data utama (tabel teachers), lalu flush
     * pending sensitive data ke tabel teacher_sensitive_data dalam 1x save.
     */
    public function save(array $options = []): bool
    {
        $saved = parent::save($options);

        if ($saved && !empty($this->pendingSensitiveData)) {
            $sensitive = $this->sensitiveData ?? $this->sensitiveData()->make([
                'school_id' => $this->school_id,
            ]);

            foreach ($this->pendingSensitiveData as $field => $value) {
                $sensitive->{$field} = $value;
            }

            $sensitive->teacher_id = $this->id;
            $sensitive->school_id = $this->school_id;
            $sensitive->save();

            $this->setRelation('sensitiveData', $sensitive);
            $this->pendingSensitiveData = [];
        }

        return $saved;
    }

    /**
     * Helper untuk pencarian exact-match by NIP.
     */
    public static function findByNip(string $nip): ?self
    {
        return static::where('nip_hash', hash('sha256', $nip))->first();
    }

    /**
     * Helper untuk pencarian exact-match by NUPTK.
     */
    public static function findByNuptk(string $nuptk): ?self
    {
        return static::where('nuptk_hash', hash('sha256', $nuptk))->first();
    }
}