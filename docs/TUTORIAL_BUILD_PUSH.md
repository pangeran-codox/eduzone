# Tutorial: Build Image Production & Push ke Registry (Docker Hub / GHCR / Custom)

Panduan **lengkap dan step-by-step** untuk build image production EduZone lalu push ke container registry (paling umum: **Docker Hub**), menggunakan script `build.ps1` otomatis. Semua perintah dijalankan dari root folder project (c:\laragon\www\eduzone) di **PowerShell Windows** (PowerShell 5.1 ke atas, atau PowerShell 7).

> **Sumber utama:** Script utama: [build.ps1](file:///c:/laragon/www/eduzone/build.ps1)
> **Referensi lebih lanjut:**
> - [DEPLOY-SWARM.md](file:///c:/laragon/www/eduzone/docs/DEPLOY-SWARM.md) — step deploy setelah image dipush
> - [Dockerfile](file:///c:/laragon/www/eduzone/Dockerfile) — multi-stage build, target `development` vs `production`
> - [docker-compose.swarm.yml](file:///c:/laragon/www/eduzone/docker-compose.swarm.yml) — stack production untuk Swarm

---

## Daftar Isi

1. [Prasyarat Wajib (Lakukan SEKALI)](#1-prasyarat-wajib-lakukan-sekali)
2. [Ringkasan Alur Kerja (Agar Tidak Bingung)](#2-ringkasan-alur-kerja-agar-tidak-bingung)
3. [Cara PALING MUDAH: Pakai Shortcut `-Release` (Interaktif)](#3-cara-paling-mudah-pakai-shortcut--release-interaktif)
4. [Pakai Shortcut Docker Hub: `-DockerHub <username>`](#4-pakai-shortcut-docker-hub--dockerhub-username)
5. [Semantic Versioning (SemVer) Auto Bump](#5-semantic-versioning-semver-auto-bump)
6. [Mode Non-Interaktif (CI/CD / Pipeline)](#6-mode-non-interaktifcicd--pipeline)
7. [Push Image YANG SUDAH ADA (tanpa build ulang)](#7-push-image-yang-sudah-ada-tanpa-build-ulang)
8. [Push ke Registry Selain Docker Hub (GHCR / Private Registry)](#8-push-ke-registry-selain-docker-hub-ghcr--private-registry)
9. [Sesudah Push: Deploy ke Docker Swarm](#9-sesudah-push-deploy-ke-docker-swarm)
10. [Troubleshooting Umum](#10-troubleshooting-umum)
11. [Daftar Parameter Lengkap](#11-daftar-parameter-lengkap)

---

## 1. Prasyarat Wajib (Lakukan SEKALI)

Lakukan ini **satu kali saja**, tidak perlu diulang setiap release:

### 1.1 Docker Login ke Registry Target

#### Docker Hub (Paling Umum)
```powershell
docker login
```
Masukkan Docker Hub **username** (bukan email!) dan **Access Token** / password.
> Untuk keamanan, **disarankan buat Access Token** (bukan pakai password akun):
> https://hub.docker.com/settings/security → New Access Token → scope `Read & Write`.

#### GitHub Container Registry (GHCR)
```powershell
docker login ghcr.io -u <USERNAME_GITHUB> -p <CLASSIC_PAT_WRITE_PACKAGES>
```
PAT (Personal Access Token) harus punya scope `write:packages`. Buat di:
https://github.com/settings/tokens (wajib tipe **Classic**, BUKA Fine-Grained untuk GHCR image push agar lebih kompatibel).

#### Private Registry
```powershell
docker login registry.acme.co.id -u <USER> -p <PASSWORD>
```

### 1.2 (Opsional) Pastikan .env Production Sudah Benar

Kalau kamu mau `.env` local kamu production-ready (berguna kalau mau `artisan optimize` / `migrate --force` di local SEBELUM push — tidak wajib, tapi lebih aman):

Periksa `.env` dan pastikan nilai berikut:

```env
APP_ENV=production
APP_DEBUG=false

# Critical keys (JANGAN KOSONG untuk production):
APP_KEY=<base64:....>          # Generate via `php artisan key:generate`
DB_PASSWORD=<password postgres>
REVERB_APP_ID=<random>
REVERB_APP_KEY=<random>
REVERB_APP_SECRET=<random>
ABSENSI_GATEWAY_JWT_SECRET=<random PANJANG>
ABSENSI_SYNC_TOKEN=<random PANJANG BEDA dari JWT di atas>
ENCRYPTION_SERVICE_API_KEY=<token dari Rust encryption service>
```

---

## 2. Ringkasan Alur Kerja (Agar Tidak Bingung)

```
 .\build.ps1 -Release
        │
        ▼
  [1] Validasi Prasyarat
       ├─ PHP >= 8.2, Composer, Node >= 20, Docker
       └─ Wajib login ke registry (Docker Hub / GHCR)
        │
        ▼
  [2] Prompt Tag Versi (jika -Tag kosong)
       ├─ Lihat git tag terbaru: mis. v1.2.5
       ├─ Suggest 1) Patch v1.2.6 | 2) Minor v1.3.0 | 3) Major v2.0.0
       └─ Atau input tag custom: v2.0.0-beta.1
        │
        ▼
  [3] Prompt Registry (jika -DockerHub / -Registry kosong)
       ├─ 1) Docker Hub | 2) GHCR | 3) Custom
       └─ Hasil: mis. docker.io/maikel  atau  ghcr.io/acme
        │
        ▼
  [4] Composer install (Production: --no-dev --optimize-autoloader)
        │
        ▼
  [5] npm ci --frozen-lockfile  +  npm run build  ->  public/build/
        │
        ▼
  [6] Laravel optimize (config:cache, route:cache, view:cache, event:cache)
        │
        ▼
  [7] Docker build --target production
       ├─ Stage 1: node-builder (npm build assets)
       ├─ Stage 2: base PHP 8.3 + ext-grpc/protobuf/redis
       └─ Stage 4: production (composer --no-dev + artisan cache)
        │
        ▼
  [8] Docker push (tag versi + :latest)
       ├─ PUSH maikel/eduzone-app:v1.2.6
       └─ PUSH maikel/eduzone-app:latest
        │
        ▼
  [9] Summary + langkah deploy ke Swarm (copy-paste ready!)
```

---

## 3. Cara PALING MUDAH: Pakai Shortcut `-Release` (Interaktif)

Ini cara **paling direkomendasikan** untuk rilis manual (bukan CI/CD otomatis). Cukup:

```powershell
cd C:\laragon\www\eduzone
.\build.ps1 -Release
```

Script akan memandu kamu dengan prompt interaktif. **Contoh alurnya:**

```
# Release aktif: set Mode=Production, DockerBuild=True, Push=True

  ─── Pilih Registry Target ───
    1) Docker Hub (paling umum) -> perlu -DockerHub <username>
    2) GitHub Container Registry (ghcr.io/username)
    3) Custom Registry (registry.acme.id/prefix)

  Pilih [1/2/3]: 1
  Docker Hub username: maikel
[OK] Registry target: docker.io/maikel

[WARN] Tag versi belum diset (-Tag kosong) + -Push aktif.

  Latest git tag: v1.2.5
  Suggested bump:
    1) Patch  -> v1.2.6  (bugfix / minor)
    2) Minor  -> v1.3.0  (new feature)
    3) Major  -> v2.0.0  (breaking change)
    4) Custom -> ketik manual (mis. v2.0.0-beta.1)

  Pilih [1/2/3/4] atau ketik custom tag [default: 1 = v1.2.6]: 1

  ─── Docker Target Summary ───
  Stage        : production
  Image tag    : docker.io/maikel/eduzone-app:v1.2.6
  :latest alias: docker.io/maikel/eduzone-app:latest
  Push target  : docker.io/maikel
```

Lalu script otomatis menjalankan semua step build. **Tinggal tunggu (bisa 30–60 menit)** karena Docker stage `production` compile `ext-grpc` dari source C++ (normal, bukan hang!).

---

## 4. Pakai Shortcut Docker Hub: `-DockerHub <username>`

Kalau kamu **selalu pakai Docker Hub** dengan username yang sama, **tidak perlu prompt** lagi. Cukup satu baris:

```powershell
# Satu perintah lengkap:
.\build.ps1 -Mode Production -DockerHub maikel -Tag v1.3.0 -DockerBuild -Push

# ATAU LEBIH PENDEK (pakai -Release shortcut yang auto-set Production+Build+Push):
.\build.ps1 -Release -DockerHub maikel -Tag v1.3.0
```

Hasilnya image di-tag sebagai:
- **`maikel/eduzone-app:v1.3.0`** → tag permanen (untuk rollback ke versi tertentu)
- **`maikel/eduzone-app:latest`** → tag paling baru (default kalau user pull tanpa tag)

---

## 5. Semantic Versioning (SemVer) Auto Bump

Script bisa menentukan tag versi **OTOMATIS** berdasarkan git tag terakhir di repo. Aturan standar SemVer:
- **PATCH** (x.y.**Z**+1) — untuk bugfix, minor tweak, hotfix
- **MINOR** (x.**Y**.0, Z reset) — ada fitur baru, backward compatible
- **MAJOR** (**X**.0.0, Y & Z reset) — breaking change: butuh user update, rename kolom DB, dll

Cara pakai `-Bump`:

```powershell
# Berdasarkan git tag v1.2.5:

# 1) Auto PATCH -> v1.2.6 (cocok untuk rutin, bugfix kecil)
.\build.ps1 -Release -DockerHub maikel -Bump Patch

# 2) Auto MINOR -> v1.3.0 (fitur baru ada)
.\build.ps1 -Release -DockerHub maikel -Bump Minor

# 3) Auto MAJOR -> v2.0.0 (release besar / breaking)
.\build.ps1 -Release -DockerHub maikel -Bump Major
```

Kalau **belum ada git tag sama sekali** (project baru pertama kali release), script otomatis default ke **`v0.1.0`**.

---

## 6. Mode Non-Interaktif (CI/CD / Pipeline)

Untuk **GitHub Actions / GitLab CI / Jenkins / PowerShell script otomatis** — tidak boleh ada prompt interaktif. Pakai kombinasi:

```powershell
.\build.ps1 `
    -Release `
    -DockerHub $env:DOCKER_HUB_USER `
    -Bump Patch `
    -Force `
    -TimeoutSeconds 3600 `
    -Verbose
```

| Flag | Fungsi |
|---|---|
| `-Force` | **Skip SEMUA prompt konfirmasi** (wajib di CI). |
| `-Bump Patch/Minor/Major` | Tentukan tag versi auto dari git tag. |
| `-TimeoutSeconds 3600` | Timeout dinaikkan ke 60 menit (build grpc bisa lambat di runner cloud). |
| `-Verbose` | Log stdout/stderr per command (berguna untuk debug CI). |

Untuk login Docker di CI, lakukan terlebih dahulu:
```powershell
# Sebelum panggil build.ps1 (di step "before_script"):
$env:DOCKER_PWD | docker login -u $env:DOCKER_HUB_USER --password-stdin
```

---

## 7. Push Image YANG SUDAH ADA (tanpa build ulang)

Kasus: kemarin kamu sudah `docker build` tapi belum push (atau push gagal karena jaringan). Tidak perlu build ulang (buang 30 menit!):

```powershell
# Misal image sudah ada di local: maikel/eduzone-app:v1.2.6
.\build.ps1 -DockerHub maikel -Tag v1.2.6 -Push
```

Script akan:
1. Jalankan `docker image inspect maikel/eduzone-app:v1.2.6` → pastikan image ada
2. Kalau TIDAK ADA → error dengan perintah build manual yang perlu dijalankan
3. Kalau ADA → langsung push tag `v1.2.6` dan alias `:latest`

---

## 8. Push ke Registry Selain Docker Hub (GHCR / Private Registry)

Gunakan **`-Registry`** (bukan `-DockerHub`):

### 8.1 GitHub Container Registry (ghcr.io)
```powershell
# Satu perintah:
.\build.ps1 -Release -Registry ghcr.io/acme -Tag v1.2.6

# Hasil image:
#   ghcr.io/acme/eduzone-app:v1.2.6
#   ghcr.io/acme/eduzone-app:latest
```

### 8.2 Private Registry (Harbor / Nexus / DigitalOcean Registry)
```powershell
.\build.ps1 -Release -Registry registry.acme.co.id/eduzone -Tag v1.2.6
# Hasil:
#   registry.acme.co.id/eduzone/eduzone-app:v1.2.6
#   registry.acme.co.id/eduzone/eduzone-app:latest
```

---

## 9. Sesudah Push: Deploy ke Docker Swarm

Setelah push BERHASIL, script akan menampilkan summary seperti ini (copy-paste ke server Swarm):

```
  ─── Deploy ke Production (Docker Swarm) ───

  Image (dipush)      : docker.io/maikel/eduzone-app:v1.2.6
  Image :latest alias : docker.io/maikel/eduzone-app:latest

  1) (opsional) Git tag version - biar mudah rollback:
     git tag -a v1.2.6 -m "Release v1.2.6"
     git push origin v1.2.6

  2) Di server Swarm:
     export DOCKER_IMAGE=docker.io/maikel/eduzone-app:v1.2.6
     docker stack deploy -c docker-compose.swarm.yml eduzone

  3) Verifikasi:
     docker stack services eduzone
     docker stack ps eduzone --no-trunc

  ⚠️  Jangan lupa DB migrate di production (sekali per release):
     docker exec -it $(docker ps -q -f name=eduzone_eduzone_app) php artisan migrate --force
```

> **Step-by-step deploy Swarm lebih detail:** lihat [DEPLOY-SWARM.md](file:///c:/laragon/www/eduzone/docs/DEPLOY-SWARM.md)

---

## 10. Troubleshooting Umum

### ❌ 10.1 "BELUM LOGIN ke registry target"
Script mendeteksi `~/.docker/config.json` tidak ada credential untuk registry target. Solusi:
```powershell
# Docker Hub:
docker login
# GHCR:
docker login ghcr.io -u <USER> -p <CLASSIC_PAT>
# Private:
docker login registry.acme.id -u <USER> -p <PASS>
```
Kemudian pilih **Y** pada prompt konfirmasi di script.

---

### ❌ 10.2 "ext-grpc build sangat LAMA (1 jam+) — apakah hang?"
**TIDAK HANG.** Stage `production` di [Dockerfile](file:///c:/laragon/www/eduzone/Dockerfile#L57-L60) menjalankan:
```
pecl install redis grpc protobuf
```
Extension `grpc` ditulis dalam C++ dan perlu kompilasi full dari source di Alpine. Waktu normal:
- Laptop Core i7 / SSD NVMe: ~20–30 menit
- PC Desktop RAM 16GB: ~30–45 menit
- CI runner shared (GitHub Actions free-tier): bisa 45–75 menit

**Kalau mau lebih cepat:** Push ke registry tapi set **prebuilt image** kamu sebagai cache, atau build di server dengan banyak core (gunakan `--cache-from` di `docker build`).

---

### ❌ 10.3 "Gagal push: requested access to the resource is denied"
Kemungkinan:
1. Username salah → cek di [hub.docker.com](https://hub.docker.com/)
2. Access Token / PAT tidak punya scope `write:packages` → buat token baru
3. Image name case-sensitif (Docker Hub: `Maikel` beda dengan `maikel`) → pakai lowercase

---

### ❌ 10.4 "Build sukses tapi deploy error: DB migration FAIL"
Pengecekan:
1. Pastikan **2 database**: `eduzone` DAN `eduzone_absensi` keduanya ada di Postgres production. Baca [DEPLOY-SWARM.md §Langkah 3](file:///c:/laragon/www/eduzone/docs/DEPLOY-SWARM.md#L64-L72).
2. Pastikan `.env` production punya value:
   ```
   DB_ABSENSI_HOST=postgres
   DB_ABSENSI_DATABASE=eduzone_absensi
   DB_ABSENSI_USERNAME=laravel
   DB_ABSENSI_PASSWORD=<PASSWORD_SAMA_DENGAN_DB_UTAMA>
   ```
3. Jalankan manual di dalam container app:
   ```bash
   docker exec -it $(docker ps -q -f name=eduzone_eduzone_app) php artisan migrate --force
   ```

---

### ❌ 10.5 "Composer install: 'zlib' not found" (saat docker build)
Periksa `apk add zlib-dev` — ini SUDAH ada di [Dockerfile line 57](file:///c:/laragon/www/eduzone/Dockerfile#L57). Kalau error:
```powershell
.\build.ps1 -Clean -Release -DockerHub maikel -Tag v1.x.x -Force
```
Flag `-Clean` menghapus `vendor/` dan build tanpa intermediate cache yang corrupt.

---

## 11. Daftar Parameter Lengkap

Semua parameter bisa dikombinasikan. Jalankan `Get-Help .\build.ps1 -Full` untuk docs resmi inline.

| Parameter | Tipe | Default | Fungsi |
|---|---|---|---|
| `-Mode` | `Development` \| `Production` | `Development` | Build mode. Production pakai `--no-dev`, optimize autoloader, & cache Laravel. |
| `-SkipComposer` | `[switch]` | off | Skip `composer install` step (berguna kalau hanya ubah asset frontend). |
| `-SkipNpm` | `[switch]` | off | Skip `npm install` + `npm run build` (cuma update backend). |
| `-SkipOptimize` | `[switch]` | off | Skip `artisan optimize / optimize:clear`. |
| `-Migrate` | `[switch]` | off | Jalankan `php artisan migrate` setelah build (pakai DB dari `.env`). |
| `-Seed` | `[switch]` | off | Jalankan `db:seed` sesudah migrate (harus bareng `-Migrate` atau `-Fresh`). |
| `-Fresh` | `[switch]` | off | `migrate:fresh` (DROP SEMUA TABEL, DESTRUKTIF. Perlu konfirmasi interaktif). |
| `-Clean` | `[switch]` | off | Hapus `vendor/`, `node_modules/`, `public/build/`, `bootstrap/cache/*.php` SEBELUM build (reproducible build). |
| `-DockerBuild` | `[switch]` | off | Build Docker image sesuai mode stage (`development` / `production`). |
| `-DockerUp` | `[switch]` | off | Jalankan `docker compose up -d` sesudah build. |
| `-DockerRecreate` | `[switch]` | off | `docker compose up -d --force-recreate --build`. |
| `-Tag` | `string` | `git SHA` / prompt | Tag image versi. Wajib atau prompt kalo `-Push` aktif. |
| `-Registry` | `string` | (empty) | Prefix registry: `ghcr.io/acme`, `registry.acme.id/eduzone`, `docker.io/maikel`. |
| `-DockerHub` | `string` | (empty) | **Shortcut Docker Hub**. Isi username saja, auto-set `-Registry docker.io/<user>`. |
| `-Push` | `[switch]` | off | Push image ke registry (butuh `-DockerHub` atau `-Registry`). |
| `-Release` | `[switch]` | off | **Shortcut ONE COMMAND**: auto set Mode=Production + DockerBuild=True + Push=True. |
| `-Bump` | `Major` \| `Minor` \| `Patch` \| `None` | `None` (prompt) | Auto increment tag versi dari git tag terakhir. Non-interaktif. |
| `-Force` | `[switch]` | off | Skip SEMUA konfirmasi interaktif. HANYA untuk CI/CD. |
| `-TimeoutSeconds` | `int` | `1800` (30 menit) | Timeout per command (naikkan ke 3600–7200 di CI runner lambat). |

---

## 💡 Tips Akhir: Rekomendasi Workflow Rutin

### Setiap Hari (Development)
```powershell
.\build.ps1 -Mode Development -Migrate
```

### Release Minor (Setiap Akut Sprint / Ada Fitur Baru)
```powershell
.\build.ps1 -Release -DockerHub maikel -Bump Minor
# ... tunggu build ...
# Git tag hasilnya:
git tag -a vX.Y.0 -m "Release vX.Y.0 - fitur <nama>"
git push origin vX.Y.0
```

### Hotfix Production (Bug Mendesak)
```powershell
.\build.ps1 -Release -DockerHub maikel -Bump Patch -Force
# Langsung deploy ke Swarm
# Jangan lupa php artisan migrate --force di container!
```
