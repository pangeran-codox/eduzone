<?php

namespace App\Services;

use App\Contracts\EncryptionClientInterface;
use Encryption\BatchDecryptRequest;
use Encryption\BatchEncryptRequest;
use Encryption\DecryptRequest;
use Encryption\EncryptionServiceClient as GrpcClient;
use Encryption\EncryptRequest;
use Encryption\HealthCheckRequest;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Wrapper Laravel-friendly di atas gRPC client mentah. Menangani koneksi,
 * header autentikasi (x-api-key), retry dengan backoff untuk status
 * transient, circuit breaker sederhana, dan menerjemahkan status gRPC
 * jadi exception PHP yang jelas.
 *
 * Daftarkan lewat interface-nya di AppServiceProvider (bukan class ini
 * langsung), supaya gampang di-mock di test:
 *
 *   $this->app->singleton(EncryptionClientInterface::class, EncryptionClient::class);
 */
class EncryptionClient implements EncryptionClientInterface
{
    private GrpcClient $client;

    /** Status gRPC yang masuk akal untuk di-retry (transient, bukan bug pemanggil). */
    private const RETRYABLE_STATUSES = [
        \Grpc\STATUS_RESOURCE_EXHAUSTED,
        \Grpc\STATUS_DEADLINE_EXCEEDED,
        \Grpc\STATUS_UNAVAILABLE,
    ];

    private const CIRCUIT_CACHE_KEY = 'encryption_client:circuit_open_until';
    private const CIRCUIT_FAILURE_KEY = 'encryption_client:consecutive_failures';

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
     * @return array{cipher_text: string, iv: string, key_id: string}
     */
    public function encrypt(string $plainText, string $aad = ''): array
    {
        return $this->withRetryAndCircuitBreaker('Encrypt', function () use ($plainText, $aad) {
            $request = new EncryptRequest();
            $request->setPlainText($plainText);
            $request->setAad($aad);

            [$response, $status] = $this->client->Encrypt($request, $this->metadata())->wait();
            $this->assertOk($status, 'Encrypt');

            return [
                'cipher_text' => $response->getCipherText(),
                'iv' => $response->getIv(),
                'key_id' => $response->getKeyId(),
            ];
        });
    }

    public function decrypt(string $cipherText, string $iv, string $keyId, string $aad = ''): string
    {
        return $this->withRetryAndCircuitBreaker('Decrypt', function () use ($cipherText, $iv, $keyId, $aad) {
            $request = new DecryptRequest();
            $request->setCipherText($cipherText);
            $request->setIv($iv);
            $request->setKeyId($keyId);
            $request->setAad($aad);

            [$response, $status] = $this->client->Decrypt($request, $this->metadata())->wait();
            $this->assertOk($status, 'Decrypt');

            return $response->getPlainText();
        });
    }

    /**
     * @param array<array{plain_text: string, aad?: string}> $items
     * @return array<array{cipher_text: string, iv: string, key_id: string}>
     */
    public function batchEncrypt(array $items): array
    {
        return $this->withRetryAndCircuitBreaker('BatchEncrypt', function () use ($items) {
            $request = new BatchEncryptRequest();
            $grpcItems = array_map(function (array $item) {
                $r = new EncryptRequest();
                $r->setPlainText($item['plain_text']);
                $r->setAad($item['aad'] ?? '');
                return $r;
            }, $items);
            $request->setItems($grpcItems);

            [$response, $status] = $this->client->BatchEncrypt($request, $this->metadata())->wait();
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
        });
    }

    /**
     * @param array<array{cipher_text: string, iv: string, key_id: string, aad?: string}> $items
     * @return string[]
     */
    public function batchDecrypt(array $items): array
    {
        return $this->withRetryAndCircuitBreaker('BatchDecrypt', function () use ($items) {
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

            [$response, $status] = $this->client->BatchDecrypt($request, $this->metadata())->wait();
            $this->assertOk($status, 'BatchDecrypt');

            $results = [];
            foreach ($response->getItems() as $item) {
                $results[] = $item->getPlainText();
            }
            return $results;
        });
    }

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

