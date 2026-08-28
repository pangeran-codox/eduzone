<?php

namespace App\Services\Absensi;

use Firebase\JWT\JWT;

/**
 * Terbitkan JWT HS256 yang diverifikasi absensi-gateway (Go) di
 * internal/middleware/auth.go, fungsi JWTAuth() / RequireRole().
 *
 * Secret HARUS SAMA PERSIS dengan JWT_SECRET di .env gateway (lihat
 * ABSENSI_GATEWAY_JWT_SECRET di .env Laravel) — shared secret ini
 * SENGAJA terpisah dari ABSENSI_SYNC_TOKEN (dipakai VerifySyncToken buat
 * endpoint /api/internal/sync/*), supaya kalau salah satu bocor, yang
 * lain tidak ikut kena.
 *
 * Struktur payload WAJIB persis seperti ini (lihat
 * docs/status-dan-tugas-laravel.md repo absensi-gateway, Bagian 2.1):
 *   { "user_id", "school_id", "role", "iat", "exp" }
 *
 * role dicek CASE-SENSITIVE EXACT MATCH di sisi gateway — cuma dua nilai
 * yang gateway kenali sekarang: 'teacher' (checkin/teacher,
 * attendance/daily) dan 'admin' (enrollment/credentials).
 *
 * user_id di token INI YANG DIPAKAI gateway sebagai person_id saat
 * mencatat attendance_events untuk guru — harus konsisten dengan
 * person_id yang disinkron lewat /api/internal/sync/people (Teacher::id),
 * kalau tidak nama guru tidak akan muncul benar di data gateway.
 */
class GatewayTokenIssuer
{
    public function issueForTeacher(string $teacherId, string $schoolId): string
    {
        return $this->issue($teacherId, $schoolId, 'teacher');
    }

    public function issueForAdmin(string $userId, string $schoolId): string
    {
        return $this->issue($userId, $schoolId, 'admin');
    }

    private function issue(string $userId, string $schoolId, string $role): string
    {
        $secret = config('services.absensi_gateway.jwt_secret');

        if (! $secret) {
            throw new \RuntimeException(
                'ABSENSI_GATEWAY_JWT_SECRET belum diset di .env — tidak bisa menerbitkan token gateway.'
            );
        }

        $now = time();
        $ttl = (int) config('services.absensi_gateway.jwt_ttl', 900);

        $payload = [
            'user_id' => $userId,
            'school_id' => $schoolId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}