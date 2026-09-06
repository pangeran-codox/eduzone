# Tutorial Setup EduZone (Development)

Panduan ini untuk anggota tim yang ingin menjalankan EduZone di komputer sendiri — clone dari GitHub, lalu build image Docker sendiri (bukan pull dari Docker Hub).

---

## 0. Prasyarat

Sebelum mulai, pastikan sudah terpasang:

- **Docker Desktop** (Windows/Mac) — sudah jalan
- **Git**
- Infrastruktur shared (Postgres, Redis, network `network`) — sudah `up` duluan di komputer kamu, terpisah dari repo EduZone. Kalau belum ada, tanya ke tim cara setup-nya, karena EduZone bergantung ke ini.

Cek network shared sudah ada:
```powershell
docker network ls
```
Harus ada network bernama `network`. Kalau belum ada, EduZone nggak akan bisa connect ke Postgres/Redis.

Cek juga nama container Postgres/Redis di komputer kamu — **bisa beda-beda tiap komputer** (kadang `postgres`, kadang `infrastructure-postgres`, tergantung nama project compose infra):
```powershell
docker ps
```
Catat nama container Postgres & Redis yang muncul — dipakai nanti di `.env` (`DB_HOST`, `REDIS_HOST`).

---

## 1. Clone repo

```powershell
git clone <URL_REPO_GITHUB>
cd eduzone
```

---

## 2. Buat database di Postgres (kalau belum ada)

Cek dulu database yang dibutuhkan sudah ada atau belum (ganti `postgres` kalau nama container di komputermu beda):
```powershell
docker exec postgres psql -U laravel -l
```

EduZone butuh 2 database:
- `eduzone` — database utama (sesuai `.env.example` & README resmi, BUKAN `eduzone_saas`)
- `eduzone_absensi` — database khusus modul Absensi (terpisah karena high-concurrency, lihat ARCHITECTURE.md §2)

Kalau belum ada, buat manual:
```powershell
docker exec postgres psql -U laravel -d postgres -c "CREATE DATABASE eduzone;"
docker exec postgres psql -U laravel -d postgres -c "CREATE DATABASE eduzone_absensi;"
```
(pakai `-d postgres` supaya psql connect ke database default dulu, bukan ke database bernama sama dengan usernya)

---

## 3. Siapkan file `.env`

Copy dari template:
```powershell
copy .env.example .env
```

Isi/pastikan bagian ini benar (sesuaikan `DB_HOST`/`REDIS_HOST` dengan nama container di komputermu dari langkah 0):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eduzone
DB_USERNAME=laravel
DB_PASSWORD=secret123

# --- Koneksi DB kedua untuk modul Absensi (JANGAN skip meskipun modulnya
#     belum dipakai sekarang — ada 19 migration file yang pakai connection
#     `pgsql_absensi`, jadi `php artisan migrate` akan gagal kalau koneksi
#     ini tidak bisa terkoneksi / database belum ada) ---
DB_ABSENSI_CONNECTION=pgsql_absensi
DB_ABSENSI_HOST=postgres
DB_ABSENSI_PORT=5432
DB_ABSENSI_DATABASE=eduzone_absensi
DB_ABSENSI_USERNAME=laravel
DB_ABSENSI_PASSWORD=secret123

QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=reverb

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

REVERB_APP_ID=659388
REVERB_APP_KEY=p6hnv3in1kkeezxpwwwr
REVERB_APP_SECRET=s82fjc9tzsl0otlc2z2c
REVERB_HOST=reverb
REVERB_PORT=8082
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# --- Integrasi dengan absensi-gateway (Go) ---
# (Wajib diisi sebelum `php artisan migrate` / menjalankan Health Check
#  Absensi di dashboard superadmin. Isi dengan random string kamu sendiri
#  untuk dev lokal, kecuali base_url di bawah — biasanya sudah benar default-nya
#  kalau absensi-gateway jalan di docker compose bareng network `network`.)

# Base URL Go Gateway (nama container Docker absensi-gateway + port internal 8080)
ABSENSI_GATEWAY_BASE_URL=http://absensi-gateway-absensi-gateway-1:8080

