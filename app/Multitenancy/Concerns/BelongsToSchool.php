<?php

namespace App\Multitenancy\Concerns;

use App\Models\School;
use App\Multitenancy\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait ini di-attach ke semua Model yang punya kolom school_id.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        // Apply global scope — semua query otomatis difilter by school_id
        static::addGlobalScope(new SchoolScope());

        // Otomatis set school_id saat record baru dibuat
        static::creating(function (self $model) {
            if (! $model->school_id) {
                $tenant = School::current();
                if ($tenant) {
                    $model->school_id = $tenant->id;
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
