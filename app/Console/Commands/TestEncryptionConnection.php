<?php

namespace App\Console\Commands;

use App\Contracts\EncryptionClientInterface;
use Illuminate\Console\Command;

/**
 * Sanity check cepat setelah instalasi/setup - jalankan `php artisan
 * encryption:test` untuk memastikan autoload, koneksi network, TLS, dan
 * API key semuanya benar SEBELUM dipakai di kode aplikasi yang lebih besar.
 */
class TestEncryptionConnection extends Command
{
    protected $signature = 'encryption:test';

    protected $description = 'Tes koneksi ke encryption-engine: health check + round-trip encrypt/decrypt';

    public function handle(EncryptionClientInterface $client): int
    {
        $this->info('Mengecek health check...');
        if (!$client->isHealthy()) {
            $this->error('Encryption service TIDAK bisa dihubungi. Cek: ENCRYPTION_SERVICE_HOST, network docker, TLS cert, AUTH_TOKEN/API key.');
            return self::FAILURE;
        }
        $this->info('Health check OK.');

        $this->info('Mengecek round-trip encrypt -> decrypt...');
        $original = 'test-' . uniqid();

        try {
            $encrypted = $client->encrypt($original);
        } catch (\Throwable $e) {
            $this->error("Encrypt gagal: {$e->getMessage()}");
            return self::FAILURE;
        }

        try {
            $decrypted = $client->decrypt(
                $encrypted['cipher_text'],
                $encrypted['iv'],
                $encrypted['key_id']
            );
        } catch (\Throwable $e) {
            $this->error("Decrypt gagal: {$e->getMessage()}");
            return self::FAILURE;
        }

        if ($decrypted !== $original) {
            $this->error('Round-trip GAGAL: hasil decrypt tidak cocok dengan input asli.');
            return self::FAILURE;
        }

        $this->info('OK - koneksi & encrypt/decrypt round-trip berhasil.');
        $this->line("  key_id yang dipakai: {$encrypted['key_id']}");
        return self::SUCCESS;
    }
}
