# ARCHITECTURE.md — EduZone

Dokumen ini merangkum arsitektur teknis EduZone secara lebih dalam dari PRD, ditujukan untuk developer/AI assistant yang perlu memahami *bagaimana* sistem bekerja, bukan cuma *apa* fiturnya.

---

## 1. Gambaran Umum Sistem

```
┌─────────────────────────────────────────────────────┐
│                  Docker Environment                 │
│                                                       │
│  ┌──────────┐   ┌──────────┐   ┌──────────────┐     │
│  │  nginx   │   │   app    │   │    vite      │     │
│  │:8083→80  │──▶│  php-fpm │   │ :5174 (dev)  │     │
│  └──────────┘   │  :9000   │   └──────────────┘     │
│                 └────┬─────┘                         │
│  ┌──────────┐        │        ┌──────────────┐       │
│  │ postgres │◀───────┤        │    redis     │       │
│  │  :5432   │        │        │    :6379     │       │
│  └──────────┘        │        └──────────────┘       │
│                      │                                │
│  ┌──────────┐         └──────▶ ┌──────────────┐      │
│  │  reverb  │                  │    queue     │      │
│  │  :8082   │                  │  (Horizon)   │      │
│  └──────────┘                  └──────────────┘      │
│                                                       │
│  ── Infrastructure (shared, di luar project ini) ──  │
│  nginx-proxy-manager (:80/:443) · adminer (:8081)    │
│  uptime-kuma (:3001) · crowdsec                      │
└─────────────────────────────────────────────────────┘
```

Postgres, Redis, dan Reverb adalah service **shared infrastructure** — dipakai bersama oleh project lain (mis. Lab Management), bukan didedikasikan untuk EduZone saja.

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
- Koneksi database kedua ini perlu didaftarkan sebagai `connection` terpisah di `config/database.php` Laravel; model-model modul Absensi perlu eksplisit set `$connection` ke koneksi tersebut.
- `attendance_events` bersifat insert-only — jangan buat endpoint yang meng-UPDATE atau DELETE baris di tabel ini secara langsung. Rencana ke depan bahkan ada `REVOKE UPDATE, DELETE` di level database role setelah alur insert-only teruji.
- Koreksi data absensi (mis. wali kelas mengubah status siswa) di versi awal masih langsung/manual; alur approval via `attendance_correction_log` baru diaktifkan belakangan — jangan bangun UI approval untuk ini di tahap pertama kecuali diminta.
- Geofencing guru pakai kombinasi GPS (radius dari `schools_ref`) + validasi IP dari `school_networks`. Untuk sekolah dengan ISP residensial ber-CGNAT (IP publik tidak stabil), solusinya `local_verifiers` — tapi ini juga belum aktif; tahap awal cukup GPS + IP publik yang stabil.

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
├── docker-compose.yml               ← app, vite, nginx, queue, scheduler — satu file
├── docker\                          ← config yang di-copy ke image saat build
│   ├── nginx\default.conf
│   ├── php\php-dev.ini, php-fpm.conf, php-prod.ini
│   └── postgres\...
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
├── reverb\      → container `reverb`, WebSocket Laravel Reverb di :8082
└── (rencana ke depan: nginx-proxy-manager\, crowdsec\, uptime-kuma\)
```

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
Extensions: `pdo_pgsql`, `pgsql`, `gd`, `zip`, `mbstring`, `bcmath`, `opcache`, `intl`, `pcntl`, `redis`, `grpc`, `protobuf`. User non-root (`laravel`, uid 1000) dipakai di kedua stage.

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
- Schema `eduzone_absensi` sudah siap (lihat bagian 2), tapi migration Laravel-nya, konfigurasi koneksi database kedua di `config/database.php`, dan controller/route untuk modul Absensi belum digarap di repo ini — jadi urutan kerja modul Absensi kemungkinan besar: setup koneksi DB kedua → migration dari schema ini → model dengan `$connection` eksplisit → job sinkronisasi → controller/route/view.
