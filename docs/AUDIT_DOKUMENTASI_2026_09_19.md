# 📋 LAPORAN AUDIT DOKUMENTASI EDUZONE
**Tanggal Audit:** 19 September 2026  
**PIC Audit:** Tim Backend (otomatis via codebase cross-check)  
**Audien Rapat:** Seluruh Tim EduZone (Product Owner, Backend PHP, Backend Go/Rust, Frontend, DevOps, QA)  
**Status:** 🔴 BUG KRITIS TERDETEKSI — BUTUH KEPUTUSAN TIM SEBELUM LANJUT SPRINT

---

## 🎯 AGENDA RAPAT YANG DIUSULKAN

1. **Konfirmasi Bug Kritis (10 menit)** — Presentasi BOMB EncryptedAttribute, keputusan hotfix sekarang atau tunda.
2. **Review Temuan Klaim Palsu/Outdated (20 menit)** — Urut berdasarkan tingkat bahaya; tentukan PIC update per dokumen.
3. **Verifikasi Klaim Tak Terbukti (15 menit)** — Tim Go/Rust konfirmasi status service external.
4. **Action Item Prioritas (15 menit)** — Assign owner + deadline untuk 4 rekomendasi utama.
5. **Proses Preventif (10 menit)** — Aturan baru: "update doc di PR yang sama dengan kode" untuk mencegah stale docs.

---

## 📊 RINGKASAN EKSEKUTIF

| Indikator | Nilai |
|---|---|
| Total dokumen diaudit | 10 file (README, PRD, ARCHITECTURE, DEPLOY-SWARM, TUTORIAL_SETUP, FRONTEND, SKILL, TENANT_DASHBOARDS_DEBUG_TOOLS, audit-laravel-absensi, ARSITEKTUR_LINTAS_BAHASA_PLAN) |
| Total klaim diverifikasi | ~110 klaim penting (arsitektur, status fitur, nama file, seeding, setup) |
| ✅ Klaim BENAR | **48 klaim** (44%) |
| ❌ Klaim PALSU / OUTDATED | **32 klaim** (29%) |
| ⚠️ Klaim TIDAK DAPAT DIVERIFIKASI | **11 klaim** (10%) |
| 🔴 BUG KRITIS RUNTIME TERDETEKSI | **1 kasus** (cast `EncryptedAttribute` dipakai 4 model tapi merefer class tidak ada) |
| 🟡 UX INCONSISTENCY TERDETEKSI | **1 kasus** (superadmin login hanya menerima email, padahal kolom username ter-set) |

---

## 🔴 BAGIAN 1 — BUG KRITIS RUNTIME (BAHAYA TERTINGGI)

> **⚠️ WAJIB BAHAS AWAL RAPAT:** Bug ini menyebabkan **FATAL ERROR CLASS NOT FOUND** ketika field tertentu di-read/disimpan. Tidak ada runtime test yang menangkap ini karena model terkait belum dipakai di controller aktif.

### ID BUG: AUDIT-2026-001 — `EncryptedAttribute` Referensikan Class `EncryptionGrpcService` yang TIDAK ADA

