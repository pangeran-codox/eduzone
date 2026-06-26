<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Encryption;

/**
 * Service untuk enkripsi data
 */
class EncryptionServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Encryption\EncryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Encrypt(\Encryption\EncryptRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/encryption.EncryptionService/Encrypt',
        $argument,
        ['\Encryption\EncryptResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Encryption\DecryptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Decrypt(\Encryption\DecryptRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/encryption.EncryptionService/Decrypt',
        $argument,
        ['\Encryption\DecryptResponse', 'decode'],
        $metadata, $options);
    }

}
