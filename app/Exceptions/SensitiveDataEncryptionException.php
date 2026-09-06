<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat EncryptionGrpcService gagal encrypt field sensitif
 * (misal: extension grpc/protobuf belum aktif, atau service Rust down).
 * SENGAJA bikin save() gagal daripada diam-diam simpan null — kehilangan
 * data sensitif secara diam-diam lebih berbahaya daripada save gagal jelas.
 */
class SensitiveDataEncryptionException extends RuntimeException
{
}