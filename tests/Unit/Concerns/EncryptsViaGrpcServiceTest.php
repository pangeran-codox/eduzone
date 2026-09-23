<?php

namespace Tests\Unit\Concerns;

use App\Contracts\EncryptionClientInterface;
use App\Exceptions\SensitiveDataEncryptionException;
use App\Models\Concerns\EncryptsViaGrpcService;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class DummySensitiveModel extends Model
{
    use EncryptsViaGrpcService;

    protected static array $encryptedFields = ['nisn'];
    protected $guarded = [];
}

class EncryptsViaGrpcServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_set_encrypts_and_stores_json_bundle_with_field_as_aad(): void
    {
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('encrypt')
            ->once()
            ->with('9987830710', 'nisn')
            ->andReturn([
                'cipher_text' => 'RAWBYTES',
                'iv' => 'RAWIV',
                'key_id' => 'default',
            ]);
        app()->instance(EncryptionClientInterface::class, $client);

        $model = new DummySensitiveModel();
        $model->nisn = '9987830710';

        $bundle = json_decode($model->getAttributes()['nisn_encrypted'], true);

        // cipher_text/iv adalah bytes biner mentah dari client, wajib
        // base64 sebelum masuk JSON - lihat docblock trait.
        $this->assertSame(base64_encode('RAWBYTES'), $bundle['cipher_text']);
        $this->assertSame(base64_encode('RAWIV'), $bundle['iv']);
        $this->assertSame('default', $bundle['key_id']);
    }

    public function test_get_decrypts_stored_bundle_with_field_as_aad(): void
    {
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('decrypt')
            ->once()
            ->with('RAWBYTES', 'RAWIV', 'default', 'nisn')
            ->andReturn('9987830710');
        app()->instance(EncryptionClientInterface::class, $client);

        $model = new DummySensitiveModel();
        $model->setRawAttributes([
            'nisn_encrypted' => json_encode([
                'cipher_text' => base64_encode('RAWBYTES'),
                'iv' => base64_encode('RAWIV'),
                'key_id' => 'default',
            ]),
        ]);

        $this->assertSame('9987830710', $model->nisn);
    }

    public function test_get_returns_null_when_column_empty(): void
    {
        $model = new DummySensitiveModel();

        $this->assertNull($model->nisn);
    }

    public function test_set_null_stores_null_without_calling_client(): void
    {
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldNotReceive('encrypt');
        app()->instance(EncryptionClientInterface::class, $client);

        $model = new DummySensitiveModel();
        $model->nisn = null;

        $this->assertNull($model->getAttributes()['nisn_encrypted']);
    }

    public function test_set_throws_and_does_not_store_when_client_fails(): void
    {
        // Beda dari cast get() - setEncrypted() SENGAJA melempar exception,
        // bukan diam-diam simpan null. Kehilangan data sensitif secara
        // diam-diam lebih berbahaya daripada save gagal dengan pesan jelas.
        $client = \Mockery::mock(EncryptionClientInterface::class);
        $client->shouldReceive('encrypt')
            ->once()
            ->andThrow(new \RuntimeException('service down'));
        app()->instance(EncryptionClientInterface::class, $client);

        $model = new DummySensitiveModel();

        $this->expectException(SensitiveDataEncryptionException::class);

        $model->nisn = '9987830710';
    }
}