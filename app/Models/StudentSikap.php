<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSikap extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'student_sikap';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'academic_year',
        'semester',
        'sikap_spiritual',
        'sikap_sosial',
        'catatan_sikap',
        'ekskul',
        'catatan_wakel',
        'ketidakhadiran_sakit',
        'ketidakhadiran_izin',
        'ketidakhadiran_alpa',
    ];

    protected $casts = [
        'ketidakhadiran_sakit' => 'integer',
        'ketidakhadiran_izin' => 'integer',
        'ketidakhadiran_alpa' => 'integer',
        'catatan_sikap' => EncryptedAttribute::class,
        'catatan_wakel' => EncryptedAttribute::class,
        // 'ekskul' SENGAJA TIDAK dienkripsi - cuma daftar nama ekstrakurikuler, bukan data sensitif
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}