# Shared-secret HS256 JWT untuk check-in guru via HP (harus identik dengan
# JWT_SECRET di .env milik absensi-gateway) — boleh dikosongkan dulu kalau
# modul check-in guru via HP belum dipakai di dev lokal.
ABSENSI_GATEWAY_JWT_SECRET=
ABSENSI_GATEWAY_JWT_TTL=900

# Token server-to-server untuk endpoint sync (Go pull data master dari Laravel
# lewat /api/internal/sync/*). Middleware VerifySyncToken reject semua request
# kalau ini kosong. Harus identik dengan LARAVEL_SYNC_TOKEN di .env gateway.
# Isi dengan random string 32+ karakter.
ABSENSI_SYNC_TOKEN=
```

> **Kesalahan paling sering:** `DB_HOST`/`REDIS_HOST` diisi `127.0.0.1` atau `localhost`. Ini salah — di dalam container, nama host harus nama **container lain** (`postgres`, `redis`), bukan alamat diri sendiri.

---

## 4. Build image & jalankan container

```powershell
docker compose build
docker compose up -d
```

Proses build pertama kali agak lama (beberapa menit) karena compile PHP extension + `npm run build`. Build berikutnya jauh lebih cepat karena Docker cache layer.

Cek semua container sudah `Up` (bukan `Restarting`):
```powershell
docker ps
```

> Kalau build gagal dengan error jaringan (`failed to resolve source metadata`, `Empty reply from server`, dll) — itu biasanya gangguan koneksi sesaat ke Docker Hub/GitHub, bukan masalah konfigurasi. Coba ulangi `docker compose build` lagi.

---

## 5. Setup awal aplikasi (sekali saja per komputer)

```powershell
docker exec eduzone_app php artisan key:generate
docker exec eduzone_app php artisan migrate
```

**Jangan jalankan `php artisan reverb:install` lagi** — config Reverb (`config/broadcasting.php`, `config/reverb.php`, `routes/channels.php`) sudah ke-commit di repo dan otomatis ikut ter-clone. Menjalankannya lagi cuma akan memunculkan error "configuration file already exists" (tidak berbahaya, tapi tidak perlu).

Kalau ada langkah di atas kena `Permission denied`:
```powershell
docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html
```
lalu ulangi command yang gagal.

---

## 6. Isi data dummy (seeder)

```powershell
docker exec eduzone_app php artisan db:seed
```

Ini menjalankan seeder secara berurutan (Role → Superadmin → School, dst) sesuai yang didaftarkan di `DatabaseSeeder.php`. Aman dijalankan di database yang masih kosong.

> **Hati-hati:** jangan jalankan `php artisan migrate:fresh --seed` di database yang sudah ada isinya — command itu **menghapus semua tabel** dulu sebelum migrate ulang.

---

## 7. Akses aplikasi

- Web: **http://localhost:8083**
- Vite dev server (hot reload): port `5174`
- Reverb (WebSocket): port `8082`
- Adminer (lihat isi database lewat browser): **http://localhost:8081** — server: `postgres` (atau nama container Postgres di komputermu), user: `laravel`, database: `eduzone` (utama) atau `eduzone_absensi` (modul Absensi)

---

## 8. Command harian yang sering dipakai

```powershell
# Lihat log semua service
docker compose logs -f

# Lihat log satu service
docker logs eduzone_app --tail 50
docker logs eduzone_queue --tail 50
docker logs reverb --tail 50

# Jalankan artisan command
docker exec eduzone_app php artisan <command>

# Jalankan composer (sebagai root, karena app jalan sebagai user non-root)
docker exec -u root eduzone_app composer <command>

# Build asset frontend
docker exec eduzone_vite npm run build

# Restart semua service
docker compose restart

