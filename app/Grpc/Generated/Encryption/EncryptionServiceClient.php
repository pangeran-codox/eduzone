<?php
// GENERATED CODE -- ditulis manual mengikuti pola output resmi
// `protoc-gen-grpc-php` (grpc_php_plugin), karena binary plugin itu tidak
// tersedia di sandbox yang dipakai membuat paket ini. SANGAT DISARANKAN
// generate ulang file ini di mesin kamu begitu `protoc` + `grpc_php_plugin`
// terpasang, untuk memastikan byte-for-byte sama dengan output resmi:
//
//   protoc --php_out=app/Grpc/Generated \
//          --grpc_out=app/Grpc/Generated \
//          --plugin=protoc-gen-grpc=$(which grpc_php_plugin) \
//          -I proto proto/encryption.proto
//
// Isi file ini seharusnya identik/sangat mirip dengan yang dihasilkan
// command di atas, karena pola generated client gRPC PHP sangat mekanis.

namespace Encryption;

/**
 * Service untuk enkripsi data (dipanggil oleh service backend, mis. Laravel)
 */
class EncryptionServiceClient extends \Grpc\BaseStub
{
    /**
     * @param string $hostname hostname:port service, mis. "encryption:50051"
     * @param array $opts channel options, HARUS termasuk 'credentials'
     *              (lihat app/Services/EncryptionClient.php untuk contoh
     *              pakai ChannelCredentials::createSsl untuk TLS)
     * @param \Grpc\Channel $channel (opsional) reuse channel yang sudah ada
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Encryption\EncryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Encrypt(\Encryption\EncryptRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/encryption.EncryptionService/Encrypt',
            $argument,
            ['\Encryption\EncryptResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Encryption\DecryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Decrypt(\Encryption\DecryptRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/encryption.EncryptionService/Decrypt',
            $argument,
            ['\Encryption\DecryptResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Encryption\BatchEncryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BatchEncrypt(\Encryption\BatchEncryptRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/encryption.EncryptionService/BatchEncrypt',
            $argument,
            ['\Encryption\BatchEncryptResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Encryption\BatchDecryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BatchDecrypt(\Encryption\BatchDecryptRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/encryption.EncryptionService/BatchDecrypt',
            $argument,
            ['\Encryption\BatchDecryptResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Encryption\HealthCheckRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function HealthCheck(\Encryption\HealthCheckRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/encryption.EncryptionService/HealthCheck',
            $argument,
            ['\Encryption\HealthCheckResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
