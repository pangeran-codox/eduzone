---
name: eduzone-dev
description: Panduan kerja untuk membantu development platform EduZone — SaaS manajemen sekolah multi-tenant berbasis Laravel 11 + PostgreSQL + Docker. Gunakan skill ini setiap kali mengerjakan kode di project EduZone: membuat/mengubah model, migration, controller, route, view Blade, konfigurasi Docker, atau apa pun yang menyentuh folder app/, database/, routes/, resources/, atau docker/ di repo ini. Wajib dipakai sebelum membuat model atau tabel baru karena ada aturan wajib (UUID, BelongsToSchool trait, multi-tenancy scope) yang gampang terlewat.
---

# EduZone — Panduan Development

EduZone adalah platform SaaS multi-tenant untuk manajemen sekolah (akademik, absensi, keuangan, kesiswaan, lab, ujian) — **rebuild total** dari sistem sekolah single-tenant sebelumnya (CodeIgniter). Satu database dipakai bersama oleh banyak sekolah (tenant), diisolasi lewat kolom `school_id` + global scope.

Stack: Laravel 11 (PHP 8.3), PostgreSQL 16, Redis, Nginx, Vite, Tailwind + Alpine.js, spatie/laravel-multitenancy v4, Laravel Horizon, Laravel Reverb (WebSocket), gRPC ke service encryption (Rust), microservice Go Fiber (scaffold, belum ada logic bisnis) + `absensi-gateway` (Go, sudah jalan production untuk modul Absensi).

Baca `README.md` (setup & konvensi detail), `ARCHITECTURE.md` (arsitektur teknis mendalam), `FRONTEND.md` (konvensi Vite/Alpine), dan `PRD.md` (product requirements & status modul) di root project untuk detail lengkap. Skill ini merangkum aturan yang paling gampang dilanggar atau paling sering dibutuhkan saat coding.

## Konteks: ini rebuild bertahap, bukan project tertinggal

Skema database (39+ tabel) sengaja dirancang menyeluruh **di awal**, sebelum controller/UI dibangun. Modul dibangun satu per satu — **Absensi adalah modul pertama** yang digarap, dipilih supaya EduZone bisa mulai dipakai sekolah lebih cepat sambil memvalidasi fondasi (auth, multi-tenancy, docker). Kalau diminta bantuan "fitur apa selanjutnya" atau ada ambiguitas prioritas, condongkan ke Absensi dulu. Modul lain (Akademik, Penilaian, Kesiswaan, Lab, Keuangan, Pengumuman) baru sebatas migration/model — cek dulu apakah controller & route-nya sudah ada sebelum asumsi "lanjutkan fitur X" berarti modul itu sudah waktunya digarap.

## Role & Area Akses

Dua area terpisah:
- **Superadmin** — penyedia platform, tidak terikat tenant manapun, akses lintas sekolah via `withoutTenant()`.
- **Tenant** (9 role sekolah) — satu user bisa punya lebih dari satu role di middleware (`role:guru_mapel,wali_kelas`):

| Role | Slug | Tanggung Jawab |
|---|---|---|
| Kepala Sekolah | `kepsek` | Pengawasan operasional sekolah |
| Kurikulum | `kurikulum` | Jadwal pelajaran, konfigurasi nilai |
| Tata Usaha | `tu` | Administrasi umum, data induk |
| Guru Mapel | `guru_mapel` | Nilai, jurnal mengajar, absensi mapel |
| Wali Kelas | `wali_kelas` | Kelas, absensi harian, sikap siswa |
| Kesiswaan | `kesiswaan` | Prestasi, rekam jejak, pelanggaran |
| Bimbingan Konseling | `bk` | Sesi konseling siswa |
| Toolman | `toolman` | Booking & inventaris lab |
| Siswa | `siswa` | Nilai, jadwal, presensi diri |

## Aturan Wajib — Model & Migration Baru

Setiap kali membuat model yang punya kolom `school_id` (hampir semua model tenant):

