<?php

namespace App\Services\Absensi;

use App\Models\Absensi\Device;
use App\Models\Absensi\SchoolRef;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Cek kesiapan layanan Absensi (gateway Go, DB pgsql_absensi, freshness sync,
 * device kiosk per sekolah). Dipakai oleh dashboard superadmin (detail penuh)
 * dan widget tenant (ringkas, auto-scope ke sekolah user login).
 *
 * Hasil di-cache 30 detik supaya polling dari banyak tenant sekaligus tidak
 * membombardir gateway/DB dengan health-check request.
 */
class HealthCheckService
{
    private const CACHE_TTL_SECONDS = 30;

    // Sync interval gateway = 5 menit (lihat absensi-gateway .env SYNC_INTERVAL).
    // Data dianggap basi kalau lebih tua dari 2x interval itu — kasih toleransi
    // 1 siklus gagal sebelum dianggap benar-benar bermasalah.
    private const SYNC_STALE_AFTER_MINUTES = 10;

    // Device dianggap offline kalau last_seen_at lebih tua dari ini.
    private const DEVICE_OFFLINE_AFTER_MINUTES = 5;

    /**
     * Laporan lengkap lintas-sekolah — dipakai dashboard superadmin.
     */
    public function fullReport(): array
    {
        return Cache::remember('absensi:health:full', self::CACHE_TTL_SECONDS, function () {
            return [
                'checked_at' => now()->toIso8601String(),
                'gateway' => $this->checkGateway(),
                'database' => $this->checkDatabase(),
                'schools' => $this->checkAllSchools(),
            ];
        });
    }

    /**
     * Status ringkas untuk satu sekolah — dipakai widget tenant.
     * Sengaja tidak expose detail infra (URL gateway, dll) ke tenant.
     */
    public function schoolReport(string $schoolId): array
    {
        $full = $this->fullReport();
        $school = collect($full['schools'])->firstWhere('school_id', $schoolId);

        $ready = $full['gateway']['ok']
            && $full['database']['ok']
            && $school
            && $school['sync_fresh']
            && $school['devices_online'] > 0;

        return [
            'ready' => $ready,
            'message' => $this->buildTenantMessage($full, $school),
            'checked_at' => $full['checked_at'],
        ];
    }

    private function checkGateway(): array
    {
        $baseUrl = config('services.absensi_gateway.base_url');
        $start = microtime(true);

        if (! $baseUrl) {
            return ['ok' => false, 'latency_ms' => null, 'error' => 'base_url belum dikonfigurasi'];
        }

        try {
            $response = Http::timeout(3)->get(rtrim($baseUrl, '/').'/health');
            $body = $response->json();

            // Gateway balas 200 kalau db.PingContext() sukses, 503 kalau
            // gagal (lihat internal/handlers/health.go). Ini cek yang jauh
            // lebih presisi dibanding sekadar "ada respons" — sekarang bisa
            // beda kondisi antara "gateway mati total" vs "gateway hidup
            // tapi Postgres-nya sendiri bermasalah".
            $ok = $response->successful() && ($body['database'] ?? null) === 'ok';
            $error = $ok ? null : ($body['database'] ?? 'Tidak bisa terhubung ke gateway');
        } catch (\Throwable $e) {
            $ok = false;
            $error = 'Tidak bisa terhubung ke gateway';
        }

        return [
            'ok' => $ok,
            'latency_ms' => round((microtime(true) - $start) * 1000),
            'error' => $error,
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection('pgsql_absensi')->select('select 1');
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Tidak bisa konek ke database absensi'];
        }
    }

    private function checkAllSchools(): array
    {
        return SchoolRef::query()->get()->map(function (SchoolRef $school) {
            $syncFresh = $school->synced_at
                && $school->synced_at->gt(now()->subMinutes(self::SYNC_STALE_AFTER_MINUTES));

            $devicesOnlineCount = Device::query()
                ->where('school_id', $school->school_id)
                ->where('is_active', true)
                ->where('last_seen_at', '>', now()->subMinutes(self::DEVICE_OFFLINE_AFTER_MINUTES))
                ->count();

            $devicesTotalCount = Device::query()
                ->where('school_id', $school->school_id)
                ->where('is_active', true)
                ->count();

            return [
                'school_id' => $school->school_id,
                'school_name' => $school->name,
                'sync_fresh' => $syncFresh,
                'synced_at' => $school->synced_at?->toIso8601String(),
                'devices_online' => $devicesOnlineCount,
                'devices_total' => $devicesTotalCount,
            ];
        })->values()->all();
    }

    private function buildTenantMessage(array $full, ?array $school): string
    {
        if (! $full['gateway']['ok'] || ! $full['database']['ok']) {
            return 'Layanan absensi sedang gangguan. Coba lagi beberapa saat.';
        }

        if (! $school) {
            return 'Data sekolah belum tersinkron ke layanan absensi. Hubungi admin.';
        }

        if (! $school['sync_fresh']) {
            return 'Data belum tersinkron terbaru — siswa/guru baru mungkin belum dikenali sistem.';
        }

        if ($school['devices_online'] === 0) {
            return 'Belum ada device absensi yang aktif saat ini.';
        }

        return 'Layanan absensi siap digunakan.';
    }
}