<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Warna badge Horizon
        Horizon::night();

        // Ukuran data yang disimpan di job payload (bytes)
        // Horizon::trimJobPayloadAt(1000);
    }

    /**
     * Hanya superadmin yang bisa akses Horizon.
     * Di local environment semua user bisa akses.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Local environment — akses bebas (untuk development)
            if (app()->environment('local')) {
                return true;
            }

            // Production — hanya superadmin
            return $user && $user->role === 'superadmin';
        });
    }
}
