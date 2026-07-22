# Tutorial Setup EduZone (Development)

Panduan ini untuk anggota tim yang ingin menjalankan EduZone di komputer sendiri — clone dari GitHub, lalu build image Docker sendiri (bukan pull dari Docker Hub).

---

## 0. Prasyarat

Sebelum mulai, pastikan sudah terpasang:

- **Docker Desktop** (Windows/Mac) — sudah jalan
- **Git**
- Infrastruktur shared (Postgres, Redis, network `network`) — sudah `up` duluan di komputer kamu, terpisah dari repo EduZone. Kalau belum ada, tanya ke [nama kamu/tim] cara setup-nya, karena EduZone bergantung ke ini.

Cek network shared sudah ada:
```powershell
docker network ls
```
Harus ada network bernama `network`. Kalau belum ada, EduZone nggak akan bisa connect ke Postgres/Redis.

---

## 1. Clone repo

```powershell
git clone <URL_REPO_GITHUB>
cd eduzone
```

---

## 2. Siapkan file `.env`

Copy dari template:
```powershell
copy .env.example .env
```

Buka `.env`, lalu isi bagian ini (sesuaikan dengan kredensial infra tim):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eduzone_saas
DB_USERNAME=laravel
DB_PASSWORD=<tanya_ke_tim>

QUEUE_CONNECTION=redis
CACHE_STORE=redis

BROADCAST_CONNECTION=reverb

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

REVERB_APP_ID=<tanya_ke_tim>
REVERB_APP_KEY=<tanya_ke_tim>
REVERB_APP_SECRET=<tanya_ke_tim>
REVERB_HOST=reverb
REVERB_PORT=8082
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> `REVERB_APP_ID/KEY/SECRET` dan `DB_PASSWORD` harus **sama** dengan yang dipakai infra shared — minta ke rekan yang setup infra kalau belum tahu.

---

## 3. Build image & jalankan container

Build dilakukan otomatis oleh `docker compose up` kalau image belum ada, tapi supaya jelas prosesnya, bisa dipisah:

```powershell
docker compose build
docker compose up -d
```

Proses build pertama kali agak lama (~1–2 menit) karena compile beberapa PHP extension. Build berikutnya jauh lebih cepat karena Docker cache layer.

Cek semua container sudah `Up`:
```powershell
docker ps
```

---

## 4. Setup awal aplikasi (sekali saja)

```powershell
docker exec eduzone_app php artisan key:generate
docker exec eduzone_app php artisan migrate
docker exec eduzone_app php artisan reverb:install --no-interaction
```

Kalau langkah manapun di atas kena `Permission denied`, jalankan:
```powershell
docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html
```
lalu ulangi command yang gagal.

---

## 5. Akses aplikasi

- Web: **http://localhost:8083**
- Vite dev server (hot reload): berjalan otomatis di port `5174`
- Reverb (WebSocket): port `8082`

---

## 6. Command harian yang sering dipakai

```powershell
# Lihat log semua service
docker compose logs -f

# Lihat log satu service
docker logs eduzone_app --tail 50
docker logs eduzone_queue --tail 50
docker logs reverb --tail 50

# Jalankan artisan command
docker exec eduzone_app php artisan <command>

# Jalankan composer
docker exec -u root eduzone_app composer <command>

# Build asset frontend
docker exec eduzone_vite npm run build

# Restart semua service
docker compose restart

# Matikan semua service
docker compose down
```

---

## 7. Troubleshooting Umum

| Gejala | Penyebab | Solusi |
|---|---|---|
| `eduzone_queue` restart terus, error `RedisException: Connection refused` | `.env` masih `REDIS_HOST=127.0.0.1` | Ganti ke `REDIS_HOST=redis` |
| `reverb` restart terus, `no commands defined in "reverb" namespace` | Package `laravel/reverb` belum lengkap terpasang | `docker exec eduzone_app composer show laravel/reverb`, lalu `reverb:install` |
| `Failed to open stream: Permission denied` saat `key:generate`/`migrate`/`reverb:install` | Ownership file nggak cocok dengan user `laravel` di container | `docker exec -u root eduzone_app chown -R laravel:laravel /var/www/html` |
| `relation "users" does not exist` | Search path Postgres nyasar / DB salah | Flush Redis session + `php artisan cache:clear`, cek `DB_DATABASE` benar `eduzone_saas` |
| Asset (CSS/JS) nggak update di browser | Belum build ulang | `docker exec eduzone_vite npm run build` |
| `TTY mode requires /dev/tty` saat jalankan artisan command tertentu | Command butuh interaktif tapi `docker exec` tanpa `-it` | Tambahkan flag `-it`: `docker exec -it eduzone_app php artisan <command>` |

---

## 8. Catatan Penting

- **Jangan commit `.env`** ke Git — sudah ada di `.gitignore`. Kalau butuh update variabel baru, update juga `.env.example` (tanpa isi secret aslinya).
- Semua perintah `artisan`/`composer`/`npm` dijalankan **di dalam container**, bukan langsung di komputer kamu.
- Kalau ganti `Dockerfile` atau `docker-compose.yml`, perlu `docker compose build` ulang supaya perubahan kepakai.
