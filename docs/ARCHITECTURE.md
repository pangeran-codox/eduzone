# ARCHITECTURE.md — EduZone

Dokumen ini merangkum arsitektur teknis EduZone secara lebih dalam dari PRD, ditujukan untuk developer/AI assistant yang perlu memahami *bagaimana* sistem bekerja, bukan cuma *apa* fiturnya.

---

## 1. Gambaran Umum Sistem

```
┌─────────────────────────────────────────────────────┐
│         Docker Environment (compose EduZone)        │
│                                                       │
│  ┌──────────┐   ┌──────────┐   ┌──────────────┐     │
│  │  nginx   │   │   app    │   │    vite      │     │
│  │:8083→80  │──▶│  php-fpm │   │ :5174 (dev)  │     │
│  └──────────┘   │  :9000   │   └──────────────┘     │
│                 └────┬─────┘                         │
│  ┌──────────┐        │        ┌──────────────┐       │
│  │  reverb  │        │        │    queue     │       │
│  │  :8082   │        │        │  (Horizon)   │       │
│  └──────────┘        │        └──────────────┘       │
│  ┌──────────┐        │                               │
│  │scheduler │        │                               │
│  └──────────┘        │                               │
└───────────────────────┼───────────────────────────────┘
                        │
┌───────────────────────┼───────────────────────────────┐
│  Infrastructure (shared, compose terpisah di           │
│  C:\opt\docker\infrastructure\)                        │
│  ┌──────────┐  ┌──────────────┐                        │
│  │ postgres │  │    redis     │                        │
│  │  :5432   │  │    :6379     │                        │
│  └──────────┘  └──────────────┘                        │
│  nginx-proxy-manager (:80/:443) · adminer (:8081)      │
│  uptime-kuma (:3001) · crowdsec                        │
└─────────────────────────────────────────────────────────┘
```

**Reverb TIDAK lagi shared infrastructure** — sempat di situ, tapi sudah dipindah ke compose EduZone sendiri (`docker-compose.yml` root project, service `reverb`, build dari `./docker/reverb`). Alasannya (persis sesuai komentar di compose-nya): Reverb terikat ke **satu instalasi Laravel spesifik** (App key, broadcast config, dst — nggak genuinely multi-tenant lintas project), beda dari Postgres/Redis yang memang dipakai bersama banyak project (EduZone, Lab Management, dll) dan cocok tetap di infrastructure shared. Cuma **Postgres dan Redis** yang masih benar-benar shared infrastructure sekarang.

---

## 2. Database Absensi (`eduzone_absensi`) — Terpisah dari Database Utama

Modul Absensi adalah modul pertama yang digarap (lihat PRD), dan didesain sebagai **sistem absensi multi-metode** (RFID, QR scan, face recognition, kiosk manual) plus geofencing GPS untuk check-in guru — bukan sekadar catat hadir/tidak. Karena volume event tinggi (ratusan tap/scan per menit saat jam masuk sekolah), databasenya dipisah secara fisik dari database utama EduZone (`createdb eduzone_absensi`, database baru, bukan schema baru di DB yang sama — supaya resource, backup, dan risiko locking benar-benar terisolasi).

### 2.1 Prinsip Desain

- **Tanpa foreign key lintas-database.** Referensi ke `school_id`/`student_id`/`teacher_id` disimpan sebagai UUID polos, divalidasi & disinkron lewat job/queue dari Laravel utama — bukan constraint SQL lintas database (memang tidak didukung Postgres antar database berbeda).
- **Cache lokal ringan** (`schools_ref`, `people_ref`) dari data master di DB utama, disinkron satu arah (utama → absensi) secara berkala atau via event. Tujuannya validasi cepat tanpa cross-database query tiap kali ada event absensi.
- **Raw log immutable + agregat terpisah.** `attendance_events` menyimpan setiap tap/scan apa adanya (termasuk yang gagal/diragukan) dan tidak pernah di-update — sumber kebenaran untuk audit & anti-fraud. `attendance_daily` adalah hasil olahan (upsert) dari event tersebut, satu baris per orang per hari, sengaja dibuat mirip struktur `student_attendance`/`teacher_attendance` di DB utama supaya sinkronisasi jadi mapping 1:1.
- **Staged rollout untuk lapisan keamanan.** Beberapa tabel/kolom (hash chaining `row_hash`/`prev_hash`, `device_keys` untuk device signing, `qr_tokens` rotating, `attendance_correction_log` untuk approval koreksi, `local_verifiers` + `presence_tickets` untuk mengatasi CGNAT ISP residensial) **sudah disiapkan strukturnya di schema, tapi belum diaktifkan logikanya.** Fokus tahap pertama: sistem absen (RFID/QR/Face + geofencing GPS guru) jalan dulu dengan baik.

### 2.2 Tabel Utama

