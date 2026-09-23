<?php

return [
    // Alamat encryption-engine (lewat load balancer nginx, BUKAN langsung
    // ke salah satu replica) - biasanya nama service docker-compose + port.
    'host' => env('ENCRYPTION_SERVICE_HOST', 'encryption:50051'),

    // Sama persis dengan AUTH_TOKEN di .env service Rust.
    'api_key' => env('ENCRYPTION_SERVICE_API_KEY'),

    // Aktifkan kalau nginx load balancer sudah pakai TLS (lihat README
    // service Rust bagian TLS - defaultnya sudah TLS begitu certs/
    // di-generate). Set false HANYA untuk development lokal tanpa TLS.
    'tls_enabled' => env('ENCRYPTION_SERVICE_TLS_ENABLED', true),

    // Path ke certs/server.crt hasil generate-certs.sh di service Rust -
    // WAJIB diisi kalau tls_enabled=true, karena sertifikatnya self-signed
    // (tidak otomatis dipercaya sistem).
    'ca_cert_path' => env('ENCRYPTION_SERVICE_CA_CERT_PATH'),

    // Retry dengan exponential backoff - hanya untuk status gRPC transient
    // (RESOURCE_EXHAUSTED, DEADLINE_EXCEEDED, UNAVAILABLE). Status lain
    // (UNAUTHENTICATED, NOT_FOUND, INVALID_ARGUMENT) tidak pernah di-retry
    // karena percobaan ulang tidak akan pernah berhasil.
    'retry' => [
        'max_attempts' => env('ENCRYPTION_SERVICE_RETRY_ATTEMPTS', 3),
        'base_delay_ms' => env('ENCRYPTION_SERVICE_RETRY_BASE_DELAY_MS', 100),
    ],

    // Circuit breaker sederhana berbasis cache - setelah sekian kegagalan
    // UNAVAILABLE beruntun, hentikan percobaan koneksi baru selama cooldown
    // supaya request lain tidak ikut menunggu timeout satu-satu saat
    // service benar-benar down.
    'circuit_breaker' => [
        'failure_threshold' => env('ENCRYPTION_SERVICE_CIRCUIT_THRESHOLD', 5),
        'cooldown_seconds' => env('ENCRYPTION_SERVICE_CIRCUIT_COOLDOWN', 30),
    ],
];