1. Primary key **UUID**, bukan auto-increment — kecuali tabel keuangan (`dana_bos`, `transaksi_pemasukan`, `transaksi_pengeluaran`, `pengajuan_anggaran`, `kategori_pemasukan`, `kategori_pengeluaran`) dan `roles`, yang memang pakai integer PK.
2. Pakai trait `App\Multitenancy\Concerns\BelongsToSchool` + `Illuminate\Database\Eloquent\Concerns\HasUuids`.
3. Set `$incrementing = false` dan `$keyType = 'string'`.
4. Tambahkan `school_id` ke `$fillable`.
5. **Jangan** definisikan ulang relasi `school()` — sudah disediakan trait `BelongsToSchool`.

Migration:
```php
$table->uuid('id')->primary();
$table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
```
Jangan pakai `foreignId()` untuk kolom yang seharusnya UUID.

Template model lengkap ada di README bagian "Panduan Model & Eloquent".

## Query & Tenant Scope

- Query model tenant otomatis terfilter `WHERE school_id = ?` lewat `SchoolScope` — tidak perlu filter manual.
- Untuk query lintas sekolah (khusus superadmin): `Model::withoutTenant()->...`.
- Sebelum raw query via `DB::table()`, pastikan search_path Postgres benar: `DB::statement('SET search_path TO public');`.
- Operasi yang menyentuh lebih dari satu tabel → bungkus dengan `DB::transaction()`.
- Data besar → pakai `chunk()`, jangan `->all()` atau `->get()` polos.
- Alur penentuan tenant: `AuthUserTenantFinder` — superadmin → tenant `null`; user dengan `school_id` → `School::find(school_id)` jadi tenant aktif → `SchoolScope` otomatis aktif. Middleware `InitializeTenancy` (alias `tenant`) yang men-set ini + `search_path` di awal request.

## Routing

Pola nama route: `{area}.{resource}.{action}`, contoh `superadmin.schools.index`, `guru.grades.store`.

- `routes/tenant.php` → fitur milik role sekolah, wajib dibungkus middleware `['auth', 'active', 'tenant']` + `role:xxx` per grup.
- `routes/superadmin.php` → fitur superadmin, middleware `superadmin`.
- `routes/web.php` → landing page, login/logout tenant, router `/dashboard`.
- `routes/kiosk.php` → khusus layar kiosk absensi, pakai middleware `web` (session) tapi TANPA `auth`/`tenant` (identitas sekolah dari kode device di URL, bukan login user). Lihat bagian Absensi.
- `routes/sync.php` → khusus server-to-server sync pull dari absensi-gateway Go, prefix `/api/internal/sync`, middleware `sync.token` SAJA (tanpa `web`, tanpa session/CSRF). JANGAN tambah route lain ke file ini — isinya 3 endpoint doang: schools, people, schedules (lihat bagian Sync Controller & Verify Sync Token di bab Modul Absensi).

Jangan taruh route tenant baru di `web.php` — akan lolos dari prefix & middleware otomatis yang di-setup di `bootstrap/app.php`.

Dua area login independen: `/login` (tenant, semua role) dan `/superadmin/login` (dark theme, rate-limited 5x/menit/IP).

## Alur Menambah Fitur Baru

1. Migration → `php artisan make:migration create_xxx_table`
2. Model → `php artisan make:model Xxx` lalu terapkan aturan di atas
3. Controller → `php artisan make:controller Xxx/XxxController --resource`
4. Form Request untuk validasi → `php artisan make:request StoreXxxRequest`
5. Route di `routes/tenant.php` atau `routes/superadmin.php` (bukan `web.php`)
6. View di `resources/views/tenant/{role}/{feature}/`
7. Build asset: `docker exec eduzone_vite npm run build`

## Konvensi Penamaan

| Tipe | Konvensi | Contoh |
|---|---|---|
| Model | PascalCase singular | `SchoolClass`, `StudentGrade` |
| Tabel | snake_case plural | `student_grades` |
| Kolom boolean | prefix `is_`/`has_` | `is_active` |
| Foreign key | `{model}_id` | `teacher_id` |
| View | snake_case | `student_grade.blade.php` |

## Frontend — Vite per Area, Alpine.js untuk Interaktivitas

Entry point JS/CSS di-organize **per area**, bukan satu bundle global atau per-modul: `resources/js/areas/superadmin.js`, `tenant.js`, `kiosk.js` (khusus device absensi). CSS tetap satu entry global (`resources/css/app.css`) untuk semua area — jangan dipecah, Tailwind sudah efisien lewat content-scanning.

