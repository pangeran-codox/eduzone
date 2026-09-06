<?php

namespace App\Services;

use App\Contracts\EncryptionClientInterface;
use Encryption\BatchDecryptRequest;
use Encryption\BatchEncryptRequest;
use Encryption\DecryptRequest;
use Encryption\EncryptionServiceClient as GrpcClient;
use Encryption\EncryptRequest;
use Encryption\HealthCheckRequest;
use RuntimeException;

/**
 * Wrapper Laravel-friendly di atas gRPC client mentah. Menangani koneksi,
 * header autentikasi (x-api-key), dan menerjemahkan status gRPC jadi
 * exception PHP yang jelas.
 *
 * Daftarkan lewat interface-nya di AppServiceProvider (bukan class ini
 * langsung), supaya gampang di-mock di test:
 *
 *   $this->app->singleton(EncryptionClientInterface::class, EncryptionClient::class);
 */
class EncryptionClient implements EncryptionClientInterface
{
    private GrpcClient $client;

    public function __construct()
    {
        $credentials = config('encryption_service.tls_enabled')
            ? \Grpc\ChannelCredentials::createSsl(
                file_get_contents(config('encryption_service.ca_cert_path'))
            )
            : \Grpc\ChannelCredentials::createInsecure();

        $this->client = new GrpcClient(
            config('encryption_service.host'),
            ['credentials' => $credentials]
        );
    }

    /**
     * Enkripsi satu nilai. $aad opsional - dipakai untuk mengikat konteks
     * (mis. "users.email") ke ciphertext, lihat README service Rust untuk
     * detail.
     *
     * @return array{cipher_text: string, iv: string, key_id: string}
     */
    public function encrypt(string $plainText, string $aad = ''): array
    {
        $request = new EncryptRequest();
        $request->setPlainText($plainText);
        $request->setAad($aad);

        [$response, $status] = $this->client
            ->Encrypt($request, $this->metadata())
            ->wait();

        $this->assertOk($status, 'Encrypt');

        return [
            'cipher_text' => $response->getCipherText(),
            'iv' => $response->getIv(),
            'key_id' => $response->getKeyId(),
        ];
    }

    /**
     * Dekripsi satu nilai. $keyId WAJIB persis sama dengan yang didapat
     * saat encrypt() data ini dulu - simpan bareng cipher_text+iv di
     * database (3 kolom, bukan 1 - lihat migration contoh).
     */
    public function decrypt(string $cipherText, string $iv, string $keyId, string $aad = ''): string
    {
        $request = new DecryptRequest();
        $request->setCipherText($cipherText);
        $request->setIv($iv);
        $request->setKeyId($keyId);
        $request->setAad($aad);

        [$response, $status] = $this->client
            ->Decrypt($request, $this->metadata())
            ->wait();

        $this->assertOk($status, 'Decrypt');

        return $response->getPlainText();
    }

    /**
     * Enkripsi banyak nilai sekaligus dalam satu round-trip gRPC - pakai
     * ini kalau butuh enkripsi banyak kolom untuk satu record, bukan
     * panggil encrypt() berkali-kali dalam loop (tiap panggilan = 1
     * round-trip network terpisah).
     *
     * @param array<array{plain_text: string, aad?: string}> $items
     * @return array<array{cipher_text: string, iv: string, key_id: string}>
     */
    public function batchEncrypt(array $items): array
    {
        $request = new BatchEncryptRequest();
        $grpcItems = array_map(function (array $item) {
            $r = new EncryptRequest();
            $r->setPlainText($item['plain_text']);
            $r->setAad($item['aad'] ?? '');
            return $r;
        }, $items);
        $request->setItems($grpcItems);

        [$response, $status] = $this->client
            ->BatchEncrypt($request, $this->metadata())
            ->wait();

        $this->assertOk($status, 'BatchEncrypt');

        $results = [];
        foreach ($response->getItems() as $item) {
            $results[] = [
                'cipher_text' => $item->getCipherText(),
                'iv' => $item->getIv(),
                'key_id' => $item->getKeyId(),
            ];
        }
        return $results;
    }

    /**
     * @param array<array{cipher_text: string, iv: string, key_id: string, aad?: string}> $items
     * @return string[]
     */
    public function batchDecrypt(array $items): array
    {
        $request = new BatchDecryptRequest();
        $grpcItems = array_map(function (array $item) {
            $r = new DecryptRequest();
            $r->setCipherText($item['cipher_text']);
            $r->setIv($item['iv']);
            $r->setKeyId($item['key_id']);
            $r->setAad($item['aad'] ?? '');
            return $r;
        }, $items);
        $request->setItems($grpcItems);

        [$response, $status] = $this->client
            ->BatchDecrypt($request, $this->metadata())
            ->wait();

        $this->assertOk($status, 'BatchDecrypt');

        $results = [];
        foreach ($response->getItems() as $item) {
            $results[] = $item->getPlainText();
        }
        return $results;
    }

    /**
     * Cek service masih hidup. Cocok dipakai di health check route Laravel
     * sendiri, atau scheduled command untuk monitoring.
     */
    public function isHealthy(): bool
    {
        try {
            [$response, $status] = $this->client
                ->HealthCheck(new HealthCheckRequest(), $this->metadata())
                ->wait();

            return $status->code === \Grpc\STATUS_OK && $response->getOk();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function metadata(): array
    {
        return ['x-api-key' => [config('encryption_service.api_key')]];
    }

    private function assertOk($status, string $method): void
    {
        if ($status->code === \Grpc\STATUS_OK) {
            return;
        }

        // Terjemahkan status gRPC yang paling sering muncul jadi pesan yang
        // jelas - cocokkan dengan status yang benar-benar dikembalikan
        // service Rust (lihat README service: ResourceExhausted untuk rate
        // limit/concurrency limit, DeadlineExceeded untuk timeout,
        // Unauthenticated untuk x-api-key salah, NotFound untuk key_id
        // yang tidak dikenal).
        $message = match ($status->code) {
            \Grpc\STATUS_RESOURCE_EXHAUSTED => "Encryption service sedang sibuk (rate/concurrency limit), coba lagi sesaat lagi",
            \Grpc\STATUS_DEADLINE_EXCEEDED => "Encryption service timeout memproses request",
            \Grpc\STATUS_UNAUTHENTICATED => "Autentikasi ke encryption service gagal - cek ENCRYPTION_SERVICE_API_KEY",
            \Grpc\STATUS_NOT_FOUND => "key_id tidak dikenal encryption service - kunci sudah dipensiunkan?",
            \Grpc\STATUS_UNAVAILABLE => "Encryption service tidak bisa dihubungi",
            default => $status->details,
        };

        throw new RuntimeException("Encryption service [{$method}] gagal: {$message}", $status->code);
    }
}
