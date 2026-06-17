<?php
// Tambahkan ini di config/database.php
// Di dalam array 'connections', tambahkan setelah koneksi 'pgsql':

'connections' => [

    // ... koneksi lain ...

    'pgsql' => [
        'driver'   => 'pgsql',
        'url'      => env('DATABASE_URL'),
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => 'utf8',
        'prefix'   => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode'  => 'prefer',
    ],

    // ← Tambahan: Finance DB (database beda, user sama)
    'pgsql_finance' => [
        'driver'   => 'pgsql',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '5432'),
        'database' => env('DB_FINANCE_DATABASE', 'finance'),
        'username' => env('DB_FINANCE_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('DB_FINANCE_PASSWORD', env('DB_PASSWORD', '')),
        'charset'  => 'utf8',
        'prefix'   => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode'  => 'prefer',
    ],

],