# Audit Kesiapan Laravel — Integrasi dengan Absensi Gateway

Dibuat: 1 Sep 2026. Tujuan: cek kode Laravel yang ADA sekarang terhadap
kontrak yang diminta gateway (4 dokumen yang diupload), karena dokumen
gateway sudah agak lama sementara Laravel sudah jalan sprint lanjutan —
jadi statusnya campur (sebagian sudah cocok, sebagian mungkin belum,
sebagian mungkin belum ada sama sekali). Checklist ini diisi bareng,
satu per satu, berdasarkan kode asli yang di-paste/upload — bukan
tebakan dari memori/dokumentasi lama.

Status: 🟢 Cocok kontrak · 🟡 Ada tapi ada gap · 🔴 Belum ada · ⬜ Belum dicek

---

## PRIORITAS 1 — JWT Issuance saat Login Guru

Tanpa ini, 3 endpoint gateway (`checkin/teacher`, `attendance/daily`,
`enrollment/credentials`) permanen 401.

| # | Yang dicek | Target (kriteria lolos) | File yang perlu dilihat | Status |
|---|---|---|---|---|
| J1 | Claims JWT persis | `user_id`, `school_id`, `role`, `exp`, `iat` — tidak lebih tidak kurang, nama field persis (bukan `id`/`uid` dll) | `app/Services/Absensi/GatewayTokenIssuer.php` | ⬜ |
| J2 | Algoritma & secret | HS256, secret dari env `ABSENSI_GATEWAY_JWT_SECRET`, HARUS sama persis dengan `JWT_SECRET` gateway | sama + `.env` | ⬜ |
| J3 | Role admin case-sensitive | String `role` untuk admin persis `"admin"` (bukan `"Admin"`/`"superadmin"`/dll) — dicek exact match di gateway | tempat role di-assign ke token | ⬜ |
| J4 | `user_id` konsisten dgn `people_ref` | UUID yang dipakai di token harus SAMA dengan `person_id` yang nanti disinkron endpoint `people` (Prioritas 2) — kalau beda, nama guru salah muncul di data absen | bandingkan sumber `user_id` token vs sumber data sync people | ⬜ |
| J5 | Benar-benar terpanggil dari flow login | Ada endpoint/proses nyata yang, setelah guru login, MENGHASILKAN token ini ke client (bukan cuma class `GatewayTokenIssuer` yang belum dipanggil siapa pun) | controller/route login guru, atau tempat `AbsenHpController` minta token | ⬜ |
| J6 | Test end-to-end | `curl` ke `checkin/teacher` gateway pakai token asli hasil J5 → respons BUKAN 401 | manual test setelah J1-J5 lolos | ⬜ |

---

## PRIORITAS 2 — 3 Endpoint Sync (`schools`, `people`, `schedules`)

Kontrak lengkap: `laravel-sync-contract.md`.

| # | Yang dicek | Target (kriteria lolos) | File yang perlu dilihat | Status |
|---|---|---|---|---|
| S1 | Header auth | `X-Sync-Token` (nama persis ini) | `VerifySyncToken.php` | ⬜ |
| S2 | Cara bandingkan token | `hash_equals()`, BUKAN `===` (timing attack) | sama | ⬜ |
| S3 | Env var terpisah dari JWT | `ABSENSI_SYNC_TOKEN` (Laravel) ≠ `ABSENSI_GATEWAY_JWT_SECRET` — dua secret beda, jangan reuse satu env buat dua fungsi | `.env`, `config/services.php` | ⬜ |
| S4 | Param `page`/`per_page`/`updated_since` | `per_page` ikut angka yang diminta gateway (akan minta 500); `updated_since` opsional — kalau TIDAK ADA param ini, kembalikan SEMUA data (bukan error/kosong) | `SyncController.php` | ⬜ |
| S5 | Urutan hasil | `orderBy('updated_at')` ASC — wajib, supaya paginasi konsisten kalau data berubah di tengah proses jemput | sama | ⬜ |
| S6 | Format response | Array JSON POLOS `[...]`, BUKAN `{"data": [...]}` atau format Laravel resource default | sama | ⬜ |
| S7 | Field `schools` | `school_id`, `name`, `latitude`, `longitude`, `geofence_radius_meters`, `late_cutoff_time` (`null` kalau belum diatur — JANGAN kasih default), `is_active`, `updated_at` (RFC3339) | sama | ⬜ |
| S8 | Field `people` | `person_id`, `school_id`, `person_type` (persis `student`/`teacher`/`staff`), `full_name`, `photo_url` (`null` bukan `""`), `class_id` (`null` utk non-siswa), `grade`, `is_active`, `updated_at` | sama | ⬜ |
| S9 | Field `schedules` | `schedule_id`, `school_id`, `class_id`, `subject_name`, `teacher_id`, `day_of_week` (1=Senin...7=Minggu, ISO — cek konversi kalau Eduzone pakai 0=Minggu), `start_time`/`end_time` (`"HH:MM:SS"` saja), `is_active`, `updated_at` | sama | ⬜ |
| S10 | Soft-delete | Record nonaktif/keluar dikembalikan dengan `is_active:false` (bukan dihilangkan dari response), dan `updated_at` ikut ter-update saat status berubah | logic query tiap endpoint | ⬜ |
| S11 | Test manual | `curl -H "X-Sync-Token: ..." ".../sync/schools?page=1&per_page=500"` → 200 + JSON array valid, untuk ketiga endpoint | manual, setelah S1-S10 | ⬜ |
| S12 | Konfigurasi gateway | `.env` gateway: `SYNC_ENABLED=true`, `LARAVEL_SYNC_URL` BASE URL SAJA tanpa suffix (bukan `.../api/internal/sync`), `LARAVEL_SYNC_TOKEN` = `ABSENSI_SYNC_TOKEN` Laravel, `SYNC_INTERVAL=5m` | `.env` gateway (bukan Laravel, tapi perlu dicocokkan) | ⬜ |