| Tabel | Fungsi |
|---|---|
| `schools_ref`, `people_ref` | Cache read-only dari data master DB utama (sekolah, siswa/guru/staf), termasuk titik GPS & radius geofence sekolah |
| `school_networks` | Daftar IP/hostname yang diizinkan per sekolah untuk validasi check-in guru via HP (bisa lebih dari satu ISP) — **aktif dipakai dari awal** |
| `devices` | Terminal absensi fisik (kamera face-recognition, RFID reader, QR scanner, kiosk manual) |
| `credentials` | Metode absensi tiap orang (hash UID RFID / token QR), satu orang bisa punya beberapa metode |
| `face_templates` | Embedding wajah terenkripsi, dipisah dari `credentials` karena data biometrik butuh perlakuan khusus — mengikuti pola `*_sensitive_data` yang sudah ada di DB utama |
| `attendance_events` | Raw log immutable, insert-only, semua tap/scan/deteksi termasuk yang gagal |
| `attendance_daily` | Agregat harian per orang, hasil upsert dari `attendance_events` |
| `sync_log` | Jejak sinkronisasi `attendance_daily` → `student_attendance`/`teacher_attendance` di DB utama |

Tabel lapisan keamanan lanjutan (belum aktif): `device_keys`, `qr_tokens`, `attendance_correction_log`, `local_verifiers`, `presence_tickets` — strukturnya sudah ada supaya tidak perlu migrasi besar nanti, tapi logikanya baru diimplementasikan saat lapisan ini benar-benar dipakai.

### 2.3 Alur Data

```
1. Device (RFID/QR/Face) → POST event mentah ke API absensi
   → INSERT ke attendance_events (selalu insert, walau gagal dikenali)

2. Worker/trigger mengolah event terbaru per (school_id, person_id, date)
   → UPSERT ke attendance_daily (hitung first_check_in, last_check_out,
     status, deteksi anomali seperti duplicate tap < 5 detik)

3. Job terjadwal (Laravel queue) baca attendance_daily yang belum sync
   → upsert ke student_attendance / teacher_attendance di DB utama
   → catat hasil di sync_log

4. schools_ref & people_ref disinkron SATU ARAH dari DB utama ke DB ini
   (bukan sebaliknya) — data induk (nama siswa, kelas) tetap DB utama
   EduZone sebagai source of truth.
```

### 2.4 Implikasi Teknis

- Query yang butuh gabungan data Absensi dengan data di database utama (mis. nama siswa lengkap dari `students`) tidak bisa pakai join SQL lintas database — pakai data dari `people_ref` (cache lokal) atau join di level aplikasi.
- Koneksi database kedua **sudah dikonfigurasi** sebagai connection `pgsql_absensi` di `config/database.php` (env `DB_ABSENSI_*`). Semua model modul Absensi eksplisit set `protected $connection = 'pgsql_absensi';` — lihat §2.5 untuk detail implementasi.
- `attendance_events` bersifat insert-only — jangan buat endpoint yang meng-UPDATE atau DELETE baris di tabel ini secara langsung. **Sudah di-enforce di level model** (`App\Models\Absensi\AttendanceEvent` melempar `RuntimeException` kalau ada yang coba `update()`/`delete()`) sebagai lapisan pertama, sebelum nanti ditambah `REVOKE UPDATE, DELETE` di level database role kalau alur insert-only sudah teruji lebih jauh.
- Koreksi data absensi (mis. wali kelas mengubah status siswa) di versi awal masih langsung/manual; alur approval via `attendance_correction_log` baru diaktifkan belakangan — jangan bangun UI approval untuk ini di tahap pertama kecuali diminta.
- Geofencing guru pakai kombinasi GPS (radius dari `schools_ref`) + validasi IP dari `school_networks`. Untuk sekolah dengan ISP residensial ber-CGNAT (IP publik tidak stabil), solusinya `local_verifiers` — tapi ini juga belum aktif; tahap awal cukup GPS + IP publik yang stabil.

### 2.5 Status Implementasi (progress log)

**Sudah selesai (terakhir diperbarui: termasuk update setelah 26 Juli 2026):**

