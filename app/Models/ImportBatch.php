<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use BelongsToSchool, HasUuids;

    public const STATUS_VALIDATED = 'validated';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_FAILED = 'failed';

    protected $table = 'import_batches';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id', 'user_id', 'type', 'original_name', 'file_path', 'status',
        'total_rows', 'valid_rows', 'error_rows', 'processed_rows',
        'created_count', 'skipped_count', 'failed_count',
        'errors', 'failures', 'notes', 'message',
        'credentials_path', 'confirmed_at', 'finished_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'failures' => 'array',
            'notes' => 'array',
            'confirmed_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Sedang menunggu atau berjalan di antrean. */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_STOPPED, self::STATUS_FAILED], true);
    }

    /** Persentase posisi baca terhadap total baris (0-100). */
    public function progressPercent(): int
    {
        if ($this->total_rows <= 0) {
            return $this->isFinished() ? 100 : 0;
        }

        return (int) min(100, round($this->processed_rows / $this->total_rows * 100));
    }
}