- Halaman baru di area yang sudah ada → pakai entry area itu (`@vite(['resources/css/app.css', 'resources/js/areas/{area}.js'])`), **jangan bikin entry baru**.
- Area baru yang benar-benar beda karakter (misal portal ortu, atau API-only) → baru bikin entry baru + daftarkan di `input` pada `vite.config.js`.
- Komponen lintas area (util formatting, helper fetch) → taruh di `resources/js/shared/`, jangan copy-paste ke tiap entry.
- Interaktivitas ringan (dropdown, modal, toggle) → Alpine.js (`x-data`, `x-show`, dst), sudah ter-load otomatis di area superadmin & tenant.
- Entry `kiosk.js` (layar RFID/QR/Face) sengaja **tidak** load Alpine — device fisik nyala berjam-jam, dijaga seringan mungkin. Kalau modul Absensi mulai butuh interaktivitas ringan di kiosk, evaluasi dulu — vanilla JS mungkin sudah cukup.
- Detail lengkap & alasan tiap keputusan ada di `FRONTEND.md`.

## Docker & Menjalankan Perintah

Semua perintah artisan/composer/npm dijalankan **di dalam container**, bukan di host:
```bash
docker exec eduzone_app php artisan <command>
docker exec -u root eduzone_app composer <command>
docker exec eduzone_vite npm run build       # production
docker exec -it eduzone_vite npm run dev     # dev, hot reload
```

