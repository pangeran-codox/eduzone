<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set search_path Postgres, LALU resolve & aktifkan tenant (sekolah)
 * untuk request ini berdasarkan user yang login (lihat
 * AuthUserTenantFinder).
 *
 * ⚠️ SEBELUM 6 Sep 2026, middleware ini CUMA set search_path — tenant
 * TIDAK PERNAH di-set untuk user yang login normal (cuma via route
 * /__test/auto-login yang manggil makeCurrent() manual). Akibatnya
 * SchoolScope (fail-open by design: kalau tenant null, query jalan
 * TANPA filter) tidak pernah memfilter apapun untuk sesi login biasa —
 * berpotensi menampilkan data lintas-sekolah tercampur. Ditambal di sini.
 *
 * Dipasangkan bareng Spatie\Multitenancy\Http\Middleware\NeedsTenant
 * (lihat bootstrap/app.php, grup 'tenant') supaya kalau tenant GAGAL
 * ke-resolve (mis. user.school_id nunjuk ke sekolah yang sudah dihapus),
 * request DITOLAK jelas (throw NoCurrentTenant) - fail-closed, bukan
 * diam-diam lanjut tanpa filter seperti sebelumnya.
 */
class InitializeTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        DB::statement('SET search_path TO public');

        $finder = app(config('multitenancy.tenant_finder'));
        $tenant = $finder->findForRequest($request);

        if ($tenant) {
            $tenant->makeCurrent();
        }

        return $next($request);
    }
}