<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Untuk halaman yang token rahasianya ada di URL (verifikasi email, atur kata sandi):
 * jangan kirim Referer ke situs lain (mis. Google Fonts) dan jangan di-cache.
 */
class NoReferrerNoStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