- Database `eduzone_absensi` dibuat di instance Postgres shared yang sama (`postgres` container).
- Koneksi `pgsql_absensi` terdaftar di `config/database.php` + `.env` (`DB_ABSENSI_CONNECTION`, `DB_ABSENSI_HOST`, dst — host tetap `postgres`, cuma nama database beda).
- **19 migration file** dibuat di `database/migrations/`, penomoran `2025_01_01_000044` s.d. `2025_01_01_000062` (lanjut dari 43 migration DB utama yang sudah ada), tiap file pakai `protected $connection = 'pgsql_absensi';`. Sudah dijalankan (`php artisan migrate`) dan **berhasil DONE semua tanpa error**.
  - Update setelah 26 Juli 2026 (3 migration tambahan):
    - `000060_add_photo_url_to_people_ref` — tambah kolom `photo_url` di `people_ref` (avatar/URL foto)
    - `000061_create_ref_sync_state_table` — tabel status sinkronisasi MASUK (Go menulis, Laravel cuma baca)
    - `000062_add_late_cutoff_time_to_schools_table` — tambah `late_cutoff_time` (time) di tabel `schools` DB utama (dipakai SyncController untuk laravel-sync-contract)
  - 16 migration awal: `schools_ref` → `people_ref` → `devices` → `device_keys` → `schedules_ref` → `school_networks` → `local_verifiers` → `credentials` → `face_templates` → `attendance_events` → `attendance_daily` → `attendance_period` → `attendance_correction_log` → `sync_log` → `qr_tokens` → `presence_tickets`.
- **17 model Eloquent** dibuat di `app/Models/Absensi/` (namespace `App\Models\Absensi`), plus trait `App\Models\Absensi\Concerns\HasCompositePrimaryKey` khusus untuk `PeopleRef` (satu-satunya tabel dengan composite primary key: `person_id` + `person_type` — Eloquent tidak native support ini, jadi `getKeyName()`/`setKeysForSaveQuery()`/dst di-override manual di trait tsb; konsekuensinya `PeopleRef::find($id)` **tidak bisa dipakai**, harus query lewat `where('person_id', ...)->where('person_type', ...)`).
  - Tambahan model baru (setelah 26 Juli): `RefSyncState` (read-only, ditulis oleh Go, model insert dilarang lewat `saving()` event)
- Model yang PK-nya UUID dan **di-generate lokal** (bukan sync dari DB utama) pakai trait `HasUuids` bawaan Laravel: `Device`, `DeviceKey`, `SchoolNetwork`, `LocalVerifier`, `Credential`, `FaceTemplate`, `AttendanceDaily`, `AttendancePeriod`, `AttendanceCorrectionLog`, `QrToken`, `PresenceTicket`. Model yang PK-nya **harus sama persis dengan ID di DB utama** (hasil sync, bukan generate baru) **tidak** pakai `HasUuids`: `SchoolRef` (`school_id`), `PeopleRef` (`person_id`), `SchedulesRef` (`schedule_id`).
- `AttendanceEvent` model sudah mengunci insert-only lewat model event `booted()` — `updating()`/`deleting()` melempar exception.
- **Ditemukan & diperbaiki 1 inkonsistensi:** migration awal buat `presence_tickets.used_by_event_id` sempat salah ditambahkan foreign key ke `attendance_events` — schema asli (dan tabel yang sudah di-Adminer-buat) **tidak** punya FK di kolom ini (beda dengan `qr_tokens.used_by_event_id` yang memang punya FK). Sudah dikoreksi supaya migration cocok 100% dengan schema asli.
- **Cross-check dengan `absensi-gateway`** (repo Go terpisah, folder lokal `absensi-gateway`, punya `main.go`, `auth.go`, `checkin_device.go`, `checkin_teacher.go`, `scheduling.go`, `docker-compose.yml`): repo ini sempat punya salinan schema sendiri (`01_schema.sql` & `absensi_schema.sql`, ternyata identik satu sama lain). **Keputusan:** `01_schema.sql` dihapus (duplikat), `absensi_schema.sql` dipertahankan sebagai **dokumentasi referensi read-only** untuk tim/kerja di sisi Go — bukan lagi dipakai buat provisioning. **Migration Laravel adalah satu-satunya sumber kebenaran** untuk schema `eduzone_absensi` sekarang; kalau ada perubahan kolom/tabel, ubah migration dulu, baru sinkronkan `absensi_schema.sql` supaya tetap akurat sebagai referensi.
- `absensi-gateway` juga punya `02_seed.sql` (data dummy testing: 1 sekolah di Surabaya, 1 siswa dummy, RFID gerbang & lab) — dipakai lokal via `docker-compose.yml` Go, tidak ada hubungannya dengan migration Laravel, aman dipakai untuk testing tapi jangan sampai ke-provision otomatis bareng schema production.

**Fitur Laravel side BARU (setelah 26 Juli 2026) — bagian monitoring & data master:**

