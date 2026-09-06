<?php

namespace App\Providers;

use App\Contracts\EncryptionClientInterface;
use App\Services\EncryptionClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding interface (bukan class konkret langsung) supaya gampang
        // di-mock di test - lihat README paket integrasi encryption-engine,
        // bagian "Testing/mocking".
        $this->app->singleton(
            EncryptionClientInterface::class,
            EncryptionClient::class
        );
    }

    public function boot(): void
    {
        // Pastikan PostgreSQL selalu pakai schema public
        // Ini mencegah search_path berubah saat Spatie Multitenancy
        // melakukan tenant switching
        if (config('database.default') === 'pgsql') {
            DB::listen(function ($query) {
                // silent — hanya untuk trigger koneksi tetap aktif
            });
            Event::listen(\Illuminate\Database\Events\ConnectionEstablished::class, function ($event) {
                if ($event->connectionName === 'pgsql') {
                    DB::statement('SET search_path TO public');
                }
            });
        }
    }
}