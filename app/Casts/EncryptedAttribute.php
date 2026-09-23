<?php

namespace App\Casts;

use App\Contracts\EncryptionClientInterface;
use App\Exceptions\SensitiveDataEncryptionException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptedAttribute implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $bundle = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

            return app(EncryptionClientInterface::class)->decrypt(
                cipherText: base64_decode($bundle['cipher_text']),
                iv: base64_decode($bundle['iv']),
                keyId: $bundle['key_id'],
                aad: $key,
            );
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

        try {
            $result = app(EncryptionClientInterface::class)->encrypt($value, aad: $key);

            return json_encode([
                'cipher_text' => base64_encode($result['cipher_text']),
                'iv' => base64_encode($result['iv']),
                'key_id' => $result['key_id'],
            ], JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            report($e);

            throw new SensitiveDataEncryptionException(
                "Gagal menyimpan field '{$key}' — layanan enkripsi tidak tersedia. Data tidak disimpan.",
                previous: $e
            );
        }
    }
}