- **Sync Pull Endpoint** (server-to-server, absensi-gateway Go → Laravel):
  - 3 endpoint di `routes/sync.php`, prefix `/api/internal/sync`, middleware `sync.token` (header `X-Sync-Token`, shared-secret TERPISAH dari JWT_SECRET guru, cek `VerifySyncToken.php` & `config/services.php`)
  - `GET /api/internal/sync/schools` — `SyncController::schools()`, response array JSON polos ASC by `updated_at`; skip sekolah tanpa `latitude`/`longitude`; include `late_cutoff_time` (nullable, null = gateway jangan hitung keterlambatan dulu)
  - `GET /api/internal/sync/people` — union `students` + `teachers` + `staff` DB utama; include `photo_url` via `Storage::disk('public')->url(...)` atau null
  - `GET /api/internal/sync/schedules` — `Schedule::whereNotNull('teacher_id')->whereNotNull('class_id')`; mapping nama hari Indonesia ke ISO 8601 day-of-week (Senin=1..Minggu=7); skip baris dengan nama hari yang tidak dikenali.
  - Kontrak lengkap: `docs/laravel-sync-contract.md` di repo absensi-gateway (JANGAN ubah response shape tanpa update dokumen itu juga).
- **Tabel `ref_sync_state`**: Ditulis oleh `internal/sync/puller.go` di sisi Go (menyimpan `last_synced_at`, `last_record_count`, `last_cursor` per resource). Model `RefSyncState` di Laravel **read-only** — `saving()` event melempar `RuntimeException`, jadi tidak ada kode Laravel yang boleh menulis ke sini.
- **Device Management UI** (Superadmin):
  - `DeviceController` full CRUD: index/create/store/edit/update/destroy + `POST /{device}/regenerate-key`
  - `api_key_hash` disimpan sebagai SHA-256 dari key mentah; key mentah CUMA ditampilkan SEKALI di flash session saat create/regenerate (tidak pernah disimpan plain)
  - `destroy()` catch `QueryException` untuk FK constraint (device sudah punya attendance_events) → arahkan nonaktifkan lewat edit, bukan crash stack trace
  - Validasi tipe device via `StoreDeviceRequest`/`UpdateDeviceRequest` (5 value valid: `face_camera`, `rfid_reader`, `qr_scanner`, `hybrid`, `manual_kiosk` — **catatan:** `rfid` BUKAN value valid, gampang salah tebak; HARUS sinkron dengan CHECK constraint `devices_device_type_check` di database)
- **Health Check Dashboard** (Superadmin + Tenant ringkas):
  - Service `HealthCheckService.php` — hasil di-cache 30 detik (`absensi:health:full`) supaya banyak tenant tidak bombardir gateway/DB
  - 3 komponen dicek: `gateway` (HTTP GET ke `/health` gateway → cek field `database` == 'ok', bukan cuma status code 200), `database` (select 1 ke `pgsql_absensi`), `schools` (per sekolah: `sync_fresh` = `synced_at` < 10 menit yang lalu [= 2x SYNC_INTERVAL gateway]; `devices_online` = `last_seen_at` < 5 menit lalu; total devices aktif)
  - Superadmin: `/superadmin/absensi/health` (AbsensiHealthController@index) & status JSON (`/status`)
  - Tenant (widget): `/absensi/health` (Tenant\AbsensiHealthController@status) — auto-scope ke `school_id` user login, cuma balas `ready: bool` + `message: string` (tidak expose URL gateway, dll)

**Belum dikerjakan (langkah selanjutnya yang logis):**