    /**
     * Bungkus satu panggilan gRPC dengan circuit breaker + retry backoff.
     * Circuit breaker dicek dulu sebelum retry loop supaya tidak membombardir
     * service yang sedang down dengan percobaan baru.
     */
    private function withRetryAndCircuitBreaker(string $method, callable $call): mixed
    {
        $this->assertCircuitClosed($method);

        $maxAttempts = (int) config('encryption_service.retry.max_attempts', 3);
        $baseDelayMs = (int) config('encryption_service.retry.base_delay_ms', 100);

        $attempt = 0;
        beginAttempt:
        $attempt++;

        try {
            $result = $call();
            $this->recordSuccess();
            return $result;
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if (in_array($statusCode, self::RETRYABLE_STATUSES, true)) {
                if ($statusCode === \Grpc\STATUS_UNAVAILABLE) {
                    $this->recordFailure();
                }

                if ($attempt < $maxAttempts) {
                    $delayMs = $baseDelayMs * (2 ** ($attempt - 1));
                    usleep($delayMs * 1000);
                    goto beginAttempt;
                }
            }

            throw $e;
        }
    }

    /**
     * Circuit breaker sederhana berbasis cache: setelah N kegagalan UNAVAILABLE
     * beruntun, hentikan percobaan koneksi baru selama cooldown singkat -
     * supaya request lain tidak ikut menunggu timeout satu-satu saat service
     * benar-benar down.
     */
    private function assertCircuitClosed(string $method): void
    {
        $openUntil = Cache::get(self::CIRCUIT_CACHE_KEY);

        if ($openUntil !== null && now()->timestamp < $openUntil) {
            throw new RuntimeException(
                "Encryption service [{$method}] dilewati - circuit breaker terbuka karena kegagalan beruntun, coba lagi sesaat lagi",
                \Grpc\STATUS_UNAVAILABLE
            );
        }
    }

    private function recordFailure(): void
    {
        $threshold = (int) config('encryption_service.circuit_breaker.failure_threshold', 5);
        $cooldownSeconds = (int) config('encryption_service.circuit_breaker.cooldown_seconds', 30);

        $failures = Cache::increment(self::CIRCUIT_FAILURE_KEY);

        if ($failures >= $threshold) {
            Cache::put(self::CIRCUIT_CACHE_KEY, now()->addSeconds($cooldownSeconds)->timestamp, $cooldownSeconds);
            Cache::forget(self::CIRCUIT_FAILURE_KEY);
        }
    }

    private function recordSuccess(): void
    {
        Cache::forget(self::CIRCUIT_FAILURE_KEY);
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

        $message = match ($status->code) {
            \Grpc\STATUS_RESOURCE_EXHAUSTED => "Encryption service sedang sibuk (rate/concurrency limit), coba lagi sesaat lagi",
            \Grpc\STATUS_DEADLINE_EXCEEDED => "Encryption service timeout memproses request",
            \Grpc\STATUS_UNAUTHENTICATED => "Autentikasi ke encryption service gagal - cek ENCRYPTION_SERVICE_API_KEY",
            \Grpc\STATUS_NOT_FOUND => "key_id tidak dikenal encryption service - kunci sudah dipensiunkan?",
            \Grpc\STATUS_UNAVAILABLE => "Encryption service tidak bisa dihubungi",
            \Grpc\STATUS_INVALID_ARGUMENT => "Request ke encryption service tidak valid (ukuran data/batch atau IV salah) - ini bug di pemanggil, bukan masalah koneksi",
            \Grpc\STATUS_INTERNAL => "Encryption service gagal memproses (kemungkinan AAD atau key_id tidak cocok saat decrypt)",
            default => $status->details,
        };

        throw new RuntimeException("Encryption service [{$method}] gagal: {$message}", $status->code);
    }
}