---

## PRIORITAS 3 — Integrasi `checkin/teacher` dari `AbsenHpController`

| # | Yang dicek | Target | File | Status |
|---|---|---|---|---|
| T1 | Controller manggil gateway, bukan insert langsung | Role guru → `POST /api/v1/checkin/teacher` ke gateway pakai token dari Prioritas 1; role lain tetap insert langsung ke `attendance_events` (sesuai pembagian yang sudah ditetapkan) | `AbsenHpController.php` | ⬜ |
| T2 | Payload sesuai gateway | `event_type`, `latitude`, `longitude` terkirim benar (bukan string yang belum di-cast float — pernah ada bug ini) | sama | ⬜ |
| T3 | Test end-to-end nyata | Sudah dicoba dari HP asli / browser mobile (bukan cuma Postman) minimal 1x, dengan token JWT asli hasil Prioritas 1 | manual | ⬜ |

---

## SUSULAN (belum mendesak, dari `status-dan-tugas-laravel.md` §2.3)

| # | Item | Catatan |
|---|---|---|
| N1 | Sync balik `attendance_daily`→`student_attendance`/`teacher_attendance` | Belum ada kontrak sama sekali di kedua sisi — kalau mau dikerjakan, kontraknya harus didesain dulu (bukan sekadar implementasi) |
| N2 | UI admin panel utk `enrollment/credentials` | Form di Laravel yang manggil endpoint gateway ini dari sisi admin sekolah |

---

## CATATAN PRODUKSI YANG RELEVAN KE LARAVEL (dari `audit-kesiapan-production.md`)

Sebagian besar audit itu soal Go/gateway (bukan tanggung jawab
Laravel), tapi 2 poin ini menyentuh sisi Laravel:

| # | Item | Target |
|---|---|---|
| P1 (F3) | Secret production ≠ testing | `ABSENSI_GATEWAY_JWT_SECRET` & `ABSENSI_SYNC_TOKEN` di `.env` production HARUS beda nilai dari yang dipakai testing lokal — belum ada mekanisme teknis yang mencegah reuse, jadi ini soal disiplin operasional saat deploy |
| P2 (G3) | Akses foto siswa terkontrol | `photo_url` yang dikirim endpoint `people` harus mengarah ke storage yang aksesnya terkontrol (bukan link publik yang bisa ditebak) — cek `Storage::disk()` yang dipakai & apakah URL-nya signed/private |

---

## Urutan Kerja yang Disarankan

1. Cek **J1–J5** dulu (paste `GatewayTokenIssuer.php` + tempat dia dipanggil)
2. Kalau J1-J5 lolos → **J6** test manual
3. Cek **S1–S10** (paste `VerifySyncToken.php` + `SyncController.php` + route sync)
4. Kalau S1-S10 lolos → **S11** test manual, lalu **S12** cocokkan config gateway
5. Cek **T1–T3**
6. P1/P2 terakhir (lebih ke review konfigurasi, bukan kode)
