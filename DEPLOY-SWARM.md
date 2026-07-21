# Deploy EduZone ke Docker Swarm

Panduan deploy EduZone ke Docker Swarm (single node atau multi node), konsisten dengan pola
yang sudah dipakai di Lab Management — kalau kamu udah pernah deploy Lab Management ke server
yang sama, sebagian langkah di sini (Swarm init, shared network, infrastructure) **cukup
sekali**, nggak perlu diulang per project.

---

## Prasyarat
1. Docker sudah terinstall di server
2. Setidaknya 1 node Docker (untuk single node)
3. Image EduZone sudah di-push ke registry (Docker Hub / GHCR) — Swarm **tidak** build image
   di node, beda dari `docker-compose.yml` lokal yang punya `build:`. Kalau belum pernah push,
   lihat bagian "Build & Push Image" di bawah dulu.

---

## Langkah 1: Inisialisasi Docker Swarm

**Skip langkah ini kalau server sudah pernah di-`docker swarm init` sebelumnya** (misal buat
deploy Lab Management). Jalankan di **manager node**:
```bash
docker swarm init
```
Verifikasi Swarm aktif:
```bash
docker node ls
```

---

## Langkah 2: Buat Overlay Network (Shared)

**Skip kalau `shared_network` sudah ada** (dicek dengan `docker network ls`). Network ini
dipakai bareng oleh infrastruktur DAN semua project (EduZone, Lab Management, dst) — persis
pola `network` (bridge) yang dipakai di lokal, versi overlay-nya buat Swarm:
```bash
docker network create --driver overlay --attachable shared_network
```

---

## Langkah 3: Deploy Infrastruktur

**Skip kalau infrastruktur (Postgres/Redis/Adminer) sudah jalan** di server ini (misal dari
deploy Lab Management sebelumnya) — EduZone numpang ke instance yang sama, bukan bikin baru.
Kalau belum pernah:

```bash
cd /opt/docker/infrastructure

cp .env.example .env  # sesuaikan kredensial

# Deploy infrastruktur sebagai stack
docker stack deploy -c docker-compose.swarm.yml infrastructure
```

Verifikasi infrastruktur berjalan:
```bash
docker stack services infrastructure
```

Pastikan database `eduzone` ada di daftar `POSTGRES_MULTIPLE_DATABASES` / script
`init-multiple-db.sh` (lihat `ARCHITECTURE.md` §6) — kalau infra ini sebelumnya cuma disetup
buat Lab Management, database `eduzone` mungkin belum ada, perlu ditambahkan manual:
```bash
docker exec -it $(docker ps -q -f name=infrastructure_postgres) psql -U laravel -c "CREATE DATABASE eduzone;"
```

---

## Langkah 4: Build & Push Image EduZone

Dari komputer development (atau CI/CD), build image production dan push ke registry:

```bash
cd C:\laragon\www\eduzone

docker build -t <REGISTRY>/eduzone:latest --target production .
docker push <REGISTRY>/eduzone:latest
```

Ganti `<REGISTRY>` dengan namespace registry kamu (mis. `namamu` untuk Docker Hub, atau
`ghcr.io/namamu` untuk GitHub Container Registry).

---

## Langkah 5: Deploy Stack EduZone

Copy folder project (atau minimal `docker-compose.swarm.yml`, `docker/nginx/default.swarm.conf`,
dan `.env`) ke server, lalu:

```bash
cd /opt/eduzone   # atau lokasi kamu taruh di server

cp .env.example .env   # isi APP_KEY, DB_PASSWORD, REVERB_*, dst — nilai PRODUCTION, bukan lokal
export DOCKER_IMAGE=<REGISTRY>/eduzone:latest

docker stack deploy -c docker-compose.swarm.yml eduzone
```

Verifikasi:
```bash
docker stack services eduzone
docker stack ps eduzone --no-trunc
```

---

## Langkah 6: Setup Reverse Proxy (Nginx Proxy Manager)

1. Buka NPM, tambah **Proxy Host** baru
2. Domain: domain EduZone kamu (mis. `eduzone.namasekolah.com` atau domain produksi)
3. Scheme: `http`
4. Forward Hostname / IP: `tasks.eduzone_nginx`  ⚠️ Khusus Swarm: pakai `tasks.<service_name>`
5. Forward Port: `80`
6. Tab **SSL** → "Request a new SSL Certificate" → centang "Force SSL" → **Save**

