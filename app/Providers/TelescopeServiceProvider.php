<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        // Dark mode
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Filter — di local catat semua, di production hanya yang penting
        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Override authorization — paksa selalu cek gate meski di local.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(function ($request) {
            return app()->environment('local')
                ? $request->user() && $request->user()->role === 'superadmin'
                : Gate::check('viewTelescope', [$request->user()]);
        });
    }    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Hanya superadmin yang bisa akses Telescope.
     * Di local semua authenticated user bisa akses.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            // Hanya superadmin yang bisa akses, di semua environment
            return $user->role === 'superadmin';
        });
    }
}
