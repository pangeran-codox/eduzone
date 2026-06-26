<?php

namespace App\Services;

use Encryption\EncryptionServiceClient;
use Encryption\EncryptRequest;
use Encryption\DecryptRequest;
use Grpc\ChannelCredentials;
use RuntimeException;

class EncryptionGrpcService
{
    protected EncryptionServiceClient $client;

    /** Timeout per call dalam microseconds (default 5 detik) */
    protected int $timeoutMicros;

    public function __construct(?string $host = null, ?int $timeoutMicros = null)
    {
        $host = $host ?? config('services.encryption_engine.host', env('ENCRYPTION_ENGINE_HOST', 'encryption-engine:50051'));
        $this->timeoutMicros = $timeoutMicros ?? 5 * 1000000;

        $this->client = new EncryptionServiceClient($host, [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);
    }

    /**
     * Enkripsi plain text, hasilnya berupa string siap-simpan (base64-encoded "iv:ciphertext").
     */
    public function encrypt(string $plainText): string
    {
        $request = new EncryptRequest();
        $request->setPlainText($plainText);

        [$response, $status] = $this->client
            ->Encrypt($request, [], ['timeout' => $this->timeoutMicros])
            ->wait();

        $this->assertOk($status, 'Encrypt');

        // Gabungkan iv + cipher_text jadi satu string supaya mudah disimpan di 1 kolom DB.
        // Format: base64(iv) . ':' . base64(cipher_text)
        $iv = base64_encode($response->getIv());
        $cipherText = base64_encode($response->getCipherText());

        return "{$iv}:{$cipherText}";
    }

    /**
     * Dekripsi string hasil encrypt() di atas, balik jadi plain text asli.
     */
    public function decrypt(string $encoded): string
    {
        $parts = explode(':', $encoded, 2);

        if (count($parts) !== 2) {
            throw new RuntimeException('Format data terenkripsi tidak valid (expected "iv:cipher_text").');
        }

        [$ivB64, $cipherTextB64] = $parts;

        $request = new DecryptRequest();
        $request->setIv(base64_decode($ivB64));
        $request->setCipherText(base64_decode($cipherTextB64));

        [$response, $status] = $this->client
            ->Decrypt($request, [], ['timeout' => $this->timeoutMicros])
            ->wait();

        $this->assertOk($status, 'Decrypt');

        return $response->getPlainText();
    }

    /**
     * Helper internal: lempar exception kalau gRPC status bukan OK.
     */
    protected function assertOk($status, string $operation): void
    {
        if (!$status || $status->code !== \Grpc\STATUS_OK) {
            $detail = $status->details ?? 'tidak ada detail (kemungkinan timeout/koneksi gagal)';
            throw new RuntimeException("gRPC {$operation} gagal: {$detail}");
        }
    }
}