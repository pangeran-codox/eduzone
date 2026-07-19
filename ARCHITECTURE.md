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

## 5. Rencana Layanan High-Concurrency (Go Fiber)

Beberapa endpoint yang butuh concurrency tinggi (contoh yang sudah dibahas: bulk attendance) direncanakan dilayani microservice terpisah berbasis **Go Fiber**, bukan langsung lewat Laravel. Laravel tetap menjadi aplikasi inti (core app, admin, CRUD reguler); Go menangani endpoint yang butuh throughput tinggi. Kedua service ini akan berkomunikasi dengan Rust encryption engine lewat gRPC.

> Catatan: berdasarkan eksplorasi repo saat ini, service Go belum terlihat di dalam zip ini — kemungkinan besar hidup di repo/folder terpisah.

---

## 6. Struktur Docker & Deployment

**Prinsip utama:** source code Laravel dan konfigurasi Docker disimpan **terpisah**:

```
C:\laragon\www\eduzone\          ← source code Laravel (repo ini)
C:\opt\docker\eduzone\           ← konfigurasi Docker (Dockerfile, compose)
├── app\        → Dockerfile (multi-stage) + docker-compose.yml (service: eduzone_app)
├── nginx\      → docker-compose.yml (service: eduzone_nginx, port 8083)
├── queue\      → docker-compose.yml (service: eduzone_queue, jalankan Horizon)
├── scheduler\  → docker-compose.yml (service: eduzone_scheduler, cron loop)
└── vite\       → docker-compose.yml (service: eduzone_vite, port 5174, dev only)
```

Folder `docker/` **di dalam** repo Laravel ini (`docker/nginx/default.conf`, `docker/php/*.ini`) berisi config yang di-*copy* ke image saat build — bukan compose file itu sendiri.

**Dockerfile multi-stage:**
```
Stage 1: node-builder    → build asset Vite (npm run build)
Stage 2: base            → PHP 8.3-FPM Alpine + extensions
Stage 3: development     → composer with dev deps + php-dev.ini
Stage 4: production      → composer --no-dev, cache:artisan, optimized
```
Extensions: `pdo_pgsql`, `gd`, `zip`, `mbstring`, `bcmath`, `opcache`, `intl`, `pcntl`, `redis`.

**Image di Docker Hub:** `iswant/eduzone-app:latest`, `iswant/eduzone-queue:latest`.

**Implikasi penting:** perubahan file di dalam container (`docker exec -it eduzone_app sh` lalu edit langsung) bersifat sementara jika volume tidak di-mount ke source — harus dicommit ke source code dan (untuk perubahan Dockerfile) rebuild image dengan `--no-cache`. Pola ini sama seperti yang berlaku di project Lab Management.

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

- Belum ada Dockerfile/docker-compose.yml di dalam zip yang diunggah — hanya config yang di-copy ke image (nginx conf, php ini). Compose file sesungguhnya hidup di `C:\opt\docker\eduzone\` di luar repo ini.
- Service Go Fiber dan Rust encryption engine tidak ada di repo ini — didokumentasikan berdasarkan konteks project sebelumnya, perlu diverifikasi lokasi repo-nya.
- Baru 6 controller yang benar-benar ada; sebagian besar modul di PRD baru sebatas schema database. **Ini disengaja** — EduZone adalah rebuild dari sistem sekolah single-tenant sebelumnya, dan skema database dirancang menyeluruh di awal sebelum controller/UI per modul mulai dibangun bertahap.
- Schema `eduzone_absensi` sudah siap (lihat bagian 2), tapi migration Laravel-nya, konfigurasi koneksi database kedua di `config/database.php`, dan controller/route untuk modul Absensi belum digarap di repo ini — jadi urutan kerja modul Absensi kemungkinan besar: setup koneksi DB kedua → migration dari schema ini → model dengan `$connection` eksplisit → job sinkronisasi → controller/route/view.