---

## Docker Swarm: Catatan Penting

### 1. Service Discovery di Swarm
- **Single service**: `<service_name>` (mis. `eduzone_reverb`)
- **Load balanced** (replicas > 1): `tasks.<service_name>` (mis. `tasks.eduzone_app`)

Contoh di Nginx config EduZone (`docker/nginx/default.swarm.conf`):
```nginx
fastcgi_pass tasks.eduzone_app:9000;  # Load balance ke semua replika eduzone_app
```

### 2. Kenapa `eduzone_public` (volume terpisah)
Di Swarm, `eduzone_app` dan `eduzone_nginx` adalah container yang benar-benar terpisah (beda
dari lokal yang mount source langsung) — nginx nggak otomatis lihat `public/` milik app.
Makanya `eduzone_app` meng-copy isi `public/` ke volume `eduzone_public` tiap kali start, dan
`eduzone_nginx` mount volume yang sama buat baca `index.php` & asset.

### 3. Placement Constraints
- `node.role == manager`: buat service yang butuh persistensi (DB, NPM)
- `node.role == worker`: buat service aplikasi (semua service EduZone pakai ini)
- `node.labels.<key> == <value>`: custom label buat penempatan spesifik

### 4. Persistensi Volume
Untuk multi node, `eduzone_storage` dan `eduzone_public` (driver `local`) cuma aman selama
SEMUA service EduZone di-constraint ke node worker yang SAMA. Kalau nanti nambah worker node
lain, ganti ke volume driver shared (NFS/Ceph/GlusterFS) — sama catatan seperti Lab Management.

### 5. Docker Secrets (opsional, lebih aman dari `.env`)
```bash
echo "app-key-produksi" | docker secret create eduzone_app_key -
echo "db-password-produksi" | docker secret create eduzone_db_password -
```
Lalu referensikan lewat `secrets:` di compose dan `*_FILE` env var — lihat contoh lengkap di
`DEPLOY-SWARM.md` Lab Management (pola sama persis, tinggal ganti nama secret).

---

## Perintah Swarm yang Sering Dipakai

| Perintah | Keterangan |
|---|---|
| `docker stack ls` | Lihat semua stack |
| `docker stack services eduzone` | Lihat service di stack EduZone |
| `docker stack ps eduzone` | Lihat container di stack EduZone |
| `docker stack deploy -c docker-compose.swarm.yml eduzone` | Deploy/update stack |
| `docker stack rm eduzone` | Hapus stack |
| `docker service scale eduzone_app=3` | Skala jumlah replika |
| `docker service logs eduzone_app` | Lihat log service |
| `docker node ls` | Lihat node di Swarm |

---

## Update Aplikasi di Swarm

```bash
# 1. Build image dengan tag baru
docker build -t <REGISTRY>/eduzone:v1.1 --target production .

# 2. Push ke registry
docker push <REGISTRY>/eduzone:v1.1

# 3. Update tag image (edit docker-compose.swarm.yml, atau via env var)
export DOCKER_IMAGE=<REGISTRY>/eduzone:v1.1

# 4. Deploy ulang (zero downtime — Swarm rolling update)
docker stack deploy -c docker-compose.swarm.yml eduzone
```

## Rollback

```bash
docker service ps eduzone_eduzone_app
docker service update --rollback eduzone_eduzone_app
```

---

## Troubleshooting

1. **Service tidak berjalan:**
   ```bash
   docker stack ps eduzone --no-trunc
   docker service logs eduzone_eduzone_app
   ```
2. **Volume tidak ter-mount:** pastikan volume driver sesuai dan direktori ada di semua node
   (kalau multi node dan masih pakai `driver: local`, ini penyebab paling umum).
3. **Service nggak bisa saling komunikasi:** pastikan kedua service ada di overlay network
   yang sama (`eduzone_network` untuk sesama EduZone, `shared_network` untuk akses ke Postgres/
   Redis).
4. **`could not translate host name "postgres"`:** cek `eduzone_app` sudah attach ke
   `shared_network` (bukan cuma `eduzone_network`) — connection ke Postgres/Redis lewat network
   itu.