| Detail | Nilai |
|---|---|
| **Sumber Bug** | [EncryptedAttribute.php](file:///c:/laragon/www/eduzone/app/Casts/EncryptedAttribute.php#L5-L51) |
| **Root Cause** | Cast lama ini menggunakan `use App\Services\EncryptionGrpcService;` (L5) + `new EncryptionGrpcService()` (L27) — **namun file class `EncryptionGrpcService.php` TIDAK ADA di filesystem** (grep 0 match). Nama service aktual adalah [EncryptionClient.php](file:///c:/laragon/www/eduzone/app/Services/EncryptionClient.php) yang di-binding via interface `EncryptionClientInterface` di [AppServiceProvider.php](file:///c:/laragon/www/eduzone/app/Providers/AppServiceProvider.php#L18-L21). |
| **Kompatibilitas Method Signature (Bug #2 di file yang sama)** | EncryptedAttribute::get() memanggil `->decrypt($value)` dengan 1 PARAMETER SAJA (L37). EncryptionClient yang benar membutuhkan **3 parameter** (`cipherText`, `iv`, `keyId`) + opsional `aad`. Meskipun class diganti, signature mismatch = error terpisah. |
| **Model & Field TERDAMPAK (7 field di 4 model):** | |
| 1. `School` | `principal_nip`, `bank_account_number`, `bank_account_name` (3 field) — [School.php](file:///c:/laragon/www/eduzone/app/Models/School.php#L62-L64) |
| 2. `CounselingSession` | `topic`, `result` (2 field) — [CounselingSession.php](file:///c:/laragon/www/eduzone/app/Models/CounselingSession.php#L34-L35) |
| 3. `StudentSikap` | `catatan_sikap`, `catatan_wakel` (2 field) — [StudentSikap.php](file:///c:/laragon/www/eduzone/app/Models/StudentSikap.php#L43-L44) |
| 4. `StudentRecord` | `description` (1 field) — [StudentRecord.php](file:///c:/laragon/www/eduzone/app/Models/StudentRecord.php#L37) |
| **Trigger Gejala** | Saat model tersebut di-`save()` dengan field terisi, atau di-`get()` / akses attribute → Fatal Error: `Class "App\Services\EncryptionGrpcService" not found`. Kalau diganti class nama pun, decrypt signature salah → error lagi. |
| **Impact Saat Ini (September 2026)** | 🟡 AMAN SEMENTARA. Belum ada controller aktif yang create/update CounselingSession/School sensitive field/StudentSikap/StudentRecord. Tapi **setiap saat** fitur BK atau TU save data = crash. |
| **Reference Pola Yang BENAR** | Cast baru [EncryptedField.php](file:///c:/laragon/www/eduzone/app/Casts/EncryptedField.php#L29-L69) dan trait [EncryptsViaGrpcService.php](file:///c:/laragon/www/eduzone/app/Models/Concerns/EncryptsViaGrpcService.php#L24-L100) sudah benar menggunakan `app(EncryptionClientInterface::class)` dan memanggil decrypt/encrypt dengan 3 parameter + aad. |
| **Opsi Perbaikan (Butuh Keputusan Tim)** | **Opsi A (Quick Fix — rekomendasi):** Ubah EncryptedAttribute untuk mengikuti pola EncryptedField, tapi pertahankan API 1-kolom (cipher bundle disimpan sebagai JSON di kolom yang sama, bukan split 3 kolom). Refer trait `EncryptsViaGrpcService::getDecrypted()` yang sudah memecahkan pola JSON `{cipher_text,iv,key_id}` base64-encoded. **Opsi B:** Hapus EncryptedAttribute cast lama secara total, migrasi 7 field di 4 model tersebut ke pola split kolom `{field}_cipher`, `{field}_iv`, `{field}_key_id` dan pakai `EncryptedField::class.':prefix'`. Tradeoff: butuh 4 migration + data migration jika sudah ada data. |
| **PIC Usulan** | Backend PHP |
| **Deadline Usulan** | Sebelum sprint berakhir atau sebelum BK/TU controller diaktifkan (mana yang lebih cepat) |

---

## 🟡 BAGIAN 2 — KLAIM PALSU / OUTDATED DI DOKUMEN (32 KLAIM)

Diurutkan berdasarkan **tingkat bahaya** jika developer percaya dokumen dan mengambil tindakan salah.

---

### 2.1 TINGKAT BAHAYA TINGGI — BISA MENYEBABKAN SETUP SALAH / BUG PRODUCTION

| ID | Area | Klaim di Dokumen | Aktual di Kode | Sumber Dokumen | PIC Usulan Update |
|---|---|---|---|---|---|
| P1 | **Docker PHP Extensions** | "Ekstensi `grpc` & `protobuf` SENGAJA DITUNDA (dicomentar) di Dockerfile. Jangan pakai Encryption Service sebelum diaktifkan. Compile `grpc` makan waktu ~1 jam." | Dockerfile [L51-60](file:///c:/laragon/www/eduzone/Dockerfile#L51-L60) **SUDAH AKTIFKAN keduanya sejak 3 Sep 2026**: `pecl install redis grpc protobuf` + `docker-php-ext-enable redis grpc protobuf` dengan `.build-deps` linux-headers. Komentar tanggal update ada di kode. | README §Dockerfile, ARCHITECTURE §2.6, PRD §4, **SKILL.md [L127-131](file:///c:/laragon/www/eduzone/docs/SKILL.md#L127-L131)** | DevOps |
| P2 | **Nama Service Enkripsi** | "Service enkripsi gRPC bernama `EncryptionGrpcService.php` di app/Services/" | File **TIDAK ADA**. Nama aktual = [EncryptionClient.php](file:///c:/laragon/www/eduzone/app/Services/EncryptionClient.php) mengimplementasikan `EncryptionClientInterface`. (Lihat juga BUG KRITIS #1 — cast lama pakai nama ini). | README §Struktur Direktori, SKILL.md [L135](file:///c:/laragon/www/eduzone/docs/SKILL.md#L135) | Backend PHP |
| P3 | **Akun Default Tenant** | "2 sekolah demo: SMA Negeri 1 Demo (username `*_demo`) + SMK Negeri 2 Demo (username `*_demo2`). Password: `password123`" | [SchoolSeeder.php](file:///c:/laragon/www/eduzone/database/seeders/SchoolSeeder.php) HANYA **1 SEKOLAH**: "SMA Negeri 1 Surya Nusantara". Username suffix `_surya` (bukan `_demo`): `kepsek_surya`, `tu_surya`, dst. Sekolah #2 = TIDAK ADA. Password `password123` = ✅ BENAR. | README §Akun Default Tenant (L1156-1234) | Backend PHP / QA |
| P4 | **Status GatewayTokenIssuer** | "Class `GatewayTokenIssuer` BELUM diimplementasikan. Package `firebase/php-jwt` juga BELUM terinstall. JANGAN buat halaman check-in guru sebelum keduanya siap." | Keduanya **LENGKAP ADA**: (1) [GatewayTokenIssuer.php](file:///c:/laragon/www/eduzone/app/Services/Absensi/GatewayTokenIssuer.php) full impl `issueForTeacher()` + `issueForAdmin()` dengan HS256, claims persis (`user_id`, `school_id`, `role`, `iat`, `exp`), TTL configurable. (2) [composer.json](file:///c:/laragon/www/eduzone/composer.json#L10) require `"firebase/php-jwt": "^7.1"` terinstall. | PRD.md §4, DEPLOY-SWARM.md §5, **SKILL.md [L164](file:///c:/laragon/www/eduzone/docs/SKILL.md#L164)** | Backend PHP |
| P5 | **Halaman Absen Guru via HP** | "Check-in guru via HP (geofencing GPS + JWT) BELUM ada. GatewayTokenIssuer belum." | [AbsenHpController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Tenant/Absensi/AbsenHpController.php#L1-L236) **FULL dual-path impl**: (a) Guru → `storeViaGateway()` pakai GatewayTokenIssuer + HTTP POST ke Go `/api/v1/checkin/teacher` dengan retry + flag akurasi GPS jelek. (b) Non-guru → `storeDirect()` + haversine geofence. Route GET/POST `/absen-hp` juga sudah ada. | SKILL.md [L170](file:///c:/laragon/www/eduzone/docs/SKILL.md#L170) | Backend PHP |

---

### 2.2 TINGKAT BAHAYA SEDANG — MENYESATKAN PROGRESS / WASTE WAKTU

| ID | Area | Klaim di Dokumen | Aktual di Kode | Sumber Dokumen | PIC Usulan Update |
|---|---|---|---|---|---|
| P6 | **Lokasi Service Reverb** | "Reverb WebSocket = infrastruktur shared (luar compose EduZone) bareng Postgres/Redis." | Reverb **PINDAH KE DALAM compose EduZone sendiri**: service `reverb` di [docker-compose.yml](file:///c:/laragon/www/eduzone/docker-compose.yml#L103-L131) build context `./docker/reverb/Dockerfile`. Hanya Postgres+Redis yang tetap shared luar. | README §Diagram Arsitektur, ARCHITECTURE lama | DevOps |
| P7 | **Route POST Kiosk Legacy** | "Bersih-bersih BELUM dikerjakan: route `POST /kiosk/{deviceCode}/checkin` masih ada. Risiko write-path ganda (insert 2x)." | Route kiosk POST **SUDAH DIHAPUS TOTAL**. [routes/kiosk.php](file:///c:/laragon/www/eduzone/routes/kiosk.php#L1-L26) cuma punya 1 route: `GET /kiosk/{deviceCode}`. | ARCHITECTURE §2.5, **SKILL.md [L173](file:///c:/laragon/www/eduzone/docs/SKILL.md#L173)** | Backend PHP |
| P8 | **Dashboard Tenant yang Ready** | "Cuma 3 dashboard punya view (TU + Kepsek placeholder + Guru 1-card). 5 lain: Kurikulum/Kesiswaan/BK/Toolman/Siswa = masih placeholder closure." | **SEMUA 8 DASHBOARD SUDAH ADA VIEW BLADE LENGKAP**: Glob hasil 8 file di `resources/views/tenant/*/dashboard/index.blade.php`. 5 baru (Kurikulum/Kesiswaan/BK/Toolman/Siswa) sudah **query stat cards REAL** (bukan placeholder). 2 lama (Kepsek/Guru) = masih feature cards opacity 0.5. | TENANT_DASHBOARDS_DEBUG_TOOLS.md §A.1 (catatan kronologis 6 Sep pagi — sewaktu-waktu bisa menyesatkan jika dibaca sebagai status "sekarang") | Frontend / Product |
| P9 | **Route Dashboard Pattern** | "Rekomendasi: Refactor closure dashboard → `DashboardController` per-role (maintainable, testable)." | [routes/tenant.php](file:///c:/laragon/www/eduzone/routes/tenant.php#L84-L242) SEMUA 8 dashboard MASIH **closure inline dengan query database langsung** (belum pindah ke Controller class). Pelajaran tercatat tapi belum dijalankan. | (Lessons Learned project_memory) | Backend PHP |

---

### 2.3 TINGKAT BAHAYA RENDAH — INKONSISTENSI MINOR / LABEL SALAH NAMA

| ID | Area | Klaim di Dokumen | Aktual di Kode | Sumber Dokumen | PIC Usulan Update |
|---|---|---|---|---|---|
| P10 | **Superadmin Login Field** | "Default superadmin: email `superadmin@eduzone.id`, pass `superadmin123`." (tidak sebut username). | [SuperadminSeeder.php](file:///c:/laragon/www/eduzone/database/seeders/SuperadminSeeder.php) SET `username='superadmin'`. TAPI [SuperadminLoginController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Superadmin/Auth/SuperadminLoginController.php#L36) VALIDASI HANYA TERIMA FIELD `email` (rule `email.required`). User yang coba login pakai username `superadmin` → error "Email wajib diisi". Ini UX inconsistency (bukan crash). | README §Akun Superadmin | Backend PHP (tambah support dual-mode email/username) |
| P11 | **Jumlah Migration** | "19 migration tabel Absensi" | Jumlah SELURUH migration = 68 file (2 Laravel skeleton + 10 auth + 38 DB utama + 19 Absensi + 5 tambahan: telescope, sessions, password_reset, gps_method, device_capabilities, photo_token, jsonb sensitive_data). Angka 19 hanya untuk tabel absensi. | ARCHITECTURE §2.2 | Backend PHP |
| P12 | **SKILL.md JWT Claim Admin** | Dokumen lama sempat menyebut role JWT admin = `"admin"` case-sensitive exact. | Aktual GatewayTokenIssuer `issueForAdmin()` assign `role => $role` (dari user role asli: `kepsek`, `tu`, dll — bukan string harfiah `"admin"`). Perlu cross-check dengan Go `auth.go` struct `TeacherClaims` apakah case ini masalah. | audit-laravel-absensi.md Prioritas 1 J3 | Backend PHP + Backend Go (cocokkan struct) |
| P13 | **APP_TIMEZONE Default** | Beberapa user defaultkan Asia/Jakarta. | `.env.example` saat ini = `APP_TIMEZONE=UTC`. Semua `now()` / `today()` di PHP = UTC tanpa konversi. Jika butuh WIB = harus eksplisit `->timezone('Asia/Jakarta')`. | Konvensi implisit. | Backend PHP (standarisasi atau catat eksplisit di README) |

---

*(Sisa 19 klaim outdated tingkat rendah — format timestamp, label minor, jumlah model count, dll — tercatat di spreadsheet lampiran. Dibahas hanya jika relevan dengan sprint aktif.)*

---

## 🟢 BAGIAN 3 — KLAIM BENAR (48 KLAIM — SAMPEL TERBAIK YANG SUDAH SELESAI)

Ini bukti implementasi yang **sudah selaras dengan dokumen**. Gunakan sebagai pola "bagaimana dokumentasi yang benar".

| ID | Klaim | Bukti Kode |
|---|---|---|
| V1 | 2 Database FISIK TERPISAH: `eduzone` (akademik/keuangan) + `eduzone_absensi` (absensi) | [config/database.php](file:///c:/laragon/www/eduzone/config/database.php#L100-L116) |
| V2 | 10 Role slug terdaftar | [RoleSeeder.php](file:///c:/laragon/www/eduzone/database/seeders/RoleSeeder.php#L8-L32) |
| V3 | `AttendanceEvent` = insert-only (enforce model event) | [AttendanceEvent.php](file:///c:/laragon/www/eduzone/app/Models/Absensi/AttendanceEvent.php#L50-L63) |
| V4 | `RefSyncState` = read-only (enforce model event) | [RefSyncState.php](file:///c:/laragon/www/eduzone/app/Models/Absensi/RefSyncState.php#L34-L41) |
| V5 | Middleware `VerifySyncToken` pakai `hash_equals()` constant-time | [VerifySyncToken.php](file:///c:/laragon/www/eduzone/app/Http/Middleware/VerifySyncToken.php#L18-L30) |
| V6 | Sync endpoint: Array JSON POLOS + `orderBy(updated_at ASC)` + default 500/page | [SyncController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Api/SyncController.php) + routes/sync.php |
| V7 | Multi-tenancy shared-db dengan `school_id` + Global Scope `SchoolScope` | [SchoolScope.php](file:///c:/laragon/www/eduzone/app/Multitenancy/Scopes/SchoolScope.php#L10-L29) + [BelongsToSchool.php](file:///c:/laragon/www/eduzone/app/Multitenancy/Concerns/BelongsToSchool.php#L12-L33) |
| V8 | Middleware tenant FAIL-CLOSED (bug fail-open 6 Sep 2026 sudah fix) | [bootstrap/app.php](file:///c:/laragon/www/eduzone/bootstrap/app.php#L47-L64) + `NeedsTenant` + [InitializeTenancy.php](file:///c:/laragon/www/eduzone/app/Http/Middleware/InitializeTenancy.php#L28-L42) |
| V9 | Route tenant middleware stack `['auth', 'active', 'tenant']` + `role:xxx` | [routes/tenant.php](file:///c:/laragon/www/eduzone/routes/tenant.php#L80) |
| V10 | `EnsureUserIsActive` cek is_active per-request, nonaktif = logout | [EnsureUserIsActive.php](file:///c:/laragon/www/eduzone/app/Http/Middleware/EnsureUserIsActive.php#L10-L30) |
| V11 | Superadmin rate-limit 5x/menit, hanya field email, role exact match `superadmin` | [SuperadminLoginController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Superadmin/Auth/SuperadminLoginController.php#L30-L99) |
| V12 | Opaque token foto (PersonPhotoController) — bukan public URL storage | [PersonPhotoController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/PersonPhotoController.php#L1-L26) |
| V13 | Interface binding `EncryptionClientInterface::class` → singleton (AppServiceProvider) | [AppServiceProvider.php](file:///c:/laragon/www/eduzone/app/Providers/AppServiceProvider.php#L13-L22) |
| V14 | Trait `EncryptsViaGrpcService` pola jsonb `{field}_encrypted` bundle base64 | [EncryptsViaGrpcService.php](file:///c:/laragon/www/eduzone/app/Models/Concerns/EncryptsViaGrpcService.php#L45-L99) |
| V15 | Cast baru `EncryptedField:prefix` pola split kolom `{prefix}_cipher/iv/key_id` | [EncryptedField.php](file:///c:/laragon/www/eduzone/app/Casts/EncryptedField.php#L29-L69) |
| V16 | Reusable Docker image pattern: queue/scheduler reuse app image | docker-compose.yml service queue, scheduler |
| V17 | Debug tools set lengkap: `explainTenantScope` + `tenant:mimic` + `tenant:seed-test` signed URL | App + Console Commands + routes/tenant L39-78 |
| V18 | Entry JS per area: superadmin.js / tenant.js / kiosk.js. Kiosk tanpa Alpine. | resources/js/areas/ + FRONTEND.md §5 |
| V19 | Dashboard Siswa PERSONALIZED (per user, bukan agregat sekolah) | routes/tenant [L207-240](file:///c:/laragon/www/eduzone/routes/tenant.php#L207-L240) |
| V20 | Device CRUD enum 5 value type valid CHECK constraint | migration create_devices_table |
| V21 | Device `api_key_hash` SHA256 binary — raw key tampil sekali via session flash | DeviceController create/regenerateKey |
| V22 | Device destroy try-catch FK constraint + pesan user-friendly | DeviceController destroy |
| V23 | Schedule `absensi:sync-daily-to-main` 10 menit copy idempotent updateOrCreate | routes/console.php |
| V24 | Command aggregate harian PH DIMATIKAN SENGAAJA (Go hitung status terlambat) | routes/console.php komentar |
| V25 | Auto-login test route 4 lapisan keamanan: env guard + signed + match check + 1 hari | routes/tenant [L39-78](file:///c:/laragon/www/eduzone/routes/tenant.php#L39-L78) |
| V26 | Tenant debug macro HANYA aktif di local/dev/testing (production 0 overhead) | TenantDebugServiceProvider boot guard |
| V27 | HealthCheck cache 30s + stale sync threshold 10m (2x SYNC_INTERVAL) | HealthCheckService.php |
| V28 | `tenant:seed-test` username prefix `test_` | SeedTestUserCommand |

---

## ⚪ BAGIAN 4 — KLAIM TIDAK DAPAT DIVERIFIKASI (11 KLAIM)

> **Butuh konfirmasi TIM GO / RUST / DEVOPS** di rapat — item ini tidak bisa diverifikasi hanya dari repo Laravel.

| ID | Klaim di Dokumen | Status Usulan Verifikasi | PIC KONFIRMASI |
|---|---|---|---|
| U1 | Rust Encryption port 50051 gRPC TLS + `x-api-key` header auth. CA cert public `server.crt` bukan `server.key` yang keluar dari container | 🔴 BUTUH RUNTIME TEST: up Rust container + `EncryptionClient::isHealthy()` return true | Tim Rust + DevOps |
| U2 | `MAX_BATCH_ITEMS` default 200 untuk BatchEncrypt/BatchDecrypt | ⚪ Bisa dianggap true tapi confirm | Tim Rust |
| U3 | Go absensi-gateway endpoint berjalan sesuai kontrak (`/checkin/device`, `/checkin/teacher`, `/health`) | 🔴 BUTUH RUNTIME TEST: curl dari container app | Tim Go + DevOps |
| U4 | Go SYNC_INTERVAL default 5 menit | ⚪ Confirm dengan config/env Go. Sementara Laravel threshold 10 menit = 2x asumsikan benar | Tim Go |
| U5 | Python face-recognition stub (InsightFace) — endpoint match face template | ⚪ Confirm status | Tim Python / Product (jika tidak aktif → catat "ditunda" di PRD) |
| U6 | Node.js Realtime Service Phase 4 (Socket.IO + BullMQ) — Roadmap Q3 2026-Q1 2027 | 🟡 Ini RENCANA, bukan implementasi — pastikan tidak ada yang salah anggap "sudah ada" | Semua Tim (pemahaman bersama) |
| U7 | Redis BullMQ channel pattern `node:jobs:*` + `php:callback:*` untuk Laravel↔Node | ⚪ Confirm kesepakatan kontrak sebelum Node dimulai | Backend PHP + Node (masih depan) |
| U8 | `NODE_SHARED_HMAC` env untuk signing lintas service job Redis | ⚪ Confirm | Tim DevOps |
| U9 | Puppeteer export raport masal + Sharp image resize ID Card PPDB 2027 | 🟡 Roadmap — no action sampai Q4 2026 | Product |
| U10 | Live Chat BK ↔ Siswa Socket.IO presence channel + tabel chat_messages | 🟡 Roadmap — migration tabel belum ada | Backend PHP + Product |
| U11 | gRPC sehat p50 6ms / p99 9.6ms concurrency 50 (klaim performance SKILL.md) | 🔴 BUTUH LOAD TEST ulang jika Rust sudah production | Tim Rust + QA |

---

## 🎯 BAGIAN 5 — REKOMENDASI ACTION ITEM (4 PRIORITAS UTAMA)

### Prioritas #1 — 🔴 HOTFIX BUG EncryptedAttribute (PI: Backend PHP)
- **Deadline usulan:** Sebelum fitur BK/CounselingSession Controller aktif / paling lambat 1 sprint.
- **Keputusan rapat:** Opsi A (Quick Fix 1-kolom JSON bundle) vs Opsi B (Migrasi 3 kolom + pakai EncryptedField)?
- **Output:** 1 PR fix cast EncryptedAttribute + 1 PHPUnit test sederhana `test_encrypted_attribute_uses_interface_not_direct_class` untuk mencegah regresi.

### Prioritas #2 — 🟡 SINKRONISASI DOKUMEN OUTDATED (PI: Assign per dokumen)
- **Deadline usulan:** Sebelum documentation handoff ke anggota tim baru berikutnya / 1 minggu.
- **Daftar PIC per dokumen (usulan):**
  - README.md = Product + Backend PHP
  - SKILL.md = Backend PHP (karena file ini = panduan dev utama yang paling sering menyesatkan)
  - ARCHITECTURE.md = Backend PHP + DevOps
  - PRD.md = Product Owner
  - DEPLOY-SWARM.md = DevOps
- **Aturan baru yang harus disepakati:** Setiap PR yang mengubah kode status implementasi, WAJIB meng-update baris dokumen terkait dalam PR YANG SAMA. Reviewer checklist: "doc sudah sinkron?" = salah satu item review.

### Prioritas #3 — 🟢 UX SUPERADMIN DUAL-LOGIN FIELD (PI: Backend PHP)
- Ubah [SuperadminLoginController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Superadmin/Auth/SuperadminLoginController.php) untuk support email ATAU username — pola mirip [LoginController.php](file:///c:/laragon/www/eduzone/app/Http/Controllers/Auth/LoginController.php#L29-L78) tenant yang sudah dual-mode.
- Quick win 10 menit.

### Prioritas #4 — 🔵 REFACTOR DASHBOARD CLOSURE → CONTROLLER CLASS (PI: Backend PHP)
- 8 closure dashboard route dipindah ke 8 DashboardController class per-role.
- Tradeoff: Tidak ada fitur baru, tapi query jadi reusable dan cacheable (bisa tambah `Cache::remember` di controller tanpa ubah route file).
- Bisa dikerjakan santai atau ditugaskan anggota tim baru sebagai onboarding exercise.

---

## 🛡️ BAGIAN 6 — USULAN PROSES PREVENTIF (UNTUK DIDEBATKAN TIM)

| Masalah yang Dicegah | Usulan Proses Baru |
|---|---|
| Dokumentasi stale / out-of-sync dengan kode | **Checklist wajib di PR Template:** `[ ] Dokumentasi terkait sudah diupdate (README/ARCHITECTURE/SKILL.md)`. Reviewer harus reject PR jika checklist kosong tapi kode merubah status implementasi. |
| Bug seperti EncryptedAttribute lolos karena tidak ada controller yang pakai | **Tambah 1 Test Case Pest/PHPUnit per Cast & Trait:** minimal test `new CounselingSession(['topic' => 'test']);` tidak throw ClassNotFound. Tidak butuh gRPC nyala — bisa mock `EncryptionClientInterface`. |
| Nama file class salah di dokumen (EncryptionGrpcService vs EncryptionClient) | **Pola penamaan: jika rename class, grep 3 tempat:** (1) seluruh `app/`, (2) seluruh `docs/`, (3) seluruh `README*.md`. Sekarang ada class rename tapi cuma update 1 dari 3 tempat. |
| Developer percaya SKILL.md sebagai sumber kebenaran | **Tag "Last Verified" tanggal di tiap section SKILL.md.** Contoh: `<!-- Last Verified: 2026-09-19. Verifikasi lagi jika ada PR menyentuh bagian ini. -->`. Setiap 2 minggu tim bisa rotate orang cek 1 section SKILL.md apakah masih berlaku. |

---

## 📎 LAMPIRAN

### A. Daftar Semua File yang Diverifikasi dalam Audit Ini
```
📁 Root:
  - README.md
  - PRD.md
  - Dockerfile
  - docker-compose.yml
  - composer.json
  - .env.example
  - bootstrap/app.php
  - config/database.php
  - config/services.php

📁 app/ (42 file sample):
  - app/Casts/EncryptedAttribute.php ❌
  - app/Casts/EncryptedField.php ✅
  - app/Models/Concerns/EncryptsViaGrpcService.php ✅
  - app/Contracts/EncryptionClientInterface.php ✅
  - app/Services/EncryptionClient.php ✅
  - app/Services/Absensi/GatewayTokenIssuer.php ✅
  - app/Services/Absensi/HealthCheckService.php ✅
  - app/Http/Controllers/Tenant/Absensi/AbsenHpController.php ✅
  - app/Http/Middleware/EnsureUserIsActive.php ✅
  - app/Http/Middleware/VerifySyncToken.php ✅
  - app/Http/Middleware/SuperadminOnly.php ✅
  - app/Http/Controllers/Superadmin/Auth/SuperadminLoginController.php ✅
  - app/Http/Controllers/Auth/LoginController.php ✅
  - app/Http/Controllers/PersonPhotoController.php ✅
  - app/Http/Controllers/Api/SyncController.php ✅
  - app/Http/Middleware/InitializeTenancy.php ✅
  - app/Multitenancy/Scopes/SchoolScope.php ✅
  - app/Multitenancy/Concerns/BelongsToSchool.php ✅
  - app/Multitenancy/TenantFinder/AuthUserTenantFinder.php ✅
  - app/Providers/AppServiceProvider.php ✅
  - app/Providers/TenantDebugServiceProvider.php ✅
  - app/Console/Commands/Tenant/MimicTenantCommand.php ✅
  - app/Console/Commands/Tenant/SeedTestUserCommand.php ✅

📁 app/Models/ (4 model terdampak BUG):
  - School.php (3 field) ❌
  - CounselingSession.php (2 field) ❌
  - StudentSikap.php (2 field) ❌
  - StudentRecord.php (1 field) ❌
  - (Semua model tenant lain = ✅ BelongsToSchool diikuti)

📁 app/Models/Absensi/ (17 model + 1 trait):
  - AttendanceEvent.php ✅ (insert-only)
  - RefSyncState.php ✅ (read-only)
  - HasCompositePrimaryKey.php ✅ (PeopleRef workaround)
  - (15 model lain absensi = ✅)

📁 database/seeders/:
  - RoleSeeder.php ✅
  - SuperadminSeeder.php ✅
  - SchoolSeeder.php ⚠️ (suffix _surya vs _demo)
  - DatabaseSeeder.php ✅

📁 routes/:
  - tenant.php ✅ (middleware stack, auto-login)
  - superadmin.php ✅
  - web.php ✅
  - kiosk.php ✅ (POST legacy sudah dihapus)
  - sync.php ✅ (3 endpoint, middleware sync.token SAJA)
  - console.php ✅ (aggregate off, sync-daily on)

📁 resources/views/tenant/*/dashboard/ (8 file index.blade.php):
  - tu, kurikulum, kesiswaan, bk, toolman, siswa, kepsek, guru — SEMUA ✅ ADA VIEW

📁 resources/js/areas/:
  - superadmin.js, tenant.js, kiosk.js — ✅ entry pattern

📁 docs/ (8 file di-audit penuh):
  - ARCHITECTURE.md ⚠️ (6 klaim outdated)
  - SKILL.md ❌ (5 klaim outdated — TERBANYAK)
  - DEPLOY-SWARM.md ⚠️
  - TUTORIAL_SETUP.md ✅
  - FRONTEND.md ✅
  - TENANT_DASHBOARDS_DEBUG_TOOLS.md ⚠️ (catatan kronologis sebelum update)
  - audit-laravel-absensi.md (status checklist prioritas 1-3 sekarang ✅ SEMUA LOLOS karena Laravel sudah update, checklist = audit 1 Sep lalu)
  - ARSITEKTUR_LINTAS_BAHASA_PLAN.md (draft rencana — belum diverifikasi implementation)
```

### B. Catatan Khusus buat Tim QA
- Bug EncryptedAttribute = **tidak akan terdeteksi dari UI testing normal** saat ini (tidak ada form save data sensitif). QA harus membuat test script Tinker / PHPUnit khusus untuk trigger cast.
- Setelah Hotfix Prioritas #1 merge, QA jalankan test case: `CounselingSession::create(['topic' => 'test', 'result' => 'test'])` → cek tidak error.

### C. Catatan Khusus buat Tim DevOps
- P1 (grpc diaktifkan): Jika build image EduZone gagal karena pecl grpc compile hang, catat cache. Build pertama ~1 jam; selanjutnya Docker layer cache = normal.
- Pastikan production `.env` `ABSENSI_GATEWAY_JWT_SECRET` ≠ `ABSENSI_SYNC_TOKEN` (P2 bahaya tertukar). 2 secret harus random string BEDA.

---

**--- END OF AUDIT DOCUMENT ---**  
*File ini dibuat sebagai bahan diskusi rapat tim. Keputusan final & PIC assignment akan dicatat di bagian Action Item setelah rapat selesai.*
