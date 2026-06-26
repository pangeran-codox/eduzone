<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToSchool, HasUuids;

    public $timestamps = false; // tabel ini cuma punya created_at (useCurrent), tidak ada updated_at

    protected $table = 'activity_logs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'activity',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper khusus: lihat riwayat perubahan data sensitif untuk record tertentu.
     * Contoh: ActivityLog::sensitiveDataHistory('Student', $student->id)->get();
     *
     * Catatan: kolom `description` bertipe text (bukan json asli Postgres),
     * jadi pakai raw query dengan cast ::json untuk query field di dalamnya.
     */
    public static function sensitiveDataHistory(string $type, string $id)
    {
        return static::where('activity', 'like', 'sensitive_data.%')
            ->whereRaw("description::json->>'auditable_type' = ?", [$type])
            ->whereRaw("description::json->>'auditable_id' = ?", [$id])
            ->orderByDesc('created_at');
    }
}