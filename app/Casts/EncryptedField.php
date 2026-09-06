<?php

namespace App\Casts;

use App\Contracts\EncryptionClientInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Cast Eloquent supaya field terenkripsi terasa seperti field biasa di
 * model - baca/tulis otomatis lewat encryption-engine, tanpa perlu manggil
 * EncryptionClient manual tiap kali.
 *
 * Pakai di model:
 *
 *   protected $casts = [
 *       'nis' => EncryptedField::class.':nis',
 *   ];
 *
 * Parameter 'nis' di atas menentukan prefix nama kolom (nis_cipher,
 * nis_iv, nis_key_id) - lihat migration contoh.
 *
 * ⚠️ CATATAN PENTING: cast ini memanggil gRPC (network call) setiap kali
 * attribute diakses/di-set. Untuk query dalam jumlah banyak (mis. list
 * 100 siswa), ini berarti 100+ round-trip gRPC terpisah kalau diakses
 * satu-satu - pertimbangkan pakai EncryptionClient::batchDecrypt()
 * langsung di query/collection level untuk kasus seperti itu, bukan
 * mengandalkan cast per-row.
 */
class EncryptedField implements CastsAttributes
{
    public function __construct(private string $prefix)
    {
    }

    public function get($model, string $key, $value, array $attributes): ?string
    {
        $cipherText = $attributes["{$this->prefix}_cipher"] ?? null;
        $iv = $attributes["{$this->prefix}_iv"] ?? null;
        $keyId = $attributes["{$this->prefix}_key_id"] ?? null;

        if ($cipherText === null || $iv === null || $keyId === null) {
            return null;
        }

        return app(EncryptionClientInterface::class)->decrypt($cipherText, $iv, $keyId);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [
                "{$this->prefix}_cipher" => null,
                "{$this->prefix}_iv" => null,
                "{$this->prefix}_key_id" => null,
            ];
        }

        // AAD diisi nama kolom - mengikat ciphertext ke konteks ini,
        // supaya tidak bisa "dipindah" diam-diam ke kolom lain (lihat
        // README service Rust bagian Associated Data).
        $result = app(EncryptionClientInterface::class)->encrypt($value, aad: $this->prefix);

        return [
            "{$this->prefix}_cipher" => $result['cipher_text'],
            "{$this->prefix}_iv" => $result['iv'],
            "{$this->prefix}_key_id" => $result['key_id'],
        ];
    }
}