# Matikan semua service
docker compose down
```

---

## 9. Kalau kamu menambahkan package baru (composer/npm)

Ini penting supaya **anggota tim lain nggak ketemu error saat clone/build**:

- **Tambah package PHP**: `docker exec -u root eduzone_app composer require <package>` → ini otomatis update `composer.json` dan `composer.lock`. **Wajib commit & push keduanya bersamaan**, jangan cuma salah satu.
- **Tambah package JS**: `docker run --rm -v ${PWD}:/app -w /app node:20-alpine npm install <package>` → ini update `package.json` dan `package-lock.json`. **Wajib commit & push keduanya bersamaan**.
- Setelah menambah package apapun, jalankan `git status` dulu sebelum commit — pastikan **kedua file** (manifest + lockfile) ikut ter-stage. Kalau cuma salah satu yang ke-push, orang lain yang `git pull` akan kena error `npm ci`/`composer install` karena manifest dan lockfile nggak sinkron.

---

## 10. Troubleshooting Umum

| Gejala | Penyebab | Solusi |
|---|---|---|
| `eduzone_queue` restart terus, error `RedisException: Connection refused` | `.env` masih `REDIS_HOST=127.0.0.1` | Ganti ke `REDIS_HOST=redis` (nama container, bukan `127.0.0.1`) |
| `reverb` restart terus, `no commands defined in "reverb" namespace` | Package `laravel/reverb` belum ter-install lengkap di volume `eduzone_vendor` | `docker exec eduzone_app composer show laravel/reverb` untuk cek; kalau belum ada, `docker exec -u root eduzone_app composer require laravel/reverb`, lalu `docker restart reverb` |
| `npm run build` gagal: `Rollup failed to resolve import "laravel-echo"` (atau package lain) | `package.json` tidak punya entry package itu meskipun sudah dipakai di kode | Tambahkan manual ke `"dependencies"` di `package.json`, lalu `npm install` untuk sinkronkan lockfile, commit keduanya |
| `npm ci` gagal: `Missing: <package> from lock file` | `package.json` dan `package-lock.json` tidak sinkron (biasanya salah satu di-commit, satunya tidak) | Jalankan `npm install` (bukan `npm ci`) untuk regenerate lockfile, commit & push `package-lock.json` |
| `Failed to open stream: Permission denied` saat `key:generate`/`migrate`/`reverb:install` | Ownership file nggak cocok dengan user `laravel` di container | `docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html` |
| `psql: FATAL: database "laravel" does not exist` | `psql` connect ke database bernama sama dengan user, padahal belum ada | Tambahkan `-d postgres`: `docker exec postgres psql -U laravel -d postgres -c "..."` |
| `relation "users" does not exist` | Search path Postgres nyasar / `DB_DATABASE` salah | Flush Redis session + `php artisan cache:clear`, cek `DB_DATABASE` sudah benar |
| Asset (CSS/JS) nggak update di browser | Belum build ulang | `docker exec eduzone_vite npm run build` |
| `TTY mode requires /dev/tty` saat jalankan artisan command tertentu | Command butuh interaktif tapi `docker exec` tanpa `-it` | Tambahkan flag `-it`: `docker exec -it eduzone_app php artisan <command>` |
| `git push` ditolak: `non-fast-forward` | Ada commit baru di GitHub yang belum di-pull | `git pull origin main` dulu, baru `git push` |
| `git pull` gagal: `local changes would be overwritten by merge` | Ada file lokal (biasanya lockfile) yang berubah tapi belum di-commit | Kalau perubahan lokal itu tidak penting/tidak sengaja: `git restore <file>`, lalu `git pull` lagi |
| `docker compose build` gagal: `failed to resolve source metadata`, `Empty reply from server` | Gangguan koneksi sesaat ke Docker Hub/GitHub | Ulangi command yang sama, biasanya berhasil di percobaan berikutnya |

---

## 11. Catatan Penting

- **Jangan commit `.env`** ke Git — sudah ada di `.gitignore`. Kalau butuh tambah variabel baru, update juga `.env.example` (tanpa isi secret aslinya).
- Semua perintah `artisan`/`composer`/`npm` dijalankan **di dalam container**, bukan langsung di komputer kamu.
- Kalau ganti `Dockerfile` atau `docker-compose.yml`, perlu `docker compose build` ulang supaya perubahan kepakai.
- Nama container infrastruktur (Postgres/Redis/Adminer) **bisa beda tiap komputer** tergantung nama project compose infra yang dipakai — selalu cek `docker ps` di komputer yang bersangkutan, jangan asumsikan sama dengan komputer lain.