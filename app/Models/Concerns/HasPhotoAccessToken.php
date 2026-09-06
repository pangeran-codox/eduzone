<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generate token opaque tiap kolom `photo` berubah, dipakai
 * PersonPhotoController untuk serving foto tanpa URL publik yang
 * bisa ditebak. Token lama otomatis "mati" (nggak disimpan di mana
 * pun) begitu foto diganti — nggak perlu revoke manual.
 */
trait HasPhotoAccessToken
{
    protected static function bootHasPhotoAccessToken(): void
    {
        static::saving(function ($model) {
            if ($model->isDirty('photo')) {
                $model->photo_access_token = $model->photo ? Str::random(40) : null;
            }
        });
    }
}