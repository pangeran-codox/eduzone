<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi header X-Sync-Token dari absensi-gateway terhadap
 * ABSENSI_SYNC_TOKEN di .env — HARUS SAMA PERSIS dengan LARAVEL_SYNC_TOKEN
 * di .env gateway. Ini shared-secret KHUSUS server-to-server sync,
 * sengaja terpisah dari ABSENSI_GATEWAY_JWT_SECRET (dipakai untuk token
 * login guru) — kalau salah satu bocor, yang lain tidak ikut kena.
 *
 * Pakai hash_equals(), bukan ===, supaya tidak rentan timing attack.
 */
class VerifySyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.absensi_gateway.sync_token');
        $given = $request->header('X-Sync-Token', '');

        if (! $expected || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}