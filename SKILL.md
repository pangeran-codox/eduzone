---
name: eduzone-dev
description: Panduan kerja untuk membantu development platform EduZone — SaaS manajemen sekolah multi-tenant berbasis Laravel 11 + PostgreSQL + Docker. Gunakan skill ini setiap kali mengerjakan kode di project EduZone: membuat/mengubah model, migration, controller, route, view Blade, konfigurasi Docker, atau apa pun yang menyentuh folder app/, database/, routes/, resources/views/, atau docker/ di repo ini. Wajib dipakai sebelum membuat model atau tabel baru karena ada aturan wajib (UUID, BelongsToSchool trait, multi-tenancy scope) yang gampang terlewat.
---

# EduZone — Panduan Development

EduZone adalah platform SaaS multi-tenant untuk manajemen sekolah (akademik, absensi, keuangan, kesiswaan, lab, ujian). Satu database dipakai bersama oleh banyak sekolah (tenant), diisolasi lewat kolom `school_id` + global scope.

Stack: Laravel 11 (PHP 8.3), PostgreSQL 16, Redis, Nginx, Vite, Tailwind, spatie/laravel-multitenancy v4, Laravel Horizon, Laravel Reverb (WebSocket), gRPC ke service encryption (Rust) dan rencana microservice Go Fiber untuk endpoint high-concurrency.

Baca `README.md` di root project untuk dokumentasi lengkap. Skill ini merangkum aturan yang paling gampang dilanggar.

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

## Routing

Pola nama route: `{area}.{resource}.{action}`, contoh `superadmin.schools.index`, `guru.grades.store`.

- `routes/tenant.php` → fitur milik role sekolah, wajib dibungkus middleware `['auth', 'active', 'tenant']` + `role:xxx` per grup.
- `routes/superadmin.php` → fitur superadmin, middleware `superadmin`.
- `routes/web.php` → landing page, login/logout tenant, router `/dashboard`.

Jangan taruh route tenant baru di `web.php` — akan lolos dari prefix & middleware otomatis yang di-setup di `bootstrap/app.php`.

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

## Docker & Menjalankan Perintah

Semua perintah artisan/composer/npm dijalankan **di dalam container**, bukan di host:
```bash
docker exec eduzone_app php artisan <command>
docker exec -u root eduzone_app composer <command>
docker exec eduzone_vite npm run build       # production
docker exec -it eduzone_vite npm run dev     # dev, hot reload
```

