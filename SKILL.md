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
- `routes/kiosk.php` → khusus layar kiosk absensi (lihat bagian Absensi).

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

**Status per 26 Juli 2026 (detail lengkap di `ARCHITECTURE.md` §2.5–2.6):**

✅ Selesai:
- 16 migration (`2025_01_01_000044` s.d. `000059`) dan **16 model Eloquent** di `app/Models/Absensi/`, semua eksplisit `protected $connection = 'pgsql_absensi';`.
- Halaman kiosk (`resources/views/kiosk/checkin.blade.php` + `resources/js/areas/kiosk.js`), route `GET /kiosk/{deviceCode}` di `routes/kiosk.php` — RFID & QR aktif, tab Manual/Wajah masih placeholder.
- Integrasi JWT Laravel↔Go: `App\Services\Absensi\GatewayTokenIssuer` (HS256, `firebase/php-jwt`) — claims (`user_id`/`school_id`/`role`) harus sinkron manual dengan `middleware.TeacherClaims` di `auth.go` Go, TIDAK ada shared schema. Secret di env var `ABSENSI_GATEWAY_JWT_SECRET` (Laravel) harus identik dengan `JWT_SECRET` (Go).

🔲 Belum:
- Job sinkronisasi `attendance_daily`/`attendance_period` → `student_attendance`/`teacher_attendance` di DB utama (tercatat di `sync_log`) — **kosong di kedua sisi** (Laravel & gateway), dikonfirmasi sengaja belum dibuat.
- Dashboard staff untuk rekap absensi (Wali Kelas/Guru Mapel/TU).
- Halaman check-in guru via HP (geofencing GPS + JWT) — endpoint Go & JWT issuer sudah ada, belum ada halaman/PWA yang memanggilnya.
- Face recognition — endpoint gateway masih stub dummy, belum ada worker Python/InsightFace.
- 5 tabel dorman (`device_keys`, `qr_tokens`, `attendance_correction_log`, `local_verifiers`, `presence_tickets`) — struktur sudah ada di schema, **jangan bikin controller/endpoint untuk ini kecuali diminta eksplisit**.
- Bersih-bersih tertunda: route `POST /kiosk/{deviceCode}/checkin` di `routes/kiosk.php` dan `AttendanceRecorder.php`/`CheckInController::store()` — sudah digantikan penuh oleh `absensi-gateway`, aman dihapus, dibiarkan menggantung berisiko jadi write path ganda.

**Aturan kerja modul ini:**

1. **`absensi-gateway` (Go, folder/repo terpisah dari Laravel) adalah write path resmi** untuk check-in device (RFID/QR/Face, `POST /api/v1/checkin/device`) dan check-in guru via HP (`POST /api/v1/checkin/teacher`). **Laravel TIDAK menduplikasi logic ini** — Laravel cuma render halaman kiosk (Blade+JS) yang memanggil gateway langsung dari browser lewat proxy NPM `/gateway`. Kalau diminta bantuan soal check-in device/guru, **jangan bikin controller Laravel yang insert ke `attendance_events`** — itu sudah dipegang gateway.
2. `attendance_events` **insert-only** — jangan buat kode yang UPDATE/DELETE baris di tabel ini. Sudah di-enforce di model (`App\Models\Absensi\AttendanceEvent` melempar exception kalau di-`update()`/`delete()`).
3. Data siswa/guru/sekolah diakses lewat cache lokal `App\Models\Absensi\PeopleRef`/`SchoolRef` (disinkron satu arah dari DB utama), bukan join langsung ke `students`/`teachers`/`schools`. `PeopleRef` punya composite primary key (`person_id`+`person_type`) — pakai trait `HasCompositePrimaryKey`, dan **jangan pakai `::find()`**, selalu `where('person_id', ...)->where('person_type', ...)`.
4. File `absensi_schema.sql` di repo Go itu **read-only/dokumentasi saja** — migration Laravel adalah satu-satunya sumber kebenaran schema. Kalau ada perubahan kolom/tabel, ubah migration dulu, baru sinkronkan file itu.
5. **Risiko keamanan yang sudah didokumentasikan, belum diperbaiki**: JWT pakai shared-secret HS256 antara Laravel & gateway — kalau secret bocor dari sisi gateway, penyerang bisa forge token untuk seluruh sistem. Rencana jangka panjang: pindah ke RS256. Belum dikerjakan, jangan anggap sudah aman.
6. Setup ulang proxy Nginx (NPM) untuk endpoint semacam ini: rewrite prefix-stripping **wajib** ditaruh di gear (⚙) per-location Custom Location, **BUKAN** di tab "Advanced" level-host — kalau salah taruh, hasilnya 404 dari Laravel (bukan 502 Nginx), gampang salah diagnosis sebagai masalah routing Laravel padahal masalah scope rewrite Nginx.

## Konteks: Ini Rebuild, Bukan Project Tertinggal (ringkasan)

Baru ~6 controller yang benar-benar ada (Auth + Superadmin dashboard dasar) — **ini memang tahap yang disengaja**. Skema database dirancang menyeluruh di awal; controller/UI dibangun bertahap per modul, dimulai dari Absensi. Jangan asumsikan skema tabel yang ada sekarang final hanya karena migration-nya sudah lengkap — masih mungkin disesuaikan begitu controller/UI tiap modul mulai dikerjakan dan requirement nyata muncul.

**Hal yang masih perlu diklarifikasi dengan product owner** (lihat `PRD.md` §7): model bisnis subscription, prioritas modul MVP, target jumlah tenant, rencana mobile app, kebijakan retensi data (UU PDP), definisi "selesai" per modul, dan status migrasi data dari sistem lama.
