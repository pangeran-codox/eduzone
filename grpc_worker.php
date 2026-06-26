<?php
/**
 * Worker satu request - dijalankan sebagai proses CLI TERPISAH (bukan pcntl_fork).
 * Ini menghindari masalah grpc C-core yang tidak fork-safe.
 *
 * Output: satu baris JSON ke stdout.
 *
 * Penggunaan:
 *   php grpc_worker.php <id>
 */

require __DIR__ . '/vendor/autoload.php';

use Encryption\EncryptionServiceClient;
use Encryption\EncryptRequest;
use Encryption\DecryptRequest;
use Grpc\ChannelCredentials;

$id   = isset($argv[1]) ? (int) $argv[1] : 0;
$host = getenv('ENCRYPTION_ENGINE_HOST') ?: 'encryption-engine:50051';

$start = microtime(true);
$status = 'OK';
$error = '';

try {
    $client = new EncryptionServiceClient($host, [
        'credentials' => ChannelCredentials::createInsecure(),
    ]);

    $plainText = "test-user-{$id}-" . bin2hex(random_bytes(8));
    $callOptions = ['timeout' => 5 * 1000000]; // 5 detik

    // --- ENCRYPT ---
    $encReq = new EncryptRequest();
    $encReq->setPlainText($plainText);

    [$encResp, $encStatus] = $client->Encrypt($encReq, [], $callOptions)->wait();

    if (!$encStatus || $encStatus->code !== \Grpc\STATUS_OK) {
        $detail = $encStatus->details ?? 'no status (timeout/connection issue)';
        throw new RuntimeException("Encrypt gagal: {$detail}");
    }

    // --- DECRYPT (verifikasi roundtrip) ---
    $decReq = new DecryptRequest();
    $decReq->setCipherText($encResp->getCipherText());
    $decReq->setIv($encResp->getIv());

    [$decResp, $decStatus] = $client->Decrypt($decReq, [], $callOptions)->wait();

    if (!$decStatus || $decStatus->code !== \Grpc\STATUS_OK) {
        $detail = $decStatus->details ?? 'no status (timeout/connection issue)';
        throw new RuntimeException("Decrypt gagal: {$detail}");
    }

    if ($decResp->getPlainText() !== $plainText) {
        throw new RuntimeException("Mismatch! Plain text tidak sama setelah roundtrip.");
    }
} catch (\Throwable $e) {
    $status = 'FAIL';
    $error = $e->getMessage();
}

$duration = round((microtime(true) - $start) * 1000, 2);

echo json_encode([
    'id'       => $id,
    'status'   => $status,
    'duration' => $duration,
    'error'    => $error,
]) . "\n";