Konfigurasi docker (Dockerfile, docker-compose.yml) hidup **terpisah** dari source code, di `C:\opt\docker\eduzone\`, sedangkan source Laravel ada di `C:\laragon\www\eduzone\`. Kalau edit file di dalam container tanpa commit ke source + rebuild image, perubahan itu hilang — pola ini konsisten dengan project Lab Management yang juga dipisah app-code vs infra-config.

Container: `eduzone_app` (PHP-FPM), `eduzone_nginx` (:8083), `eduzone_vite` (:5174, dev only), `eduzone_queue` (Horizon), `eduzone_scheduler`, `reverb` (WebSocket, :8082 — sudah pindah ke compose EduZone sendiri, BUKAN lagi infrastruktur shared, lihat ARCHITECTURE.md §1). Cuma Postgres dan Redis yang masih ada di infrastruktur shared, dipakai bareng project lain.

## Frontend — Vite per Area, Alpine.js untuk Interaktivitas

Entry point JS/CSS di-organize **per area**, bukan satu bundle global atau per-modul: `resources/js/areas/superadmin.js`, `tenant.js`, `kiosk.js` (khusus device absensi). CSS tetap satu entry global (`resources/css/app.css`) untuk semua area — jangan dipecah, Tailwind sudah efisien lewat content-scanning.

- Halaman baru di area yang sudah ada → pakai entry area itu, **jangan bikin entry baru**.
- Interaktivitas ringan di Blade (dropdown, modal, toggle) → pakai Alpine.js (`x-data`, `x-show`, dst), sudah ter-load otomatis di area superadmin & tenant.
- Entry `kiosk.js` (layar RFID/QR/Face) sengaja **tidak** load Alpine — device fisik nyala berjam-jam, dijaga seringan mungkin.
- Detail lengkap & alasan tiap keputusan ada di `FRONTEND.md`.

## gRPC & Encryption Service — Sementara Dinonaktifkan di Dockerfile

Extension PHP `grpc`/`protobuf` **sengaja dikomentar/dilepas** dari `Dockerfile` (cuma `redis`
yang aktif) — compile `grpc` dari source di Alpine makan waktu ~1 jam dan belum ada controller
yang benar-benar makai `EncryptionGrpcService`/`grpc_worker.php`. `composer install` juga
dikasih `--ignore-platform-req=ext-grpc` biar nggak nolak gara-gara package `grpc/grpc` di
`composer.json` minta ekstensi itu aktif.

**Jangan coba pakai `EncryptionGrpcService` atau apapun yang butuh `ext-grpc` sebelum ini
diaktifkan lagi** — bakal fatal error class/extension not found. Kalau mulai kerjain integrasi
encryption service, aktifkan lagi sesuai instruksi komentar di `Dockerfile` (tambah `grpc
protobuf` balik ke `pecl install`, tambah `linux-headers` ke `.build-deps`, hapus
`--ignore-platform-req=ext-grpc`), lalu siap-siap nunggu compile ~1 jam sekali itu doang (abis
itu ke-cache normal).

## Debug & Observability (khusus superadmin)

- `/horizon` — monitor queue/job (role `superadmin` saja)
- `/telescope` — debug request, SQL query, exception (role `superadmin` saja, semua environment)
- Debugbar — otomatis aktif di `APP_ENV=local`

## Yang Sering Salah (dari troubleshooting README)

- Error `relation "users" does not exist` → search_path Postgres nyasar. Flush Redis session + `php artisan cache:clear`.
- Session invalid setelah migrasi kolom ke UUID → clear cookies / incognito.
- Asset tidak update → jangan lupa `npm run build` ulang di container vite.
- Permission denied composer/artisan → `docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html`.

## Konteks: Ini Rebuild, Bukan Project Tertinggal

EduZone adalah **recoding ulang** dari sistem manajemen sekolah sebelumnya (single-tenant) menjadi platform SaaS multi-tenant yang jauh lebih besar. Skema database (39+ tabel/model) sengaja dirancang menyeluruh di awal, sebelum controller/UI dibangun bertahap per modul. Per eksplorasi terakhir, baru ada 6 controller (Auth + Superadmin dashboard dasar) — ini memang tahap yang disengaja, bukan sinyal ada yang salah atau tertinggal.

Implikasi saat membantu development:
- **Prioritas saat ini: modul Absensi.** Ini sengaja dipilih sebagai fitur pertama supaya EduZone bisa mulai dipakai sekolah walau baru sebatas absen, sambil memvalidasi fondasi (auth, multi-tenancy, docker) lewat fitur nyata. Kalau diminta bantuan "fitur apa selanjutnya" atau ada ambiguitas prioritas, condongkan ke Absensi dulu sebelum modul lain (Akademik, Penilaian, Kesiswaan, dll).
- **Database Absensi (`eduzone_absensi`) terpisah dari database utama** — karena Absensi multi-metode (RFID/QR/Face/manual) diperkirakan high-concurrency, dipisah fisik sebagai database baru (bukan schema baru di DB yang sama). Detail lengkap skema & alur data ada di `ARCHITECTURE.md` §2.
  - **Status per 26 Juli 2026 (lihat progress log lengkap di `ARCHITECTURE.md` §2.5):** database, koneksi `pgsql_absensi` di `config/database.php`, 16 migration (`2025_01_01_000044` s.d. `000059`), dan 16 model Eloquent di `app/Models/Absensi/` **sudah selesai**. Yang belum: job sinkronisasi ke DB utama, controller/route/view. Baca §2.5 dulu sebelum lanjut kerja di modul ini, biar tahu persis sudah sampai mana.
  - `attendance_events` itu **insert-only** — jangan buat kode yang UPDATE/DELETE baris di tabel ini. Sudah di-enforce di model (`App\Models\Absensi\AttendanceEvent` melempar exception kalau di-`update()`/`delete()`).
  - Data siswa/guru/sekolah diakses lewat cache lokal `App\Models\Absensi\PeopleRef`/`SchoolRef` (disinkron satu arah dari DB utama), bukan lewat join langsung ke `students`/`teachers`/`schools`. `PeopleRef` punya composite primary key (`person_id`+`person_type`) — pakai trait `HasCompositePrimaryKey`, dan **jangan pakai `::find()`** untuk model ini, selalu `where('person_id', ...)->where('person_type', ...)`.
  - Sinkronisasi balik ke DB utama (`student_attendance`/`teacher_attendance`) terjadi via job/queue terjadwal yang baca `attendance_daily`, dicatat progresnya di `sync_log` — bukan ditulis langsung dari request user. **Job ini belum dibuat.**
  - Tabel `device_keys`, `qr_tokens`, `attendance_correction_log`, `local_verifiers`, `presence_tickets` sudah ada di schema (dan modelnya sudah dibuat) tapi **belum diaktifkan logikanya**. Jangan buat controller/endpoint untuk fitur ini kecuali diminta eksplisit — fokus rilis pertama cuma RFID/QR/Face + geofencing GPS dasar.
  - Semua model modul Absensi eksplisit set `protected $connection = 'pgsql_absensi';` — sudah konsisten di 16 model yang ada.
  - **Pembagian kerja Go vs Laravel SUDAH JELAS (per 26 Juli 2026, lihat ARCHITECTURE.md §2.6):** `absensi-gateway` (Go, folder lokal terpisah dari repo Laravel ini) adalah **write path resmi untuk check-in device** (RFID/QR/Face lewat `POST /api/v1/checkin/device`) dan **check-in guru via HP** (`POST /api/v1/checkin/teacher`, pakai JWT). Laravel **TIDAK** menduplikasi logic ini — Laravel cuma render halaman (Blade+JS kiosk) yang manggil gateway langsung dari browser (lewat proxy NPM `/gateway`, bukan hostname Docker internal). Kalau diminta bantuan soal check-in device/guru, JANGAN bikin controller Laravel yang insert ke `attendance_events` — itu udah dipegang gateway. File referensi schema (`absensi_schema.sql`) di repo Go itu **read-only/dokumentasi saja** — migration Laravel adalah sumber kebenaran schema.
  - **Halaman kiosk sudah live & teruji end-to-end:** `resources/views/kiosk/checkin.blade.php` + `resources/js/areas/kiosk.js`, route `GET /kiosk/{deviceCode}` di `routes/kiosk.php`. RFID & QR aktif, tab Manual/Wajah masih placeholder (gateway belum dukung method `manual`; Face masih stub tanpa worker Python).
  - **Integrasi JWT Laravel↔Go sudah terpasang:** `App\Services\Absensi\GatewayTokenIssuer` (pakai `firebase/php-jwt`) menerbitkan token HS256 buat dipakai check-in guru via HP nanti — claims-nya (`user_id`/`school_id`/`role`) harus sinkron manual dengan `middleware.TeacherClaims` di `auth.go` Go, TIDAK ada shared schema antar keduanya. **Catatan keamanan dari README gateway sendiri:** shared-secret HS256 ini beresiko (secret bocor di gateway = bisa forge token buat seluruh sistem) — rencana jangka panjang pindah ke RS256, belum dikerjakan.
  - Kalau setup ulang proxy Nginx buat endpoint baru semacam ini, ingat jebakan NPM: rewrite prefix-stripping WAJIB ditaruh di gear (⚙) per-location Custom Location, BUKAN di tab "Advanced" level-host — kalau salah taruh, request 404 dari Laravel (bukan 502 Nginx), gampang salah diagnosis. Detail lengkap di ARCHITECTURE.md §2.6.
- Modul lain di luar Absensi **baru sebatas migration/model**, belum ada controller/route/view. Saat diminta "lanjutkan fitur X" untuk modul selain Absensi, cek dulu apakah controller & route-nya sudah ada — kalau belum, berarti memang belum masuk giliran kerja.
- Jangan mengasumsikan skema tabel yang ada sekarang final hanya karena migration-nya sudah lengkap — skema didesain di awal untuk seluruh sistem, tapi masih mungkin disesuaikan begitu controller/UI tiap modul mulai dikerjakan dan requirement nyata muncul.