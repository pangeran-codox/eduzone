<?php

namespace App\Casts;

use App\Services\EncryptionGrpcService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Cast untuk kolom yang disimpan terenkripsi LANGSUNG di kolomnya sendiri
 * (beda dengan pola Student/Teacher/Staff yang pisah ke tabel *_sensitive_data).
 *
 * Cocok untuk kolom pada tabel dengan relasi 1-to-many (misal catatan per sesi/baris),
 * yang tidak butuh searchable dan tidak ada concern row-multiplication.
 *
 * Penggunaan di Model:
 *   protected $casts = [
 *       'topic' => EncryptedAttribute::class,
 *       'result' => EncryptedAttribute::class,
 *   ];
 */
class EncryptedAttribute implements CastsAttributes
{
    protected static ?EncryptionGrpcService $encryptionService = null;

    protected static function service(): EncryptionGrpcService
    {
        return static::$encryptionService ??= new EncryptionGrpcService();
    }

    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return static::service()->decrypt($value);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return static::service()->encrypt($value);
    }
}
