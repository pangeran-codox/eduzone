<?php

namespace Tests\Unit\Casts;

use App\Casts\EncryptedAttribute;
use App\Contracts\EncryptionClientInterface;
use App\Exceptions\SensitiveDataEncryptionException;
use Tests\TestCase;

class EncryptedAttributeTest extends TestCase
{
    private EncryptedAttribute $cast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new EncryptedAttribute();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_set_null_returns_null(): void
    {
        $this->assertNull($this->cast->set(null, 'topic', null, []));
    }

    public function test_set_empty_string_returns_null(): void
    {
        $this->assertNull($this->cast->set(null, 'topic', '', []));
    }

    public function test_get_null_returns_null(): void
    {
        $this->assertNull($this->cast->get(null, 'topic', null, []));
    }

        public function test_set_encrypts_via_interface_with_field_name_as_aad(): void
    {
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('encrypt')
            ->once()
            ->with('halo dunia', 'topic')
            ->andReturn([
                'cipher_text' => 'CIPHERTEXT',
                'iv' => 'IVVALUE',
                'key_id' => 'default',
            ]);
        app()->instance(EncryptionClientInterface::class, $client);

        $result = $this->cast->set(null, 'topic', 'halo dunia', []);
        $bundle = json_decode($result, true);

        // Cast meng-base64-encode cipher_text/iv sebelum disimpan (keduanya
        // bytes biner mentah dari client) - lihat App\Casts\EncryptedAttribute::set().
        $this->assertSame(base64_encode('CIPHERTEXT'), $bundle['cipher_text']);
        $this->assertSame(base64_encode('IVVALUE'), $bundle['iv']);
        $this->assertSame('default', $bundle['key_id']);
    }

        public function test_get_decrypts_bundle_via_interface_with_field_name_as_aad(): void
    {
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('decrypt')
            ->once()
            ->with('CIPHERTEXT', 'IVVALUE', 'default', 'topic')
            ->andReturn('halo dunia');
        app()->instance(EncryptionClientInterface::class, $client);

        // Bundle disimpan dengan cipher_text/iv sudah di-base64-encode
        // (bytes biner mentah) - cast get() men-decode balik sebelum
        // kirim ke client, jadi bundle di sini harus base64 juga.
        $bundle = json_encode([
            'cipher_text' => base64_encode('CIPHERTEXT'),
            'iv' => base64_encode('IVVALUE'),
            'key_id' => 'default',
        ]);

        $result = $this->cast->get(null, 'topic', $bundle, []);

        $this->assertSame('halo dunia', $result);
    }

    public function test_get_returns_null_when_aad_mismatch_throws(): void
    {
        // AAD divalidasi kriptografis oleh AES-GCM di sisi Rust - kalau
        // field tertukar (mis. bundle 'topic' dibaca sebagai 'result'),
        // client akan melempar exception. Cast harus menelan ini jadi null,
        // bukan meledak ke pemanggil.
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('decrypt')
            ->once()
            ->andThrow(new \RuntimeException('AAD mismatch'));
        app()->instance(EncryptionClientInterface::class, $client);

        $bundle = json_encode([
            'cipher_text' => 'CIPHERTEXT',
            'iv' => 'IVVALUE',
            'key_id' => 'default',
        ]);

        $result = $this->cast->get(null, 'result', $bundle, []);

        $this->assertNull($result);
    }

    public function test_get_returns_null_for_invalid_json(): void
    {
        // Data lama/rusak yang bukan JSON bundle valid (mis. plaintext
        // sisa sebelum cast diperbaiki) tidak boleh membuat halaman error.
        $result = $this->cast->get(null, 'topic', 'bukan json valid', []);

        $this->assertNull($result);
    }

    public function test_set_throws_sensitive_data_exception_when_client_fails(): void
    {
        // set() SENGAJA gagal jelas kalau gRPC error, bukan diam-diam
        // simpan null - kehilangan data sensitif secara diam-diam lebih
        // berbahaya daripada save gagal dengan pesan jelas.
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('encrypt')
            ->once()
            ->andThrow(new \RuntimeException('service down'));
        app()->instance(EncryptionClientInterface::class, $client);

        $this->expectException(SensitiveDataEncryptionException::class);

        $this->cast->set(null, 'topic', 'halo dunia', []);
    }
}