<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi header X-Sync-Token dari absensi-gateway (Go) untuk endpoint
 * internal/sync/*. SENGAJA pakai shared-secret TERPISAH dari JWT_SECRET
 * (guru login) - lihat docs/laravel-sync-contract.md di repo absensi-gateway:
 * kalau salah satu bocor, yang lain tidak ikut kena.
 *
 * Endpoint ini server-to-server (Go -> Laravel), bukan diakses browser -
 * tidak butuh CSRF, tidak butuh session/cookie.
 */
class VerifySyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.absensi_gateway.sync_token');
        $given = $request->header('X-Sync-Token', '');

        if (! $expected || ! hash_equals((string) $expected, (string) $given)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
