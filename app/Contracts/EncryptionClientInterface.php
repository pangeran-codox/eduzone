<?php

namespace App\Contracts;

/**
 * Kontrak EncryptionClient - bind ini (bukan class konkretnya langsung)
 * di service container, supaya test bisa mock tanpa menyentuh gRPC
 * sungguhan. Lihat README bagian "Testing/mocking".
 */
interface EncryptionClientInterface
{
    /**
     * @return array{cipher_text: string, iv: string, key_id: string}
     */
    public function encrypt(string $plainText, string $aad = ''): array;

    public function decrypt(string $cipherText, string $iv, string $keyId, string $aad = ''): string;

    /**
     * @param array<array{plain_text: string, aad?: string}> $items
     * @return array<array{cipher_text: string, iv: string, key_id: string}>
     */
    public function batchEncrypt(array $items): array;

    /**
     * @param array<array{cipher_text: string, iv: string, key_id: string, aad?: string}> $items
     * @return string[]
     */
    public function batchDecrypt(array $items): array;

    public function isHealthy(): bool;
}
