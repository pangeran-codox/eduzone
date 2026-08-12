# EduZone — Platform Manajemen Sekolah SaaS

> Platform SaaS multi-tenant untuk manajemen sekolah modern. Satu platform untuk ratusan sekolah — akademik, keuangan, absensi, dan lebih banyak lagi.

---

## Daftar Isi

- [Gambaran Umum](#gambaran-umum)
- [Arsitektur Sistem](#arsitektur-sistem)
- [Stack Teknologi](#stack-teknologi)
- [Struktur Direktori](#struktur-direktori)
- [Setup & Instalasi](#setup--instalasi)
- [Konfigurasi Docker](#konfigurasi-docker)
- [Database & Migrasi](#database--migrasi)
- [Multi-Tenancy](#multi-tenancy)
- [Autentikasi & Role](#autentikasi--role)
- [Aturan Coding](#aturan-coding)
- [Konvensi Penamaan](#konvensi-penamaan)
- [Flow Pengembangan](#flow-pengembangan)
- [Struktur Route](#struktur-route)
- [Struktur View](#struktur-view)
- [Panduan Model & Eloquent](#panduan-model--eloquent)
- [Seeder & Data Dummy](#seeder--data-dummy)
- [Akun Default](#akun-default)

---

## Gambaran Umum

EduZone adalah aplikasi web SaaS (Software as a Service) yang memungkinkan sekolah-sekolah di Indonesia mengelola seluruh operasional akademik dan administrasi dalam satu sistem. Setiap sekolah mendapatkan akses terisolasi dengan data yang sepenuhnya terpisah meskipun menggunakan satu database yang sama (shared database, single schema multi-tenancy).

### Fitur Utama

| Modul | Deskripsi |
|---|---|
| Akademik | Jadwal, nilai, raport, jurnal mengajar |
| Absensi | Siswa dan guru, real-time, notifikasi orang tua |
| Keuangan | Pemasukan, pengeluaran, dana BOS, laporan |
| Kesiswaan | Data siswa, prestasi, konseling BK |
| Lab | Booking laboratorium, inventaris |
| Ujian | Soal, bank soal, pengawas |
| Superadmin | Manajemen sekolah, subscription, audit log |

---

## Arsitektur Sistem

```
┌─────────────────────────────────────────────────────┐
│                  Docker Environment                 │
│                                                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────────┐     │
│  │  nginx   │   │   app    │   │    vite      │     │
│  │:8083→80  │──▶│  php-fpm │   │ :5174 (dev) │      │
│  └──────────┘   │  :9000   │   └──────────────┘     │
│                 └────┬─────┘                        │
│  ┌──────────┐        │        ┌──────────────┐      │
│  │ postgres │◀──────┤         │    redis     │      │
│  │  :5432   │        │        │    :6379     │      │
│  └──────────┘        │        └──────────────┘      │
│                      │                              │
│  ┌──────────┐         └──────▶ ┌──────────────┐     │
│  │  reverb  │                  │    queue     │     │
│  │  :8082   │                  │   (worker)   │     │
│  └──────────┘                  └──────────────┘     │
│                                                     │
│  ── Infrastructure (shared) ──────────────────────  │
│  nginx-proxy-manager (:80/:443)                     │
│  adminer (:8081)                                    │
│  uptime-kuma (:3001)                                │
│  crowdsec                                           │
└─────────────────────────────────────────────────────┘
```

### Multi-Tenancy Flow

```
Request masuk
    │
    ▼
AuthUserTenantFinder
    │
    ├─ user.role == 'superadmin' ──▶ tenant = null (akses semua)
    │
    └─ user.school_id ada ──────────▶ School::find(school_id) = current tenant
                                              │
                                              ▼
                                      SchoolScope aktif
                                      (semua query otomatis
                                       WHERE school_id = ?)
```

---

## Stack Teknologi

| Layer | Teknologi | Versi |
|---|---|---|
| Framework | Laravel | 11.x |
| PHP | PHP | 8.2+ (Dockerfile build dengan 8.3) |
| Database | PostgreSQL | 16 |
| Cache / Session / Queue | Redis | latest |
| Web Server | Nginx | 1.27-alpine |
| Frontend Build | Vite | 6.x |
| CSS Framework | Tailwind CSS | 3.x |
| Font | Plus Jakarta Sans | — |
| Multi-Tenancy | spatie/laravel-multitenancy | 4.x |
| Queue Dashboard | Laravel Horizon | 5.x |
| Debug & Profiling | Laravel Telescope | 5.x |
| Debug Bar | barryvdh/laravel-debugbar | 4.x |
| WebSocket | Laravel Reverb | — |
| Container | Docker + Docker Compose | — |

---

## Struktur Direktori

```
eduzone/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── SyncController.php   # 3 endpoint internal sync pull (schools/people/schedules) untuk absensi-gateway Go
│   │   │   ├── Auth/                    # Login, Dashboard router tenant
│   │   │   ├── Kiosk/
│   │   │   │   └── CheckInController.php # Render halaman kiosk device absensi (RFID/QR/Face)
│   │   │   └── Superadmin/             # Semua controller superadmin
│   │   │       ├── Auth/               # Login/logout superadmin
│   │   │       ├── DashboardController.php
│   │   │       ├── SchoolController.php        # CRUD sekolah
│   │   │       ├── SubscriptionController.php  # CRUD langganan
│   │   │       ├── UserController.php          # List user lintas sekolah
│   │   │       ├── DeviceController.php        # CRUD device absensi + regenerate-key
│   │   │       ├── AbsensiHealthController.php # Dashboard health gateway/DB/sync per sekolah
│   │   │       └── ActivityLogController.php   # Log aktivitas superadmin
│   │   └── Middleware/
│   │       ├── InitializeTenancy.php   # Set search_path PostgreSQL + tenant
│   │       ├── RoleMiddleware.php      # Cek role user
│   │       ├── EnsureUserIsActive.php  # Cek is_active
│   │       ├── SuperadminOnly.php      # Guard superadmin area
│   │       └── VerifySyncToken.php     # Verifikasi header X-Sync-Token dari absensi-gateway
│   ├── Models/                         # 50+ Eloquent models (DB utama + 17 model modul Absensi di app/Models/Absensi/)
│   ├── Multitenancy/
│   │   ├── Concerns/
│   │   │   └── BelongsToSchool.php     # Trait untuk semua model tenant
│   │   ├── Scopes/
│   │   │   └── SchoolScope.php         # Global scope filter school_id
│   │   └── TenantFinder/
│   │       └── AuthUserTenantFinder.php
│   ├── Providers/
│   │   └── AppServiceProvider.php      # Set search_path PostgreSQL
│   └── Services/
│       ├── EncryptionGrpcService.php   # gRPC client ke Rust encryption service (ext-grpc ditunda di Dockerfile)
│       └── Absensi/
│           └── HealthCheckService.php  # Cek health gateway/DB/sync/freshness data per sekolah (cached 30s)
│
├── database/
│   ├── migrations/                     # 68 migration files (000001 cache/jobs → 000001-000043 DB utama → 000044-000062 modul Absensi → telescope/sessions)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       ├── SuperadminSeeder.php
│       └── SchoolSeeder.php
│
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.js
│   │   └── areas/
│   │       ├── superadmin.js   # Entry semua halaman /superadmin/*
│   │       ├── tenant.js       # Entry semua halaman tenant role sekolah
│   │       └── kiosk.js        # Entry layar kiosk device absensi (minimal, tanpa Alpine)
│   └── views/
│       ├── welcome.blade.php           # Landing page
│       ├── auth/
│       │   └── login.blade.php         # Login tenant
│       ├── kiosk/
│       │   └── checkin.blade.php       # Layar kiosk RFID/QR/Face device
│       ├── superadmin/
│       │   ├── auth/login.blade.php    # Login superadmin (dark theme)
│       │   ├── layouts/app.blade.php   # Layout superadmin
│       │   ├── dashboard/index.blade.php
│       │   ├── absensi/
│       │   │   ├── health.blade.php    # Health check dashboard Absensi
│       │   │   └── devices/            # CRUD device absensi (index/create/edit)
│       │   ├── schools/                # CRUD sekolah
│       │   ├── subscriptions/          # CRUD langganan
│       │   ├── logs/index.blade.php    # Activity logs
│       │   └── users/index.blade.php
│       └── tenant/                     # Dashboard per role
│           ├── layouts/app.blade.php   # Shared layout untuk semua tenant
│           ├── absensi/dashboard.blade.php # Dashboard absensi Wali Kelas
│           ├── kepsek/dashboard/index.blade.php
│           └── guru/dashboard/index.blade.php
│
├── routes/
│   ├── web.php                         # Public + tenant auth + dashboard router
│   ├── tenant.php                      # Dashboard routes per role sekolah
│   ├── superadmin.php                  # Semua route superadmin (schools, subscriptions, devices, health, logs)
│   ├── kiosk.php                       # Route halaman kiosk device absensi (tanpa auth/tenant middleware)
│   ├── sync.php                        # 3 endpoint internal sync pull (tanpa session/CSRF, dilindungi X-Sync-Token)
│   └── console.php
│
├── docker/
│   ├── nginx/default.conf, default.swarm.conf
│   ├── php/
│   │   ├── php-fpm.conf
│   │   ├── php-dev.ini
│   │   └── php-prod.ini
│   ├── postgres/
│   │   └── init-multiple-db.sh
│   └── reverb/Dockerfile               # Build image Reverb (WebSocket) service
│
└── bootstrap/app.php                   # Route loader + middleware alias (tenant, role, active, superadmin, sync.token)
```

---

## Setup & Instalasi

### 0. Setup Infrastructure Shared (sekali di awal, sebelum project apapun)

EduZone **tidak** punya Postgres/Redis/Reverb sendiri — semuanya numpang ke stack
infrastructure shared yang dipakai bareng project lain (Lab Management, dll), hidup terpisah
di `C:\opt\docker\infrastructure\`. Kalau infra ini belum pernah di-setup di komputer kamu,
lakukan langkah-langkah berikut **sekali saja** (bukan tiap kali buka project EduZone).

> **Soal "overlay network":** overlay network itu fitur khusus Docker **Swarm**, nggak jalan di
> `docker compose` biasa. Karena setup kamu sekarang masih lokal (bukan Swarm — itu baru
> dipakai nanti pas deploy ke Proxmox), yang dipakai di sini adalah network **bridge** biasa.
> Folder infra kamu sebenarnya sudah punya dua versi tiap service: `docker-compose.yml`
> (bridge, buat lokal — dipakai di tutorial ini) dan `docker-compose.swarm.yml` (overlay,
> disiapkan buat nanti). Jangan pakai file `.swarm.yml` sebelum benar-benar jalanin
> `docker swarm init` di server tujuan.

**a. Extract infra ke lokasi permanen:**

```bash
# Sesuaikan lokasi tujuan kalau mau beda
xcopy /E /I infra C:\opt\docker\infrastructure
cd C:\opt\docker\infrastructure
```

**b. Buat network `network` dulu — WAJIB paling pertama**, karena semua service lain
(postgres, redis, adminer, reverb, dan nanti EduZone sendiri) referensi network ini sebagai
`external: true`. Kalau network ini belum ada, semua compose lain bakal gagal start dengan
error `network network declared as external, but could not be found`:

```bash
docker network create --driver bridge --subnet 172.20.0.0/16 network
```

> **Kenapa bukan `docker compose up -d` di folder `network/`?** File `network/docker-compose.yml`
> isinya cuma definisi `networks:` tanpa `services:` — `docker compose up` butuh minimal satu
> service buat dijalankan, jadi kalau dipaksa akan gagal dengan error `no service selected`.
> Untuk network-only compose file kayak gini, langsung pakai `docker network create` dengan
> spesifikasi yang sama (nama, driver, subnet) persis seperti yang tertulis di file itu.

Verifikasi network sudah dibuat:
```bash
docker network ls | findstr network
```
Harus muncul network bernama `network` dengan driver `bridge`.

**c. Copy `.env` di tiap folder service** (kalau belum ada — beberapa folder di zip infra ini
sudah menyertakan `.env` terisi, tapi kalau kamu clone ulang dari `.env.example`, isi dulu):

```bash
copy postgres\.env.example postgres\.env
copy redis\.env.example redis\.env
copy adminer\.env.example adminer\.env
```

**d. Nyalakan Postgres, Redis, Adminer** (urutan antar ketiganya nggak masalah, yang penting
network sudah ada dari langkah b):

```bash
cd postgres
docker compose up -d
cd ..

cd redis
docker compose up -d
cd ..

cd adminer
docker compose up -d
cd ..
```

> **Reverb belum dinyalakan di sini, sengaja.** Reverb butuh volume `eduzone_vendor` (isi
> `vendor/` Laravel EduZone) supaya `php artisan reverb:start` bisa jalan — lihat
> `reverb/.env`, ada `VENDOR_VOLUME=eduzone_vendor` yang dirujuk sebagai `external: true` di
> `reverb/docker-compose.yml`. Volume itu **baru ada setelah EduZone sendiri di-build**
> (langkah §1 di bawah), jadi Reverb harus dinyalakan **paling terakhir**, setelah setup
> EduZone selesai — bukan bareng Postgres/Redis/Adminer di sini. Kalau dipaksa duluan, akan
> muncul error `service "reverb" refers to undefined volume eduzone_vendor: invalid compose
> project`.

**e. Verifikasi Postgres, Redis, Adminer jalan:**
```bash
docker ps --filter "name=postgres" --filter "name=redis" --filter "name=adminer"
```
Keempatnya harus berstatus `Up` (Postgres & Redis malah ada `healthcheck`, jadi statusnya bisa
`Up (healthy)`).

**Kredensial default yang perlu kamu ingat** (dari `.env` infra, dipakai lagi nanti di `.env`
project EduZone):

| Variable | Nilai default di infra |
|---|---|
| `POSTGRES_USER` | `laravel` |
| `POSTGRES_PASSWORD` | `secret123` *(ganti kalau kamu sudah ubah di `.env` infra)* |
| `REDIS_PASSWORD` | *(kosong/tanpa auth secara default)* |

> Database `eduzone` **sudah otomatis dibuat** oleh `postgres/init/01-create-databases.sh`
> (daftarnya: `lab_management`, `finance`, `eduzone`) — tapi script init Postgres **cuma jalan
> sekali, saat volume `postgres_data` masih kosong/baru pertama kali dibuat**. Kalau kamu sudah
> pernah jalanin Postgres ini sebelum `eduzone` ditambahkan ke daftar, database-nya nggak akan
> otomatis muncul — buat manual:
> ```bash
> docker exec -it postgres psql -U laravel -c "CREATE DATABASE eduzone;"
> ```
> Catatan buat nanti: kalau modul Absensi mulai digarap dan butuh database
> `eduzone_absensi` (lihat `ARCHITECTURE.md` §2), nama itu **belum** ada di daftar
> `01-create-databases.sh` — perlu ditambahkan manual dengan cara yang sama.

**Adminer** (GUI buat lihat isi database) bisa diakses di `http://localhost:8081` (port dari
`ADMINER_PORT`), server default sudah diarahkan ke `postgres`.

---

### Reset Total (hapus semua container + data, mulai dari nol)

Kalau butuh reset bersih — baik infra maupun EduZone — urutannya kebalikan dari setup: matikan
EduZone dulu, baru infra, baru network.

```bash
# 1. EduZone: matikan + hapus volume (vendor, node_modules)
cd C:\laragon\www\eduzone
docker compose down -v

# 2. Infra: matikan + hapus volume data (postgres_data, redis_data)
cd C:\opt\docker\infrastructure

cd postgres
docker compose down -v
cd ..

cd redis
docker compose down -v
cd ..

cd adminer
docker compose down -v
cd ..

cd reverb
docker compose down -v
cd ..

# 3. Network: TIDAK dibuat via compose (lihat catatan di atas), jadi dihapus
#    juga bukan via `docker compose down` — pakai docker network rm langsung
docker network rm network
```

Verifikasi semua benar-benar bersih:
```bash
docker ps -a --filter "name=eduzone"
docker ps -a --filter "name=postgres"
docker ps -a --filter "name=redis"
docker ps -a --filter "name=adminer"
docker ps -a --filter "name=reverb"
docker network ls | findstr network
docker volume ls
```
Semua hasil di atas idealnya kosong. Setelah bersih, ulangi dari langkah **a** di atas
(`docker network create ...`) untuk build dari awal lagi.

---

### 1. Prasyarat Project EduZone

- Docker Desktop
- Git
- Infrastructure shared sudah jalan (langkah 0 di atas) — cek lagi kalau perlu:
  ```bash
  docker network ls | findstr network
  docker ps --filter "name=postgres"
  docker ps --filter "name=redis"
  ```

### Langkah Instalasi

```bash
# 1. Clone repository
git clone <repo-url> eduzone
cd eduzone

# 2. Copy environment file
cp .env.example .env
```

**3. Sesuaikan `.env` — bagian ini paling sering salah, baca baik-baik:**

Postgres dan Redis di project ini **bukan container khusus EduZone** — itu instance shared
yang dipakai bareng banyak project (lihat `docker/postgres/init-multiple-db.sh`: satu instance
Postgres, banyak database dibuatkan otomatis lewat `POSTGRES_MULTIPLE_DATABASES`). Konsekuensinya:

- **`DB_USERNAME` dan `DB_PASSWORD` di `.env` EduZone WAJIB SAMA PERSIS** dengan
  `POSTGRES_USER` / `POSTGRES_PASSWORD` yang didefinisikan di `.env` folder infra Postgres
  (`C:\opt\docker\infrastructure\postgres\.env` atau sejenisnya). Ini **satu user Postgres
  untuk semua database** — bukan user terpisah per project. Salah isi password di sini bukan
  error "wrong database", tapi gagal autentikasi total (`password authentication failed for
  user`).
- **`DB_DATABASE`** harus salah satu nama yang sudah didaftarkan di `POSTGRES_MULTIPLE_DATABASES`
  punya infra (mis. `eduzone`). Kalau nama database ini belum ada di daftar itu, tambahkan dulu
  di `.env` infra Postgres lalu `docker compose down && docker compose up -d` ulang container
  Postgres-nya (script `init-multiple-db.sh` cuma jalan sekali saat volume Postgres kosong/baru).
- **`DB_HOST`** diisi nama *service* Postgres di docker-compose infra (biasanya `postgres`),
  **bukan** `127.0.0.1` atau `localhost` — karena EduZone jalan di container terpisah yang
  connect lewat Docker network, bukan dari host langsung.
- **`REDIS_HOST`** sama logikanya: nama service Redis di infra (biasanya `redis`), bukan
  `127.0.0.1`.
- **`REDIS_PASSWORD`**: kalau Redis di infra kamu diaktifkan `requirepass`-nya, isi persis sama
  dengan itu. Kalau Redis-nya jalan tanpa auth (umum untuk internal-only), biarkan
  `REDIS_PASSWORD=null` — jangan diisi string kosong, itu beda arti buat Laravel.

Contoh potongan `.env` yang benar, sesuai infra yang sudah kamu setup di langkah 0
(kalau kamu ganti `POSTGRES_PASSWORD` di `.env` infra dari default, sesuaikan juga di sini):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eduzone
DB_USERNAME=laravel
DB_PASSWORD=secret123

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

> `.env.example` bawaan project ini masih pakai default skeleton Laravel (`DB_CONNECTION=sqlite`,
> `REDIS_HOST=127.0.0.1`) — itu **harus diubah manual** seperti contoh di atas. Kalau nanti kamu
> ganti kredensial di `.env` infra (`C:\opt\docker\infrastructure\postgres\.env` dan
> `redis\.env`), nilai di sini harus ikut disesuaikan juga — dua-duanya harus selalu cocok.

**4. Build & jalankan semua container:**

```bash
docker compose up -d --build
```

Ini otomatis menjalankan lima service sekaligus: `app`, `vite`, `nginx`, `queue`, `scheduler`
(lihat `docker-compose.yml` di root project). Tunggu sampai build selesai (build pertama kali
paling lama, karena narik base image + install PHP extensions + composer + npm).

Cek semua container jalan:
```bash
docker ps --filter "name=eduzone"
```
Kelimanya harus berstatus `Up`. Kalau ada yang `Exited`, cek dulu `docker logs <nama_container>`
sebelum lanjut ke langkah berikutnya.

**4b. Sekarang baru nyalakan Reverb** (yang sengaja dilewat di langkah infra tadi — lihat
catatan di §0d). Volume `eduzone_vendor` sudah ada sekarang karena `app` baru selesai di-build:

```bash
cd C:\opt\docker\infrastructure\reverb
docker compose up -d
cd C:\laragon\www\eduzone

docker ps --filter "name=reverb"
```

**5. Setup aplikasi (generate key, migration, seeder):**

```bash
docker exec eduzone_app php artisan key:generate
docker exec eduzone_app php artisan migrate
docker exec eduzone_app php artisan db:seed
```

**6. Selesai.** Buka domain yang sudah kamu arahkan ke Nginx Proxy Manager / port `8083`
(default `NGINX_PORT`).

### Development Mode (Hot Reload)

Nggak perlu langkah manual tambahan — service `vite` di `docker-compose.yml` sudah otomatis
menjalankan `npm install && npm run dev -- --host` begitu container start. Edit file di
`resources/js` atau `resources/css`, otomatis reload di browser.

Kalau mau lihat log dev server-nya:
```bash
docker logs eduzone_vite -f
```

Build production (dipakai kalau `APP_TARGET=production`, atau mau generate `public/build`
manual):
```bash
docker exec eduzone_vite npm run build
```

---

## Konfigurasi Docker

### Struktur Folder Docker

`Dockerfile` dan `docker-compose.yml` ada di **root project ini** (satu repo dengan source
code Laravel) — bukan terpisah di folder infra lain:

```
C:\laragon\www\eduzone\
├── Dockerfile                       # Multi-stage: node-builder → base → development/production
├── docker-compose.yml               # Semua service: app, vite, nginx, queue, scheduler
├── docker\                          # Config yang di-copy ke image saat build
│   ├── nginx\default.conf
│   ├── php\php-dev.ini, php-fpm.conf, php-prod.ini
│   └── postgres\init-multiple-db.sh, database-config-snippet.php
└── .env                              # Dipakai BARENG oleh Laravel & Docker Compose
                                         (docker compose otomatis baca .env di folder yang sama)
```

Yang **tetap terpisah** (infrastructure shared, dipakai bareng project lain):

```
C:\opt\docker\infrastructure\
├── postgres\   ├── redis\   ├── reverb\   ├── nginx-proxy-manager\   └── ...
```

`docker-compose.yml` EduZone terhubung ke service-service ini lewat Docker network bernama
`network`, didefinisikan `external: true` — network-nya sendiri dibuat oleh compose infra,
bukan oleh compose EduZone. Makanya infra **wajib** jalan lebih dulu (lihat Prasyarat di atas).

Service Go Fiber (`go/` — lihat `ARCHITECTURE.md`) juga tetap project/repo terpisah, connect
ke network `network` yang sama.

---

### Dockerfile Overview

Dockerfile menggunakan **multi-stage build**:

```
Stage 1: node-builder    → Build Vite assets (npm ci --frozen-lockfile && npm run build)
Stage 2: base            → PHP 8.3-FPM Alpine + extensions
Stage 3: development     → Composer with dev deps, php-dev.ini
Stage 4: production      → Composer no-dev, optimized, cache:artisan
```

PHP Extensions yang diinstall: `pdo_pgsql`, `pgsql`, `gd`, `zip`, `mbstring`, `bcmath`,
`opcache`, `intl`, `pcntl`, `redis`, `grpc`, `protobuf`

---

### Service dalam `docker-compose.yml`

| Service | Container | Image/Build | Fungsi | Port host |
|---|---|---|---|---|
| `app` | `eduzone_app` | Build dari `Dockerfile`, tag `eduzone-app:{target}` | PHP-FPM | 9000 (internal) |
| `vite` | `eduzone_vite` | `node:20-alpine` | Dev server Vite, hot reload | `5174` |
| `nginx` | `eduzone_nginx` | `nginx:1.27-alpine` | Reverse proxy ke php-fpm | `8083` |
| `queue` | `eduzone_queue` | Image sama dengan `app` (reuse) | `php artisan horizon` | — |
| `scheduler` | `eduzone_scheduler` | Image sama dengan `app` (reuse) | Loop `schedule:run` tiap 60 detik | — |

`queue` dan `scheduler` sengaja **tidak** punya `build:` sendiri — mereka pakai
`image: eduzone-app:${APP_TARGET}` yang sama dengan service `app`, supaya nggak build 3x dari
Dockerfile yang identik. Konsekuensinya: kalau cuma jalanin `docker compose up -d queue` tanpa
`app` pernah di-build duluan, image-nya belum ada — jalankan `docker compose up -d` (semua
service) atau minimal `docker compose up -d app` dulu.

Infra shared yang dipakai bareng (bukan bagian compose file ini):

| Container | Image | Fungsi |
|---|---|---|
| `postgres` | `postgres:16-alpine` | Database (shared, banyak project) |
| `redis` | `redis:7-alpine` | Cache/session/queue (shared) |
| `reverb` | custom | WebSocket (shared) |

---

### Environment Variables Docker

Variable ini didaftarkan di `.env`/`.env.example` yang sama dengan variable Laravel (satu
sumber kebenaran, karena `docker-compose.yml` sekarang satu folder dengan `.env`):

| Variable | Default | Deskripsi |
|---|---|---|
| `APP_TARGET` | `development` | Build stage: `development` atau `production` |
| `APP_ENV` | `local` | Laravel `APP_ENV`, dipakai juga oleh compose |
| `APP_DEBUG` | `true` | Laravel `APP_DEBUG`, dipakai juga oleh compose |
| `NGINX_PORT` | `8083` | Port nginx di host |
| `VITE_PORT` | `5174` | Port Vite dev server di host |

---

### Perintah Docker Umum

```bash
# Nyalakan semua service
docker compose up -d

# Build ulang setelah perubahan Dockerfile/dependency
docker compose up -d --build

# Build ulang total tanpa cache (kalau --build biasa masih pakai layer lama)
docker compose build --no-cache
docker compose up -d --force-recreate

# Artisan
docker exec eduzone_app php artisan <command>

# Composer (pakai -u root untuk install package)
docker exec -u root eduzone_app composer <command>

# Build assets production
docker exec eduzone_vite npm run build

# Masuk ke container
docker exec -it eduzone_app sh

# Lihat log real-time
docker logs eduzone_app -f
docker logs eduzone_vite -f
docker logs eduzone_queue -f
docker logs eduzone_scheduler -f

# Restart satu service
docker restart eduzone_app

# Matikan semua service (tanpa hapus volume)
docker compose down

# Matikan dan hapus volume juga (HATI-HATI: data vendor/node_modules ke-reset)
docker compose down -v
```

---
## Database & Migrasi

### Konvensi Database

- **Database**: PostgreSQL 16
- **Schema**: `public`
- **Primary Key**: UUID (semua tabel utama)
- **Exception**: Tabel keuangan (`dana_bos`, `transaksi_pemasukan`, `transaksi_pengeluaran`, `pengajuan_anggaran`, `kategori_pemasukan`, `kategori_pengeluaran`) menggunakan integer auto-increment sebagai PK karena bersifat nomor urut transaksi
- `roles` table menggunakan integer PK

### UUID Rules

```php
// ✅ BENAR — primary key UUID
$table->uuid('id')->primary();

// ✅ BENAR — foreign key UUID
$table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
$table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

// ✅ BENAR — FK ke tabel keuangan (integer PK)
$table->unsignedInteger('id_kategori');
$table->foreign('id_kategori')->references('id_kategori_pemasukan')->on('kategori_pemasukan');

// ❌ SALAH
$table->foreignId('school_id'); // jangan pakai foreignId untuk UUID
```

### Perintah Migrasi

```bash
# Jalankan migration
docker exec eduzone_app php artisan migrate

# Rollback
docker exec eduzone_app php artisan migrate:rollback

# Fresh (hapus semua + migrate ulang)
docker exec eduzone_app php artisan migrate:fresh --seed

# Status
docker exec eduzone_app php artisan migrate:status
```

---

## Multi-Tenancy

EduZone menggunakan **shared database, single schema** multi-tenancy via `spatie/laravel-multitenancy` v4.

### Cara Kerja

1. User login → `AuthUserTenantFinder` mengambil `school_id` dari user
2. `School::find(school_id)` dijadikan current tenant
3. `SchoolScope` otomatis menambahkan `WHERE school_id = ?` ke semua query
4. Saat membuat record baru, `school_id` otomatis diisi dari current tenant

### Trait BelongsToSchool

Semua model yang punya kolom `school_id` **wajib** menggunakan trait ini:

```php
use App\Multitenancy\Concerns\BelongsToSchool;

class Teacher extends Model
{
    use BelongsToSchool, HasUuids;
    // ...
}
```

**Manfaat:**
- Query otomatis terfilter by `school_id`
- `school_id` otomatis diisi saat `create()`
- Relasi `school()` tersedia di semua model

### Bypass Scope (Superadmin)

```php
// Ambil data semua sekolah (superadmin)
Teacher::withoutTenant()->get();

// Superadmin tidak punya school_id → scope tidak aktif secara otomatis
// karena TenantFinder return null untuk superadmin
```

### Model Baru — Checklist

Setiap kali membuat model baru yang punya `school_id`:

- [ ] Tambah `use BelongsToSchool` trait
- [ ] Tambah `use HasUuids` trait
- [ ] Set `$keyType = 'string'`
- [ ] Set `$incrementing = false`
- [ ] Tambah `school_id` di `$fillable`

---

## Autentikasi & Role

### Dua Area Login Terpisah

| Area | URL | Deskripsi |
|---|---|---|
| Tenant | `/login` | Login untuk semua user sekolah |
| Superadmin | `/superadmin/login` | Login khusus superadmin, dark theme, rate limited |

### 10 Role User

| Role | Slug | Akses Dashboard |
|---|---|---|
| Super Admin | `superadmin` | `/superadmin/dashboard` |
| Kepala Sekolah | `kepsek` | `/kepsek/dashboard` |
| Kurikulum | `kurikulum` | `/kurikulum/dashboard` |
| Tata Usaha | `tu` | `/tu/dashboard` |
| Guru Mapel | `guru_mapel` | `/guru/dashboard` |
| Wali Kelas | `wali_kelas` | `/guru/dashboard` |
| Kesiswaan | `kesiswaan` | `/kesiswaan/dashboard` |
| Bimbingan Konseling | `bk` | `/bk/dashboard` |
| Toolman | `toolman` | `/toolman/dashboard` |
| Siswa | `siswa` | `/siswa/dashboard` |

### Middleware Stack

```php
// Route tenant biasa
Route::middleware(['auth', 'active', 'tenant', 'role:kepsek'])->group(...);

// Route superadmin
Route::middleware('superadmin')->group(...);
```

| Middleware | Fungsi |
|---|---|
| `auth` | Cek user sudah login |
| `active` | Cek `is_active = true` |
| `tenant` | Set `search_path` PostgreSQL + inisialisasi tenant |
| `role:xxx` | Cek role user, pisahkan dengan koma untuk multi-role |
| `superadmin` | Guard khusus superadmin, redirect ke `/superadmin/login` jika belum auth |
| `sync.token` | Verifikasi header `X-Sync-Token` untuk endpoint `/api/internal/sync/*` (server-to-server dari absensi-gateway) |

### Fitur Login

- Login menggunakan **email ATAU username**
- Cek `is_active` — akun nonaktif langsung ditolak
- Update `last_login_at` setiap login sukses
- Rate limiting pada superadmin login (5x per menit per IP)
- Session regenerate setelah login untuk mencegah session fixation

### Route Khusus di Luar Middleware Auth/Tenant

| Route File | Middleware | Karakteristik |
|---|---|---|
| `routes/sync.php` | `sync.token` SAJA (tanpa `web`, tanpa session/CSRF) | Server-to-server: 3 endpoint pull data master untuk absensi-gateway (`/api/internal/sync/schools`, `/people`, `/schedules`) |
| `routes/kiosk.php` | `web` (session) tapi TANPA `auth`/`tenant` | Render halaman kiosk device fisik (`/kiosk/{deviceCode}`) — identitas sekolah didapat dari kode device, bukan dari user login |

---

## Aturan Coding

### PHP / Laravel

```php
// ✅ Gunakan typed properties dan return types
public function index(): Response { }

// ✅ Gunakan early return
if (!$user->isActive()) {
    return redirect()->route('login');
}
// lanjut logika utama...

// ✅ Gunakan named arguments untuk clarity
User::create(
    school_id: $schoolId,
    role: 'guru_mapel',
);

// ❌ Hindari query N+1
$teachers = Teacher::with('user', 'major')->get(); // ✅
$teachers = Teacher::all(); // lalu $teacher->user di loop ❌

// ✅ Selalu pakai Mass Assignment Protection
protected $fillable = [...]; // atau $guarded = []

// ✅ Gunakan Form Request untuk validasi
php artisan make:request StoreTeacherRequest

// ✅ Gunakan Resource untuk API response (kalau nanti ada API)
php artisan make:resource TeacherResource
```

### Blade / View

```blade
{{-- ✅ Gunakan @isset / @empty untuk null check --}}
@isset($teacher->photo)
    <img src="{{ $teacher->photo }}">
@endisset

{{-- ✅ Gunakan @forelse untuk collection --}}
@forelse($teachers as $teacher)
    <tr>...</tr>
@empty
    <tr><td>Tidak ada data</td></tr>
@endforelse

{{-- ✅ Selalu escape output dengan {{ }} --}}
{{ $user->name }}

{{-- ⚠️  Hanya gunakan {!! !!} untuk HTML yang sudah dipastikan aman --}}
{!! $content->toHtml() !!}
```

### Database / Eloquent

```php
// ✅ Selalu gunakan DB::statement untuk search_path sebelum raw query
DB::statement('SET search_path TO public');
DB::table('users')->where(...)->update([...]);

// ✅ Gunakan withoutTenant() untuk query superadmin
Teacher::withoutTenant()->where('school_id', $id)->get();

// ✅ Gunakan transactions untuk operasi multi-tabel
DB::transaction(function () {
    $user = User::create([...]);
    Teacher::create(['user_id' => $user->id, ...]);
});

// ✅ Gunakan chunk() untuk data besar
Student::withoutTenant()->chunk(200, function ($students) {
    // proses...
});
```

---

## Konvensi Penamaan

### File & Class

| Tipe | Konvensi | Contoh |
|---|---|---|
| Model | PascalCase singular | `SchoolClass`, `StudentGrade` |
| Controller | PascalCase + Controller | `TeacherController` |
| Migration | snake_case + timestamp | `2025_01_01_000001_create_roles_table.php` |
| Seeder | PascalCase + Seeder | `SchoolSeeder` |
| View | snake_case | `student_grade.blade.php` |
| Middleware | PascalCase | `EnsureUserIsActive` |

### Database

| Tipe | Konvensi | Contoh |
|---|---|---|
| Tabel | snake_case plural | `student_grades`, `teaching_attendances` |
| Kolom | snake_case | `first_name`, `created_at` |
| Primary Key | `id` (UUID) | `id` |
| Foreign Key | `{model}_id` | `school_id`, `teacher_id` |
| Boolean | `is_` atau `has_` prefix | `is_active`, `is_homeroom` |
| Timestamp | `_at` suffix | `created_at`, `verified_at` |

### Route Naming

```
// Pola: {area}.{resource}.{action}
superadmin.schools.index
superadmin.schools.create
superadmin.schools.store
superadmin.schools.show
superadmin.schools.edit
superadmin.schools.update
superadmin.schools.destroy

kepsek.students.index
guru.grades.store
```

---

## Flow Pengembangan

### Membuat Fitur Baru

```
1. Buat/update migration
   php artisan make:migration create_xxx_table

2. Buat/update Model
   php artisan make:model Xxx
   → Tambah BelongsToSchool trait
   → Tambah HasUuids trait
   → Isi fillable, casts, relationships

3. Buat Controller
   php artisan make:controller Xxx/XxxController --resource

4. Buat Form Request (untuk validasi)
   php artisan make:request StoreXxxRequest

5. Tambah Route di file yang sesuai
   routes/tenant.php      ← untuk fitur tenant
   routes/superadmin.php  ← untuk fitur superadmin

6. Buat View
   resources/views/tenant/{role}/{feature}/index.blade.php
   resources/views/tenant/{role}/{feature}/create.blade.php

7. Build assets
   docker exec eduzone_vite npm run build
```

### Git Workflow

```bash
# Buat branch dari main
git checkout -b feature/nama-fitur

# Commit dengan pesan yang jelas
git commit -m "feat: tambah manajemen jadwal pelajaran"
git commit -m "fix: perbaiki filter kehadiran by tanggal"
git commit -m "refactor: pisahkan logic keuangan ke service class"

# Push dan buat PR
git push origin feature/nama-fitur
```

### Prefix Commit

| Prefix | Deskripsi |
|---|---|
| `feat:` | Fitur baru |
| `fix:` | Bug fix |
| `refactor:` | Refactor kode |
| `style:` | Perubahan UI/CSS |
| `docs:` | Update dokumentasi |
| `chore:` | Konfigurasi, dependency |
| `test:` | Tambah/update test |

---

## Struktur Route

```
routes/
├── web.php          # Landing page, login/logout tenant, /dashboard router
├── tenant.php       # Semua route per role tenant (diload via bootstrap/app.php)
├── superadmin.php   # Semua route superadmin (prefix: /superadmin)
├── kiosk.php        # Halaman kiosk device absensi GET /kiosk/{deviceCode} (tanpa auth/tenant — identitas dari deviceCode)
├── sync.php         # 3 endpoint internal sync pull untuk absensi-gateway (prefix: /api/internal/sync, middleware sync.token: X-Sync-Token, TANPA session/CSRF)
└── console.php      # Artisan commands

# Prefix otomatis dari bootstrap/app.php:
# - superadmin.php → /superadmin/* dengan name superadmin.*
# - tenant.php     → /* tanpa prefix tambahan
# - sync.php       → /api/internal/sync/* dengan middleware sync.token
```

### Contoh Struktur Route Tenant

```php
// routes/tenant.php
Route::middleware(['auth', 'active', 'tenant'])->group(function () {

    Route::prefix('kepsek')->name('kepsek.')->middleware('role:kepsek')->group(function () {
        Route::get('/dashboard', [KepsekDashboardController::class, 'index'])->name('dashboard');
        Route::get('/students',  [KepsekStudentController::class, 'index'])->name('students.index');
    });

    Route::prefix('guru')->name('guru.')->middleware('role:guru_mapel,wali_kelas')->group(function () {
        Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
        Route::resource('grades', GuruGradeController::class);
    });

    // Dashboard absensi Wali Kelas — sudah aktif:
    Route::middleware('role:wali_kelas')->group(function () {
        Route::get('/absensi', [WaliKelas\AbsensiController::class, 'dashboard'])
            ->name('wali_kelas.absensi.dashboard');
    });
});
```

---

## Struktur View

```
resources/views/
├── welcome.blade.php              # Landing page publik
│
├── auth/
│   └── login.blade.php            # Login tenant (indigo-pink gradient)
│
├── kiosk/
│   └── checkin.blade.php          # Layar kiosk device absensi (RFID/QR/Face) — entry kiosk.js
│
├── superadmin/
│   ├── auth/
│   │   └── login.blade.php        # Login superadmin (dark theme)
│   ├── layouts/
│   │   └── app.blade.php          # Layout: sidebar + topbar dark
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── schools/
│   │   ├── index.blade.php        # CRUD sekolah (list + form)
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── subscriptions/
│   │   ├── index.blade.php        # CRUD langganan (list + form)
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── absensi/
│   │   ├── health.blade.php       # Dashboard health: gateway/DB/sync/freshness per sekolah
│   │   └── devices/               # CRUD device absensi (index/create/edit)
│   │       ├── index.blade.php
│   │       ├── create.blade.php
│   │       └── edit.blade.php
│   ├── logs/
│   │   └── index.blade.php        # Activity log superadmin
│   └── users/
│       └── index.blade.php        # List user lintas sekolah
│
└── tenant/
    ├── layouts/
    │   └── app.blade.php          # Shared layout untuk semua tenant
    ├── absensi/
    │   └── dashboard.blade.php    # Dashboard absensi Wali Kelas
    ├── kepsek/
    │   └── dashboard/
    │       └── index.blade.php
    ├── guru/
    │   └── dashboard/
    │       └── index.blade.php
    └── shared/
        └── components/            # Komponen yang dipakai lintas role
```

### Konvensi View

- Setiap area (superadmin, kepsek, guru, dst.) punya layout sendiri yang di-extend
- Komponen yang dipakai lebih dari satu role diletakkan di `shared/components/`
- Gunakan `@section('title')` dan `@section('page-title')` untuk judul halaman
- Gunakan `@push('styles')` dan `@push('scripts')` untuk CSS/JS per halaman

---

## Panduan Model & Eloquent

### Template Model Baru (dengan school_id)

```php
<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamaModel extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'nama_tabel';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'school_id',    // selalu ada
        'kolom_lain',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
    ];

    // Tambah relationships sesuai kebutuhan
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

### Relasi School

Karena `BelongsToSchool` trait sudah menyediakan relasi `school()`, **jangan** mendefinisikan ulang di model:

```php
// ❌ JANGAN — sudah ada di trait
public function school(): BelongsTo
{
    return $this->belongsTo(School::class, 'school_id');
}

// ✅ Cukup pakai dari trait
$teacher->school->name;
```

---

## Seeder & Data Dummy

### Menjalankan Seeder

```bash
# Semua seeder
docker exec eduzone_app php artisan db:seed

# Seeder spesifik
docker exec eduzone_app php artisan db:seed --class=SchoolSeeder

# Fresh migration + seed
docker exec eduzone_app php artisan migrate:fresh --seed
```

### Membuat Seeder Baru

```php
// database/seeders/MajorSeeder.php
class MajorSeeder extends Seeder
{
    public function run(): void
    {
        $schools = DB::table('schools')->get();

        foreach ($schools as $school) {
            DB::table('majors')->insert([
                'id'        => Str::uuid(),
                'school_id' => $school->id,
                'name'      => 'IPA',
                // ...
            ]);
        }
    }
}
```

---

## Akun Default

Setelah menjalankan `db:seed`:

### Superadmin

| Field | Value |
|---|---|
| URL Login | `/superadmin/login` |
| Email | `superadmin@eduzone.id` |
| Password | `superadmin123` |

### Developer Tools (Superadmin Only)

| Tool | URL | Deskripsi |
|---|---|---|
| Laravel Horizon | `/horizon` | Monitor queue, jobs, failed jobs, throughput |
| Laravel Telescope | `/telescope` | Debug request, query SQL, exception, cache, mail |
| Debugbar | Bar bawah browser | Query real-time per halaman, otomatis muncul di local |

> **Catatan:** Horizon dan Telescope hanya bisa diakses oleh user dengan role `superadmin`. Debugbar otomatis aktif di environment `local` untuk semua halaman.

### Superadmin

| Field | Value |
|---|---|
| URL Login | `/superadmin/login` |
| Email | `superadmin@eduzone.id` |
| Password | `superadmin123` |

### Tenant — Sekolah 1 (SMA Negeri 1 Demo)

| Role | Username | Email | Password |
|---|---|---|---|
| Kepala Sekolah | `kepsek_demo` | `kepsek@demo.sch.id` | `password123` |
| Kurikulum | `kurikulum_demo` | `kurikulum@demo.sch.id` | `password123` |
| Tata Usaha | `tu_demo` | `tu@demo.sch.id` | `password123` |
| Guru Mapel | `guru_demo` | `guru@demo.sch.id` | `password123` |
| Wali Kelas | `wakel_demo` | `wakel@demo.sch.id` | `password123` |
| Kesiswaan | `kesiswaan_demo` | `kesiswaan@demo.sch.id` | `password123` |
| BK | `bk_demo` | `bk@demo.sch.id` | `password123` |
| Toolman | `toolman_demo` | `toolman@demo.sch.id` | `password123` |
| Siswa | `siswa_demo` | `siswa@demo.sch.id` | `password123` |

### Tenant — Sekolah 2 (SMK Negeri 2 Demo)

Sama seperti di atas tapi suffix `_demo2` dan domain `@demo2.sch.id`.

---

## Troubleshooting

### `relation "users" does not exist`

PostgreSQL `search_path` berubah. Solusi:

```bash
# Flush Redis session
docker exec redis redis-cli FLUSHALL

# Clear cache
docker exec eduzone_app php artisan cache:clear
```

### Session invalid setelah migration UUID

Hapus cookies browser atau buka tab incognito.

### Permission denied (composer/artisan)

```bash
docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html
```

### Asset tidak terupdate

```bash
docker exec eduzone_vite npm run build
```

### Telescope tidak bisa diakses

Pastikan sudah login sebagai superadmin. Telescope hanya bisa diakses oleh role `superadmin` di semua environment.

```bash
# Jika data Telescope terlalu banyak, bersihkan
docker exec eduzone_app php artisan telescope:clear
```

### Horizon tidak aktif

```bash
# Cek status
docker exec eduzone_queue php artisan horizon:status

# Restart jika perlu
docker restart eduzone_queue
```

---

## Lisensi

Project ini bersifat privat. Seluruh kode dan aset adalah milik tim pengembang EduZone.

---

*Dokumentasi ini diperbarui seiring perkembangan project. Pastikan selalu baca README terbaru sebelum mulai kontribusi.*
