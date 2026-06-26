<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk model *SensitiveData (StudentSensitiveData, TeacherSensitiveData, dst).
 * Otomatis mencatat field mana yang diubah, kapan, dan oleh siapa - ke tabel activity_logs
 * yang sudah ada di sistem - TANPA menyimpan isi/nilai datanya (supaya log sendiri
 * tidak jadi titik kebocoran data sensitif).
 */
trait LogsSensitiveDataChanges
{
    protected static function bootLogsSensitiveDataChanges(): void
    {
        static::created(function ($model) {
            $model->logChange('created', array_keys($model->getAttributes()));
        });

        static::updating(function ($model) {
            $changedFields = array_keys($model->getDirty());
            // Filter cuma field yang benar2 berubah, exclude timestamp/FK biasa
            $changedFields = array_diff($changedFields, ['updated_at', 'created_at']);

            if (!empty($changedFields)) {
                $model->logChange('updated', $changedFields);
            }
        });

        static::deleted(function ($model) {
            $model->logChange('deleted', []);
        });
    }

    protected function logChange(string $action, array $fields): void
    {
        // Nama field "logis" tanpa suffix _encrypted, supaya gampang dibaca di laporan audit
        $cleanFields = array_values(array_map(
            fn ($field) => str_replace('_encrypted', '', $field),
            array_filter($fields, fn ($field) => str_ends_with($field, '_encrypted'))
        ));

        if (empty($cleanFields) && $action !== 'deleted') {
            return; // tidak ada field sensitif yang berubah, skip log
        }

        $ownerForeignKey = $this->getAuditableForeignKey();
        $ownerType = $this->getAuditableType();

        // 1 baris per kejadian save, ditulis ke tabel activity_logs yang sudah ada
        ActivityLog::create([
            'school_id' => $this->school_id,
            'user_id' => Auth::id(),
            'activity' => "sensitive_data.{$action}",
            'description' => json_encode([
                'auditable_type' => $ownerType,
                'auditable_id' => $this->{$ownerForeignKey},
                'fields' => $cleanFields ?: ['*'],
            ]),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Override di model kalau nama foreign key beda (default: student_id/teacher_id dst
     * ditentukan dari nama tabel parent).
     */
    protected function getAuditableForeignKey(): string
    {
        return $this->auditableForeignKey ?? 'student_id';
    }

    protected function getAuditableType(): string
    {
        return $this->auditableType ?? class_basename(static::class);
    }
}