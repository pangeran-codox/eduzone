<?php

namespace App\Models\Concerns;

use App\Contracts\EncryptionClientInterface;
use App\Exceptions\SensitiveDataEncryptionException;

/**
 * Proxy transparan __get/__set untuk field terenkripsi, disimpan sebagai
 * 1 kolom JSON per field ({field}_encrypted, tipe jsonb) berisi:
 *   {"cipher_text": "<base64>", "iv": "<base64>", "key_id": "..."}
 *
 * cipher_text/iv dari EncryptionClient adalah BYTES BINER MENTAH (bukan
 * valid UTF-8) - wajib base64 sebelum masuk JSON, base64-decode balik
 * sebelum dikirim ke gRPC decrypt. Dikonfirmasi tim Rust 3 Sep 2026.
 *
 * aad diisi otomatis dari nama field logis (mis. "nisn") - validasinya
 * melekat di algoritma AES-GCM sisi Rust sendiri (AEAD), bukan cek kode
 * terpisah - kalau field tertukar, decrypt PASTI gagal secara kriptografis.
 *
 * Class yang pakai trait ini WAJIB deklarasikan:
 *   protected static array $encryptedFields = ['nisn', 'address', ...];
 */
trait EncryptsViaGrpcService
{
    public function __get($key)
    {
        if (in_array($key, static::$encryptedFields, true)) {
            return $this->getDecrypted($key);
        }

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        if (in_array($key, static::$encryptedFields, true)) {
            $this->setEncrypted($key, $value);
            return;
        }

        parent::__set($key, $value);
    }

    protected function getDecrypted(string $field): ?string
    {
        $column = "{$field}_encrypted";
        $raw = $this->attributes[$column] ?? null;

        if (empty($raw)) {
            return null;
        }

        try {
            $bundle = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

            return app(EncryptionClientInterface::class)->decrypt(
                cipherText: base64_decode($bundle['cipher_text']),
                iv: base64_decode($bundle['iv']),
                keyId: $bundle['key_id'],
                aad: $field,
            );
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * SENGAJA bikin save() gagal (lempar exception) kalau gRPC gagal,
     * bukan diam-diam simpan null - kehilangan data sensitif secara
     * diam-diam lebih berbahaya daripada save gagal dengan pesan jelas.
     */
    protected function setEncrypted(string $field, ?string $value): void
    {
        $column = "{$field}_encrypted";

        if ($value === null || $value === '') {
            $this->attributes[$column] = null;
            return;
        }

        try {
            $result = app(EncryptionClientInterface::class)->encrypt($value, aad: $field);

            $this->attributes[$column] = json_encode([
                'cipher_text' => base64_encode($result['cipher_text']),
                'iv' => base64_encode($result['iv']),
                'key_id' => $result['key_id'],
            ], JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            report($e);

            throw new SensitiveDataEncryptionException(
                "Gagal menyimpan field '{$field}' — layanan enkripsi sedang tidak tersedia. Data tidak disimpan untuk mencegah kehilangan data secara diam-diam.",
                previous: $e
            );
        }
    }
}