**Dockerfile & docker-compose.yml SATU REPO dengan source code** (`C:\laragon\www\eduzone\`) — beda dari draf awal yang memisahkannya ke `C:\opt\docker\eduzone\`, dan beda dari pola project Lab Management (yang masih pisah app-code vs infra-config). Alasan pindah: perubahan kode sering butuh perubahan Dockerfile/compose barengan (nambah PHP extension, ganti dependency Node, dst), jadi lebih aman satu histori git, lebih gampang di-debug.

Implikasi: **edit file di dalam container itu sementara** — alur normalnya edit source → rebuild (`docker compose build app` atau `docker compose up -d --build`), bukan edit langsung lewat `docker exec -it eduzone_app sh`.

Container: `eduzone_app` (PHP-FPM), `eduzone_nginx` (:8083), `eduzone_vite` (:5174, dev only, pakai `npm install` bukan `npm ci` — sengaja, biar toleran kalau ada yang nambah dependency manual tanpa regenerate lockfile), `eduzone_queue` (Horizon, image sama dengan `app`, reuse), `eduzone_scheduler` (image sama dengan `app`, loop `schedule:run` tiap 60 detik), `reverb` (WebSocket, :8082 — sudah pindah ke compose EduZone sendiri, BUKAN lagi infrastruktur shared). Cuma **Postgres dan Redis** yang masih ada di infrastruktur shared (`C:\opt\docker\infrastructure\`), dipakai bareng project lain (Lab Management, dll). Network bridge bernama `network` (subnet `172.20.0.0/16`, `external: true`) harus dibuat lebih dulu sebelum service manapun di-`up`.

Database per-project dibuat otomatis oleh init script Postgres shared — daftar saat ini: `lab_management`, `finance`, `eduzone`. **`eduzone_absensi` belum ada di daftar ini** — kalau setup ulang dari nol, perlu ditambahkan manual ke script atau `CREATE DATABASE` manual.

## gRPC & Encryption Service — Sementara Dinonaktifkan di Dockerfile

Extension PHP `grpc`/`protobuf` **sengaja dikomentar/dilepas** dari `Dockerfile` (cuma `redis` yang aktif) — compile `grpc` dari source di Alpine makan waktu ~1 jam dan belum ada controller yang benar-benar makai `EncryptionGrpcService`/`grpc_worker.php`. `composer install` juga dikasih `--ignore-platform-req=ext-grpc` biar nggak nolak gara-gara package `grpc/grpc` di `composer.json` minta ekstensi itu aktif.

**Jangan coba pakai `EncryptionGrpcService` atau apapun yang butuh `ext-grpc` sebelum ini diaktifkan lagi** — bakal fatal error class/extension not found. Kalau mulai kerjain integrasi encryption service, aktifkan lagi sesuai instruksi komentar di `Dockerfile` (tambah `grpc protobuf` balik ke `pecl install`, tambah `linux-headers` ke `.build-deps`, hapus `--ignore-platform-req=ext-grpc`), lalu siap-siap nunggu compile ~1 jam sekali itu doang (abis itu ke-cache normal).

> ⚠️ Catatan konsistensi: `ARCHITECTURE.md` §6 mencantumkan `grpc`/`protobuf` di daftar extension Dockerfile. Kalau ternyata sudah benar-benar diaktifkan sejak catatan ini ditulis, cek langsung isi `Dockerfile` sebelum percaya bagian ini — jangan asumsikan otomatis nonaktif.

Implementasi encryption service sendiri ada di **Rust**, di luar repo Laravel ini, dipanggil lewat `App\Services\EncryptionGrpcService` + cast `App\Casts\EncryptedAttribute`. Kontrak service di `proto/encryption.proto`.

## Debug & Observability (khusus superadmin)

- `/horizon` — monitor queue/job (role `superadmin` saja)
- `/telescope` — debug request, SQL query, exception (role `superadmin` saja, semua environment)
- Debugbar — otomatis aktif di `APP_ENV=local`

## Yang Sering Salah (dari troubleshooting README)

- Error `relation "users" does not exist` → search_path Postgres nyasar. Flush Redis session + `php artisan cache:clear`.
- Session invalid setelah migrasi kolom ke UUID → clear cookies / incognito.
- Asset tidak update → jangan lupa `npm run build` ulang di container vite.
- Permission denied composer/artisan → `docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html`.
- Telescope tidak bisa diakses → pastikan login sebagai `superadmin`; kalau data kebanyakan, `php artisan telescope:clear`.
- Horizon tidak aktif → cek `docker exec eduzone_queue php artisan horizon:status`, restart container kalau perlu.

## Modul Absensi — Prioritas Saat Ini

**Database `eduzone_absensi` terpisah secara fisik** dari database utama (bukan schema baru di DB yang sama) — karena volume event tinggi (ratusan tap/scan per menit saat jam masuk), supaya resource/backup/locking terisolasi. Koneksi `pgsql_absensi` sudah terdaftar di `config/database.php`.

**Status setelah update (detail lengkap di `ARCHITECTURE.md` §2.5–2.6):**

✅ Selesai:
- **19 migration** (`2025_01_01_000044` s.d. `000062`) dan **17 model Eloquent** di `app/Models/Absensi/` (tambah `RefSyncState`), semua eksplisit `protected $connection = 'pgsql_absensi';`. 3 migration baru setelah 26 Juli 2026: `000060_add_photo_url_to_people_ref`, `000061_create_ref_sync_state_table`, `000062_add_late_cutoff_time_to_schools_table` (kolom baru di tabel `schools` DB utama).
- Halaman kiosk (`resources/views/kiosk/checkin.blade.php` + `resources/js/areas/kiosk.js`), route `GET /kiosk/{deviceCode}` di `routes/kiosk.php` — RFID & QR aktif, tab Manual/Wajah masih placeholder.
- **Sync Pull Endpoint (Go → Laravel):** 3 endpoint di `routes/sync.php` — `/api/internal/sync/schools`, `/people`, `/schedules` (lihat bagian "Sync Controller & VerifySyncToken Middleware" di bawah).
- **Device Management UI (Superadmin):** `DeviceController` full CRUD + regenerate-key (5 tipe device valid: `face_camera`, `rfid_reader`, `qr_scanner`, `hybrid`, `manual_kiosk` — `rfid` BUKAN value valid, lihat § "Device CRUD — Tipe Device yang Benar" di bawah).
- **Health Check Dashboard:** Superadmin (laporan semua sekolah + status gateway/DB) dan tenant (widget ringkas per sekolah). Service class `HealthCheckService` cache 30 detik (lihat § "HealthCheckService").
- **⚠️ INGAT:** `App\Services\Absensi\GatewayTokenIssuer` **BELUM diimplementasikan** — nama kelas cuma direferensikan di config/services.php & dokumen, file aslinya tidak ada di filesystem. Package `firebase/php-jwt` juga belum terpasang di `composer.json`. JANGAN buat halaman check-in guru sebelum keduanya siap. Claims JWT (`user_id`/`school_id`/`role`) — kalau-kalau nanti dibuat — harus sinkron manual dengan struct `middleware.TeacherClaims` di `auth.go` Go, TIDAK ada shared schema. Secret di env var `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) harus identik dengan `JWT_SECRET` (Go).
- ⚠️ **Dua shared-secret TERPI SIH, JANGAN tertukar:** `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) ↔ `JWT_SECRET` (Go) untuk JWT guru; `ABSENSI_SYNC_TOKEN` (Laravel) ↔ `LARAVEL_SYNC_TOKEN` (Go) untuk header `X-Sync-Token` sync pull.

🔲 Belum:
- Job sinkronisasi `attendance_daily`/`attendance_period` → `student_attendance`/`teacher_attendance` di DB utama (tercatat di `sync_log`) — **kosong di kedua sisi** (Laravel & gateway), dikonfirmasi sengaja belum dibuat.
- Dashboard staff untuk rekap absensi (Wali Kelas/Guru Mapel/TU) — placeholder Wali Kelas dashboard sudah ada (route + view) tapi belum berisi data.
- Halaman check-in guru via HP (geofencing GPS + JWT) — endpoint Go ada, tapi class PHP `GatewayTokenIssuer` + `firebase/php-jwt` package **belum** (lihat poin di atas).
- Face recognition — endpoint gateway masih stub dummy, belum ada worker Python/InsightFace.
- 5 tabel dorman (`device_keys`, `qr_tokens`, `attendance_correction_log`, `local_verifiers`, `presence_tickets`) — struktur sudah ada di schema, **jangan bikin controller/endpoint untuk ini kecuali diminta eksplisit**.
- Bersih-bersih tertunda: route `POST /kiosk/{deviceCode}/checkin` di `routes/kiosk.php` dan `AttendanceRecorder.php`/`CheckInController::store()` — sudah digantikan penuh oleh `absensi-gateway`, aman dihapus, dibiarkan menggantung berisiko jadi write path ganda.

**Aturan kerja modul ini:**

1. **`absensi-gateway` (Go, folder/repo terpisah dari Laravel) adalah write path resmi** untuk check-in device (RFID/QR/Face, `POST /api/v1/checkin/device`) dan check-in guru via HP (`POST /api/v1/checkin/teacher`). **Laravel TIDAK menduplikasi logic ini** — Laravel cuma render halaman kiosk (Blade+JS) yang memanggil gateway langsung dari browser lewat proxy NPM `/gateway`. Kalau diminta bantuan soal check-in device/guru, **jangan bikin controller Laravel yang insert ke `attendance_events`** — itu sudah dipegang gateway.
2. `attendance_events` **insert-only** — jangan buat kode yang UPDATE/DELETE baris di tabel ini. Sudah di-enforce di model (`App\Models\Absensi\AttendanceEvent` melempar exception kalau di-`update()`/`delete()`).
3. **`RefSyncState` read-only** — tabel ini ditulis oleh Go (sinkronisasi pull), tidak boleh ada insert/update/delete dari Laravel. Sudah di-enforce di model (event `saving()` melempar `RuntimeException`).
4. Data siswa/guru/sekolah diakses lewat cache lokal `App\Models\Absensi\PeopleRef`/`SchoolRef` (disinkron satu arah dari DB utama), bukan join langsung ke `students`/`teachers`/`schools`. `PeopleRef` punya composite primary key (`person_id`+`person_type`) — pakai trait `HasCompositePrimaryKey`, dan **jangan pakai `::find()`**, selalu `where('person_id', ...)->where('person_type', ...)`.
5. File `absensi_schema.sql` di repo Go itu **read-only/dokumentasi saja** — migration Laravel adalah satu-satunya sumber kebenaran schema. Kalau ada perubahan kolom/tabel, ubah migration dulu, baru sinkronkan file itu.
6. **Risiko keamanan yang sudah didokumentasikan, belum diperbaiki**: JWT pakai shared-secret HS256 antara Laravel & gateway — kalau secret bocor dari sisi gateway, penyerang bisa forge token untuk seluruh sistem. Rencana jangka panjang: pindah ke RS256. Belum dikerjakan, jangan anggap sudah aman.
7. Setup ulang proxy Nginx (NPM) untuk endpoint semacam ini: rewrite prefix-stripping **wajib** ditaruh di gear (⚙) per-location Custom Location, **BUKAN** di tab "Advanced" level-host — kalau salah taruh, hasilnya 404 dari Laravel (bukan 502 Nginx), gampang salah diagnosis sebagai masalah routing Laravel padahal masalah scope rewrite Nginx.

### Sync Controller & VerifySyncToken Middleware

Endpoint sync di `app/Http/Controllers/Api/SyncController.php` adalah **satu-satunya write path data master ke gateway** (gateway melakukan HTTP GET periodik ke endpoint ini untuk menarik sekolah/pegawai/siswa/jadwal). **JANGAN ubah response shape tanpa update dokumen kontrak `docs/laravel-sync-contract.md` di repo `absensi-gateway` juga** — gateway parse field-name dan ASC-sorting secara eksplisit, perubahan sekecil apapun (misal bungkus jadi `{data: [...]}` alih-alih array polos) akan bikin sync gagal di sisi gateway.

Aturan wajib yang harus selalu dijaga:
- **Middleware pelindung:** Semua route sync DILINDUNGI HANYA oleh `sync.token` alias `VerifySyncToken` (di `app/Http/Middleware/`), DAN TIDAK DIBUNGKUS middleware `web`/session/CSRF. Jangan tambah middleware `auth`/`tenant`/`superadmin` ke grup sync — gateway pakai token stateless, bukan cookie/session.
- **Header verifikasi:** `VerifySyncToken` membandingkan `hash_equals()` header `X-Sync-Token` dengan `config('services.absensi_gateway.sync_token')` (dibaca dari env `ABSENSI_SYNC_TOKEN`). Jangan pakai perbandingan string biasa (rentan timing attack) — class ini sudah benar, tinggal pakai.
- **Response JSON ARRAY POLOS (bukan `{data: [...]}`)**. Di SyncController dipakai `->values()->all()` di akhir untuk strip associative key dan pastikan ini array indexed sejati. Jangan ganti ke `response()->json($query->paginate(...))` atau Resource collection Laravel lain yang auto-wrap.
- **Sortir SELALU `orderBy('updated_at', 'ASC')`** (prinsip kursor: gateway pakai `?cursor=timestamp_terakhir_ditarik`). Kalau sortirnya DESC, halaman pertama gateway dapat record terbaru lalu cursor-nya makin lama makin jauh ke masa depan; record baru yang muncul tengah paginasi akan terlewat.
- **Filter per endpoint:**
  - `/schools`: `whereNotNull('latitude')->whereNotNull('longitude')` — sekolah tanpa GPS tidak valid untuk geofencing guru.
  - `/people`: union tiga tabel (`students` + `teachers` + `staff` via `DB::raw`). Setiap baris harus punya `person_id` (UUID sama dengan DB utama), `person_type` (string enum: `student|teacher|staff`), `photo_url` (atau null, lewat helper `resolvePhotoUrl()`).
  - `/schedules`: `whereNotNull('teacher_id')->whereNotNull('class_id')`. Konversi nama hari Indonesia (`$schedule->day`) ke ISO 8601 day-of-week (Senin=1, Selasa=2, …, Minggu=7) via `mapDayNameToIso()`, nilai invalid dilewati (return null di collection, tidak masuk final array).
- **Paginasi default `per_page = 500`** dan di-query sebagai chunk supaya OOM di Laravel untuk sekolah banyak. Gateway akan loop `?page=N` sampai jumlah baris < `per_page`.

### HealthCheckService

`App\Services\Absensi\HealthCheckService` — dipanggil dari `Superadmin\AbsensiHealthController` (laporan lintas sekolah) dan `Tenant\AbsensiHealthController` (widget sekolah saja). **Jangan call methodnya per request tanpa cache** — gateway/DB akan dihantam HTTP SELECT N kali per detik kalau banyak tenant buka dashboard bersamaan.

Aturan:
- Superadmin (`fullReport()`) → cache key `absensi:health:full` dengan TTL 30 detik. Isinya: `gateway.status`, `gateway.latency_ms`, `gateway.database` (cek `body->database == 'ok'` dari `/health` gateway, BUKAN cuma status code 200), `database.status` (cek `select 1` ke koneksi `pgsql_absensi`), `schools[]` (per sekolah: `school_id`, `name`, `sync_fresh` [true jika `schools_ref.synced_at` < 10 menit yang lalu = 2x SYNC_INTERVAL gateway], `devices_online` [jumlah device dengan `last_seen_at` < 5 menit lalu], `devices_total`).
- Tenant (`schoolReport($schoolId)`) → ringkas, auto-scope ke `school_id` user login. Cuma return array `['ready' => bool, 'message' => string]`, TIDAK BOLEH expose `gateway_url`, `gateway_latency_ms`, atau detail sekolah lain. Kalau superadmin mau detail lengkap, harus login ke area superadmin.
- Treshold "stale sync" **10 menit** (2x default SYNC_INTERVAL gateway 5 menit). Treshold "device offline" **5 menit** (heartbeat device tiap menit, jadi 5x lewat berarti mati). Kalau nanti SYNC_INTERVAL gateway diubah, update angka 10 menit di method ini biar tidak false-positive.
- Kalau gateway HTTP timeout/error (tidak bisa koneksi), jangan throw exception langsung ke user — set `gateway.status = 'error'`, `gateway.message = (string) $e->getMessage()`, dan lanjut hitung `schools[]` (koneksi DB absensi mungkin masih jalan meskipun gateway down).

### Device CRUD — Tipe Device yang Benar

`DeviceController` di `Superadmin` namespace dan tabel `devices` punya CHECK constraint `devices_device_type_check` di DB. **Validasi Form Request HARUS sinkron persis dengan enum DB** — kalau tidak, simpan ke DB akan throw QueryException CHECK constraint violation (lebih parah daripada validation error biasa, karena stack trace muncul dan user tidak tahu dia salah ketik value).

5 value yang VALID (copy-paste kalau bikin Form Request, jangan ketik manual):
`face_camera`, `rfid_reader`, `qr_scanner`, `hybrid`, `manual_kiosk`.

**Value yang BUKAN valid & sering salah tebak (jangan sampai lolos validasi):** `rfid`, `qr`, `camera`, `rfid_device`, `face_id`, `gateway` — untuk gate/pintu gerbang umumnya value pakai `rfid_reader` atau `hybrid`.

Aturan lain Device:
- `api_key_hash` disimpan sebagai `hash('sha256', $rawKey, true)` binary 32-byte. Key mentah 32 karakter hex CUMA ditampilkan SEKALI via `session()->flash('device_api_key_raw', ...)` di response create()/regenerateKey(). Jangan simpan raw key di DB atau log.
- `destroy()` method harus **selalu** bungkus `$device->delete()` dengan try-catch `QueryException` — kalau device sudah punya relasi ke `attendance_events`, FK constraint akan nolak DELETE. Pesan error user-friendly: "Device X memiliki data absensi, tidak bisa dihapus. Nonaktifkan (is_active=false) lewat halaman Edit sebagai gantinya." Jangan biarkan stack trace `QueryException` keluar ke user.

## Konteks: Ini Rebuild, Bukan Project Tertinggal (ringkasan)

Baru ~6 controller yang benar-benar ada (Auth + Superadmin dashboard dasar) — **ini memang tahap yang disengaja**. Skema database dirancang menyeluruh di awal; controller/UI dibangun bertahap per modul, dimulai dari Absensi. Jangan asumsikan skema tabel yang ada sekarang final hanya karena migration-nya sudah lengkap — masih mungkin disesuaikan begitu controller/UI tiap modul mulai dikerjakan dan requirement nyata muncul.

**Hal yang masih perlu diklarifikasi dengan product owner** (lihat `PRD.md` §7): model bisnis subscription, prioritas modul MVP, target jumlah tenant, rencana mobile app, kebijakan retensi data (UU PDP), definisi "selesai" per modul, dan status migrasi data dari sistem lama.