- Job sinkronisasi `attendance_daily`/`attendance_period` → `student_attendance`/`teacher_attendance` (dan tabel sejenis) di DB utama, tercatat di `sync_log`. **Belum ada di Laravel maupun `absensi-gateway`** — dikonfirmasi eksplisit di README `absensi-gateway` sebagai "sengaja belum dibuat", jadi ini murni pekerjaan yang masih kosong di kedua sisi.
- Halaman/dashboard staff untuk lihat rekap absensi (per Wali Kelas/Guru Mapel/TU) — kiosk device duluan yang selesai. Dashboard Wali Kelas (`routes/tenant.php` → `WaliKelas\AbsensiController@dashboard`) sudah mulai ada route + view placeholder.
- Halaman check-in guru via HP (geofencing GPS + JWT) — endpoint Go-nya (`POST /api/v1/checkin/teacher`) sudah ada, tapi **`GatewayTokenIssuer` service untuk menerbitkan JWT di sisi Laravel BELUM diimplementasikan** (nama kelasnya hanya direferensikan di `config/services.php` + docs, file kelasnya tidak ada di `app/Services/Absensi/` — yang ada baru `HealthCheckService.php`). JWT claims (`user_id`/`school_id`/`role`) harus sinkron manual dengan struct `middleware.TeacherClaims` di `auth.go` Go; secret shared via env `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) == `JWT_SECRET` (Go).
- 5 tabel dorman (`device_keys`, `qr_tokens`, `attendance_correction_log`, `local_verifiers`, `presence_tickets`) tetap belum diaktifkan logikanya, sesuai rencana awal.
- Face recognition: endpoint di gateway sudah ada tapi masih stub dummy (belum ada worker Python/InsightFace).
- **Bersih-bersih kecil yang masih tertunda:** route `POST /kiosk/{deviceCode}/checkin` di `routes/kiosk.php` dan `AttendanceRecorder.php`/`CheckInController::store()` di Laravel perlu dihapus — sudah digantikan penuh oleh `absensi-gateway`, dibiarkan menggantung berisiko jadi write path ganda kalau nggak sengaja terpanggil.

### 2.6 Integrasi dengan `absensi-gateway` (Go) — SUDAH JALAN END-TO-END (26 Juli 2026)

Setelah membaca kode asli `absensi-gateway` (`main.go`, `auth.go`, `checkin_device.go`, `README.md`), ketauan gateway ini **jauh lebih matang** dari perkiraan awal — bukan cuma skeleton, tapi sudah py transaksi + advisory lock + deteksi anomali + resolve jadwal aktif untuk check-in device. **Keputusan final: `absensi-gateway` adalah write path resmi untuk check-in device (RFID/QR/Face)**, bukan Laravel. Laravel cuma nge-render halaman kiosk-nya (Blade + JS), request check-in-nya langsung ke gateway.

**Yang sudah selesai & terbukti jalan (end-to-end, bukan cuma unit test):**

- **Halaman kiosk** (`resources/views/kiosk/checkin.blade.php` + `resources/js/areas/kiosk.js`) — RFID & QR aktif, toggle Masuk/Pulang (karena `event_type` wajib dikirim eksplisit oleh client, tidak di-infer server-side), tab Manual & Wajah masih placeholder (gateway belum dukung method `manual`, Face masih stub tanpa worker Python).
- **Route Laravel** `routes/kiosk.php` — `GET /kiosk/{deviceCode}` doang yang aktif dipakai (render halaman). Route `POST .../checkin` yang sempat dibuat sekarang **tidak dipakai** (lihat item bersih-bersih di §2.5).
- **Integrasi JWT Laravel ↔ Go** (buat nanti dipakai check-in guru via HP): `firebase/php-jwt` BELUM terpasang di `composer.json` (package yang ada baru `google/protobuf` + `grpc/grpc` untuk encryption service), dan **class `GatewayTokenIssuer` BELUM diimplementasikan** (nama cuma direferensikan di `config/services.php` + docs; `app/Services/Absensi/` saat ini HANYA berisi `HealthCheckService.php`). Begitu class ini dibuat, ia harus menerbitkan token HS256 dengan claims `user_id`/`school_id`/`role` — HARUS sinkron manual dengan struct `middleware.TeacherClaims` di `auth.go` Go (tidak ada shared schema antar keduanya). Secret sama-sama di-set lewat env var `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) dan `JWT_SECRET` (Go, `.env` gateway) — **nilainya harus identik**.
- ⚠️ **Dua shared-secret TERPI SIH untuk dua use case berbeda — JANGAN tertukar:**
  - `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) ↔ `JWT_SECRET` (Go): untuk menerbitkan & memverifikasi **JWT guru** (HS256, claim user_id/school_id/role). Ini dipakai endpoint Go `/api/v1/checkin/teacher`.
  - `ABSENSI_SYNC_TOKEN` (Laravel) ↔ `LARAVEL_SYNC_TOKEN` (Go): untuk verifikasi header **`X-Sync-Token`** request pull dari gateway ke endpoint Laravel `/api/internal/sync/*`. Diverifikasi oleh middleware `VerifySyncToken`.
  Kalau salah satu bocor, **hanya use case tersebut yang terdampak** (itulah alasan dipisah, bukan pakai secret yang sama untuk dua arah).
- **⚠️ Risiko keamanan yang didokumentasikan gateway sendiri (README poin 8), belum diperbaiki:** JWT guru pakai *shared secret* HS256 antara Laravel & gateway. Kalau secret bocor dari sisi gateway, penyerang bisa menerbitkan token palsu untuk **seluruh sistem Eduzone**. Solusi jangka panjang: pindah ke RS256 (Laravel pegang private key, gateway cuma pegang public key buat verifikasi). Ini butuh perubahan di sisi Laravel (`GatewayTokenIssuer`) yang belum dikerjakan — dicatat di README gateway supaya tidak terlupakan.
- **Proxy Nginx Proxy Manager**: Custom Location `/gateway` di host `eduzone.local` → `absensi-gateway:8080`, dengan `rewrite ^/gateway/(.*)$ /$1 break;` untuk strip prefix sebelum masuk ke Go (karena route Go didaftarkan sebagai `/api/v1/...` polos, bukan `/gateway/api/v1/...`).
  - ⚠️ Endpoint sync `/api/internal/sync/*` **TIDAK** lewat `/gateway` — ini request DARI gateway KE Laravel (server-to-server, langsung ke nama container/service `eduzone_nginx` atau NPM host), bukan dari browser. Jadi penulisan header `X-Sync-Token` dan URL targetnya diatur di konfigurasi Go, bukan di NPM frontend.
  - **Jebakan NPM yang ditemukan:** ada 2 tempat berbeda buat "advanced config" di NPM — tab **"Advanced"** di level host (nempel di level `server {}` Nginx, dieksekusi SEBELUM location matching) vs gear (⚙) di baris Custom Location itu sendiri (nempel di DALAM `location { }` yang bersangkutan). Rewrite prefix-stripping **wajib** di taruh di yang kedua (per-location) — kalau ketaruh di tab host-level, request akan salah location-matching dan jatuh ke `location /` (fallback ke Laravel), menghasilkan 404 dari Laravel sendiri (bukan 502 dari Nginx), yang sempat bikin bingung karena keliatannya seperti masalah routing Laravel padahal sebenarnya masalah scope rewrite Nginx.
- **Device testing:** `GATE-01` (device umum, gerbang) sudah berhasil check-in end-to-end via browser kiosk maupun `curl`/`Invoke-RestMethod` langsung, pakai data dummy dari `02_seed.sql` (`CARD-ANDI-001` / `DEVKEY-GERBANG-01`).

---

## 3. Multi-Tenancy

**Model:** shared database, single schema. Semua sekolah berbagi satu database Postgres; isolasi dilakukan di level aplikasi lewat kolom `school_id` dan Eloquent global scope — bukan lewat database/schema terpisah per tenant.

**Alur penentuan tenant:**
```
Request masuk
    │
    ▼
AuthUserTenantFinder
    │
    ├─ user.role == 'superadmin' ──▶ tenant = null (akses semua sekolah)
    │
    └─ user.school_id ada ──────────▶ School::find(school_id) jadi current tenant
                                              │
                                              ▼
                                      SchoolScope aktif
                                      (semua query otomatis
                                       WHERE school_id = ?)
```

Komponen kunci:
- `App\Multitenancy\TenantFinder\AuthUserTenantFinder` — menentukan tenant dari user yang login.
- `App\Multitenancy\Scopes\SchoolScope` — global scope yang otomatis filter query berdasarkan `school_id`.
- `App\Multitenancy\Concerns\BelongsToSchool` — trait yang harus dipakai semua model tenant; menyediakan scope otomatis, auto-fill `school_id` saat create, dan relasi `school()`.
- Middleware `InitializeTenancy` (alias `tenant`) — men-set tenant + `search_path` Postgres di awal request.

Superadmin tidak terikat tenant manapun; bisa query lintas sekolah dengan `Model::withoutTenant()`.

---

## 4. Data Sensitif & Enkripsi

Data sensitif siswa/guru/staf (tabel `*_sensitive_data`) tidak dienkripsi langsung di Laravel, melainkan lewat service gRPC terpisah:

- Kontrak service didefinisikan di `proto/encryption.proto`.
- Laravel memanggilnya lewat `App\Services\EncryptionGrpcService` dan cast `App\Casts\EncryptedAttribute`.
- Implementasi service encryption-nya sendiri berada di **Rust** (di luar repo Laravel ini), sejalan dengan arsitektur polyglot EduZone yang lebih besar.
- `grpc_worker.php` di root project menjadi entry point worker PHP yang berkomunikasi dengan service ini.

Alasan dipisah dari aplikasi utama: memisahkan kunci/logic enkripsi dari codebase aplikasi, dan performa operasi kriptografi lebih baik dilakukan di Rust dibanding PHP.

---

## 5. Layanan High-Concurrency (Go Fiber) — Sudah Discaffold

Beberapa endpoint yang butuh concurrency tinggi (contoh yang sudah dibahas: bulk attendance) dilayani microservice terpisah berbasis **Go Fiber**, bukan langsung lewat Laravel. Laravel tetap menjadi aplikasi inti (core app, admin, CRUD reguler); Go menangani endpoint yang butuh throughput tinggi.

**Status saat ini:** baru skeleton — `main.go` cuma punya endpoint `/health` dan `/api/v1/ping`, belum ada logic bisnis apapun (belum nyentuh `eduzone_absensi` atau service encryption). Tapi infrastrukturnya sudah jalan: Dockerfile multi-stage (`golang:1.22-alpine` build → `alpine:latest` run, binary statis, non-root user), docker-compose sendiri (`eduzone_go`, port host `3002` → container `3000`), healthcheck aktif.

**Struktur:** `go.mod` bernama `github.com/eduzone/go-service`, pakai Fiber v2.52.5. Ini **repo/folder terpisah dari Laravel** (`go/` — punya `go.mod`, `go.sum`, `main.go` sendiri), bukan bagian dari codebase PHP. Deploy-nya juga independen — punya `docker-compose.yml` sendiri, connect ke network `network` yang sama supaya bisa saling akses dengan `eduzone_app` nanti.

---

## 6. Struktur Docker & Deployment

**Prinsip saat ini:** `Dockerfile` dan `docker-compose.yml` (untuk service `app`, `vite`, `nginx`, `queue`, `scheduler`) **digabung ke dalam repo Laravel ini** — beda dari draf awal yang memisahkannya total ke `C:\opt\docker\eduzone\`. Alasan pindah: perubahan kode sering butuh perubahan Dockerfile/compose barengan (nambah PHP extension, ganti dependency Node, dst), jadi lebih aman satu repo/satu histori git, dan lebih gampang di-debug karena semua file yang relevan ada di satu tempat.

```
C:\laragon\www\eduzone\              ← source code Laravel (repo ini)
├── Dockerfile                       ← multi-stage: node-builder → base → development/production
├── docker-compose.yml               ← app, vite, nginx, queue, scheduler, reverb — satu file
├── docker\                          ← config yang di-copy ke image saat build
│   ├── nginx\default.conf
│   ├── php\php-dev.ini, php-fpm.conf, php-prod.ini
│   ├── postgres\...
│   └── reverb\Dockerfile            ← build context service reverb (lihat §1 - pindah dari infra shared)
└── .env                             ← dipakai BARENG oleh Laravel (artisan) & Docker Compose
                                        (docker compose otomatis baca .env di folder yang sama)
```

**Yang TETAP terpisah** (infrastruktur shared, dipakai bareng project lain seperti Lab Management):
```
C:\opt\docker\infrastructure\
├── network\     → docker-compose.yml (network bridge lokal) + docker-compose.swarm.yml (overlay, buat Proxmox nanti)
├── postgres\    → container `postgres`, image postgres:16-alpine, healthcheck aktif
├── redis\       → container `redis`, image redis:7-alpine, tanpa auth secara default
├── adminer\     → container `adminer`, GUI database di :8081, default server `postgres`
└── (rencana ke depan: nginx-proxy-manager\, crowdsec\, uptime-kuma\)
```

> **Reverb bukan lagi bagian dari folder infrastructure ini** — sudah dipindah jadi service dalam `docker-compose.yml` EduZone sendiri (lihat §1). Kalau masih nemu referensi `reverb\` di folder infrastructure lama, itu sisa struktur sebelum migrasi dan aman dihapus/diabaikan.

`docker-compose.yml` di repo EduZone terhubung ke service-service ini lewat Docker network bernama `network` (driver **bridge**, subnet `172.20.0.0/16`) yang didefinisikan `external: true` — network-nya sendiri dibuat oleh compose infrastructure (`network/docker-compose.yml`), bukan oleh compose EduZone, dan harus dibuat **paling pertama** sebelum service lain manapun di-`up`.

> **Bridge vs overlay:** setup saat ini pakai network **bridge** biasa (`docker-compose.yml`), bukan **overlay**. Overlay network cuma berlaku di mode Docker Swarm — folder `network\` sudah menyiapkan `docker-compose.swarm.yml` (driver `overlay`, nama `shared_network`) untuk dipakai nanti saat deploy ke Proxmox pakai Swarm, tapi belum relevan untuk development lokal.

**Kredensial Postgres shared:** satu instance dipakai banyak project — `POSTGRES_USER=laravel`, `POSTGRES_PASSWORD` di-set di `.env` folder infra (default `secret123`, sebaiknya diganti untuk lingkungan yang lebih dari sekadar lokal-dev). Database per-project dibuat otomatis oleh `postgres/init/01-create-databases.sh` saat volume Postgres pertama kali dibuat — daftar saat ini: `lab_management`, `finance`, `eduzone`. **`eduzone_absensi` (§2) belum ada di daftar ini** — perlu ditambahkan manual ke script atau lewat `CREATE DATABASE` manual saat modul Absensi mulai butuh database keduanya.

**Service Go (`eduzone-go-service`)** juga tetap **terpisah** dari repo Laravel ini (lihat §5) — beda bahasa/codebase, siklus deploy independen, tapi tetap nempel ke network `network` yang sama.

**Dockerfile multi-stage:**
```
Stage 1: node-builder    → build asset Vite (npm ci --frozen-lockfile && npm run build)
Stage 2: base            → PHP 8.3-FPM Alpine + extensions
Stage 3: development     → composer install (dengan dev deps) + php-dev.ini
Stage 4: production      → composer install --no-dev, cache:artisan, optimized
```
Extensions aktif: `pdo_pgsql`, `pgsql`, `gd`, `zip`, `mbstring`, `bcmath`, `opcache`, `intl`, `pcntl`, `redis`.
**`grpc` & `protobuf` SENGAJA DITUNDA (dicomentar)** — compile dari source di Alpine ~1 jam dan belum ada controller/flow yang benar-benar memakai `EncryptionGrpcService` atau `grpc_worker.php`. Cuma package `grpc/grpc` + `google/protobuf` di `composer.json` yang sudah ada; `composer install` diberi `--ignore-platform-req=ext-grpc` sebagai bypass sementara. Begitu integrasi encryption Rust dimulai, aktifkan lagi sesuai instruksi komentar di `Dockerfile` (tambah `grpc protobuf` ke `pecl install`, tambah `linux-headers` ke `.build-deps`, hapus `--ignore-platform-req=ext-grpc`). User non-root (`laravel`, uid 1000) dipakai di semua stage.

**Service dalam `docker-compose.yml`:**
| Service | Image/Build | Fungsi |
|---|---|---|
| `app` | Build dari `Dockerfile`, tag `eduzone-app:{target}` | PHP-FPM, port internal 9000 |
| `vite` | `node:20-alpine` | Dev server Vite (`npm install && npm run dev -- --host`), port `5174` |
| `nginx` | `nginx:1.27-alpine` | Reverse proxy, port host `8083` |
| `queue` | Image sama dengan `app` (reuse, nggak build ulang) | `php artisan horizon` |
| `scheduler` | Image sama dengan `app` | Loop `php artisan schedule:run` tiap 60 detik |

`queue` dan `scheduler` sengaja **tidak punya `build:` sendiri** — mereka pakai `image: eduzone-app:${APP_TARGET}` yang sama dengan service `app`, supaya nggak build 3x dari Dockerfile yang identik.

**Variable Docker Compose** (`APP_TARGET`, `VITE_PORT`, `NGINX_PORT`) didaftarkan di `.env`/`.env.example` yang sama dengan variable Laravel — satu file, satu sumber kebenaran, karena `docker-compose.yml` sekarang satu folder dengan `.env` itu.

**Implikasi penting:** perubahan file di dalam container (`docker exec -it eduzone_app sh` lalu edit langsung) bersifat sementara — Dockerfile & source sekarang di repo yang sama, jadi alur normalnya edit source → rebuild (`docker compose build app` atau `docker compose up -d --build`), bukan edit langsung di container.

**Gotcha yang pernah kejadian:** container `vite` pakai `npm install` (bukan `npm ci`) secara sengaja — `npm ci` butuh `package-lock.json` sinkron persis dengan `package.json`, dan itu gampang pecah kalau ada yang nambah dependency manual ke `package.json` tanpa regenerate lockfile (pernah bikin container `eduzone_vite` exit code 1). Stage `node-builder` di Dockerfile (buat build production) tetap pakai `npm ci --frozen-lockfile` karena di situ reproducibility lebih penting daripada toleransi.

---

## 7. Autentikasi & Middleware Stack

Dua area login independen:
| Area | URL | Karakteristik |
|---|---|---|
| Tenant | `/login` | Untuk semua role sekolah |
| Superadmin | `/superadmin/login` | Dark theme, rate-limited 5x/menit/IP |

Middleware chain untuk route tenant:
```php
Route::middleware(['auth', 'active', 'tenant', 'role:kepsek'])->group(...);
```
- `auth` — user sudah login
- `active` (`EnsureUserIsActive`) — akun `is_active = true`
- `tenant` (`InitializeTenancy`) — set search_path Postgres + resolve tenant
- `role:xxx` (`RoleMiddleware`) — cek role, bisa multi-role dipisah koma

Route superadmin pakai middleware `superadmin` (`SuperadminOnly`) — redirect ke `/superadmin/login` kalau belum auth.

---

## 8. Observability

| Tool | Fungsi | Akses |
|---|---|---|
| Laravel Horizon | Monitor queue, job, throughput | `/horizon`, superadmin only |
| Laravel Telescope | Debug request, query SQL, exception, mail | `/telescope`, superadmin only, semua environment |
| Debugbar | Query real-time per halaman | Otomatis di `APP_ENV=local` |

---

## 9. Batasan & Hal yang Belum Terkonfirmasi

- Service Go Fiber sudah discaffold (lihat §5) tapi baru endpoint health-check/ping — belum ada logic bisnis. Rust encryption engine masih belum terlihat di repo manapun yang sudah dibagikan — perlu diverifikasi lokasinya.
- Baru 6 controller yang benar-benar ada; sebagian besar modul di PRD baru sebatas schema database. **Ini disengaja** — EduZone adalah rebuild dari sistem sekolah single-tenant sebelumnya, dan skema database dirancang menyeluruh di awal sebelum controller/UI per modul mulai dibangun bertahap.
- Schema `eduzone_absensi` (§2), koneksi database kedua, migration, dan model Eloquent modul Absensi **sudah selesai** — lihat progress log lengkap di §2.5. Yang masih tersisa: job sinkronisasi ke DB utama, controller/route/view, dan pemetaan pembagian kerja dengan service Go `absensi-gateway` yang juga sudah mulai punya beberapa file logic (`auth.go`, `checkin_device.go`, `checkin_teacher.go`, `scheduling.go`) — isinya belum direview detail.
