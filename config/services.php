<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Integrasi dengan absensi-gateway (Go). jwt_secret dipakai
    // GatewayTokenIssuer buat menerbitkan token guru (harus SAMA PERSIS
    // dengan JWT_SECRET di .env gateway). sync_token dipakai VerifySyncToken
    // buat verifikasi header X-Sync-Token dari gateway (harus SAMA PERSIS
    // dengan LARAVEL_SYNC_TOKEN di .env gateway) - lihat
    // docs/laravel-sync-contract.md di repo absensi-gateway.
    'absensi_gateway' => [
        'jwt_secret' => env('ABSENSI_GATEWAY_JWT_SECRET'),
        'jwt_ttl' => env('ABSENSI_GATEWAY_JWT_TTL', 900),
        'sync_token' => env('ABSENSI_SYNC_TOKEN'), // <- sudah ada sebelumnya
        'base_url'   => env('ABSENSI_GATEWAY_BASE_URL', 'http://absensi-gateway:8080'), // <- BARU
    ],

];