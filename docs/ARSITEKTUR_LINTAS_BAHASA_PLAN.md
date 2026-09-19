# 🏗️ EduZone — Perencanaan Arsitektur Lintas Bahasa (Multi-Service)
## Tanggal: 6 September 2026 (Revisi bagian Encryption Service: 9 September 2026)
## Status: DRAFT — Perlu Review Tim
## Audience: Semua Tim (Backend PHP, Backend Go/Rust/Node, Frontend, DevOps, QA)

> **📝 Catatan revisi 9 Sep 2026:** Bagian Encryption Service (Section 2.2,
> 5, dan 8.2) diperbaiki berdasarkan review langsung dari tim Rust yang
> membangun `encryption-engine`, supaya dokumen ini mencerminkan yang
> BENERAN sudah terbangun & teruji — bukan rencana yang belum
> diimplementasikan. Section lain (Node.js, Absensi Gateway, dll) TIDAK
> direview/diubah di revisi ini.

---

## ⚠️ PENGUMUMAN PENTING SEBELUM MEMBACA

> **TIDAK SEMUA FITUR BUTUH NODE.JS.**
>
> Dokumen ini adalah **peta rencana jangka panjang**. Peraturan dasar yang WAJIB diingat seluruh anggota tim:
>
> 1. **JANGAN menambahkan Node.js jika masalahnya hanya bisa diselesaikan dengan Reverb PHP / queue job biasa.**
> 2. **JANGAN pernah menyentuh domain absensi (pipeline agregasi attendance_events → daily) — sudah 100% dikelola Go absensi-gateway.**
> 3. **JANGAN pernah membuat fitur enkripsi field sensitif sendiri — gunakan Encryption Service (gRPC Rust) yang sudah ada.**
>
> 3 aturan di atas = **menghemat ribuan jam kerja bugfix**. Patuhi.

---

## 📑 Daftar Isi

1. [Latar Belakang & Alasan Perubahan](#1-latar-belakang--alasan-perubahan)
2. [Survey Stack SAAT INI (September 2026) — 3 Service Berjalan](#2-survey-stack-saat-ini-september-2026--3-service-berjalan)
3. [Evaluasi 3 Opsi Arsitektur Realtime & Cross-Language](#3-evaluasi-3-opsi-arsitektur-realtime--cross-language)
4. [Keputusan Akhir — Arsitektur HIBRIDA 4 Service Specialist](#4-keputusan-akhir--arsitektur-hibrida-4-service-specialist)
5. [Pembagian Tugas JELAS per Service (TANPA OVERLAP!)](#5-pembagian-tugas-jelas-per-service-tanpa-overlap)
6. [Daftar Fitur Target — Kapan Pakai Service Mana?](#6-daftar-fitur-target--kapan-pakai-service-mana)
7. [Diagram Alir Data — Seluruh Interaksi Antar Service](#7-diagram-alir-data--seluruh-interaksi-antar-service)
8. [Keamanan Antar Service (Redis, gRPC, Socket.IO, HTTP)](#8-keamanan-antar-service-redis-grpc-socketio-http)
9. [Deployment & Docker Swarm — Scaling Horizontal](#9-deployment--docker-swarm--scaling-horizontal)
10. [Roadmap Implementasi Bertahap (Urutan Prioritas)](#10-roadmap-implementasi-bertahap-urutan-prioritas)
11. [Kriteria Kapan MULAI dan Kapan TIDAK MEMBUAT Node.js Service](#11-kriteria-kapan-mulai-dan-kapan-tidak-membuat-nodejs-service)
12. [Lampiran — Referensi File Kode Penting](#12-lampiran--referensi-file-kode-penting)

---

## 1. Latar Belakang & Alasan Perubahan

Project EduZone dimulai dengan monolit PHP Laravel. Seiring waktu, karena **spesialisasi bahasa untuk workload tertentu**, arsitektur berkembang menjadi **multi-service**:

| Tanggal | Service Ditambahkan | Alasan |
|---|---|---|
| Fase 1 (Awal) | **PHP Laravel Monolit** | Productivity tinggi untuk CRUD, form validation, RBAC, Blade UI. |
| Fase 2 | **Rust Encryption Service** (eksternal) | CPU-bound field sensitif enkripsi 10.000+ record/menit (target awal — sejak load test nyata, tercapai jauh di atas ini, lihat Section 5). PHP terlalu lambat & tidak cocok untuk long-running gRPC server memory-stable. |
| Fase 3 | **Go Absensi Gateway** (eksternal) | Ingest event check-in 500+ device bersamaan dengan latensi rendah. Goroutine + Go channel = 10x lebih cepat + memory 100MB stabil dibanding PHP queue worker. Laravel **hanya konsumen** hasil agregasi akhir. |
| **Fase 4 (sekarang, September 2026)** | **Node.js Realtime Service** ⭐ (TUGAS BARU DOKUMEN INI) | **Gap 20% yang tidak tertutup Go+PHP:** Interactive 2-way WebSocket (Socket.IO lebih matang dari Reverb PHP untuk Presence/Ack/Room), dan media processing / dokumen batch (Puppeteer PDF, Sharp gambar, ExcelJS streaming) — tidak ada library PHP setara. |

**Tujuan penambahan Node.js (Fase 4): BUKAN menggantikan PHP/Go yang ada. TETAPI MENAMBAH KEMAMPUAN BARU YANG TIDAK BISA DILAKUKAN OLEH SERVICE YANG SUDAH ADA SECARA EFISIEN.**

---

## 2. Survey Stack SAAT INI (September 2026) — 3 Service Berjalan

### 2.1 Komponen Shared Infrastructure (SUDAH ADA)

| Komponen | Alamat Jaringan (Docker Network `network`) | Pemegang Data | Penting! |
|---|---|---|---|
| **PostgreSQL (DB Utama)** | `postgres:5432` / multiple DB (`eduzone_tenant`, `eduzone_superadmin`, `eduzone_absensi`) | Tabel users, students, teachers, attendance_daily, attendance_events, student_attendance, exams, dll. | Semua service boleh **HANYA SELECT** kecuali PHP (DML penuh). Node.js & Go hanya write tabel khusus miliknya (dokumentasikan per-tabel). |
| **Redis** | `redis:6379` DB0 default | Horizon Queue (PHP), Reverb scaling pub-sub, Cache, Session. | Shared message bus. Akan ditambah: BullMQ Node.js Queue + Redis PubSub lintas service. |
| **MinIO / S3 Compatible** (opsional) | `minio:9000` | File upload foto, dokumen PDF export, asset. | Node.js & PHP sama-sama punya akses read/write. |
| **Docker Network `network`** | External: true (dibuat oleh compose infra central) | Jaringan privat antar service — **TIDAK BOLEH** expose port internal ke publik. | Semua inter-service communication harus melewati network ini. **Public access hanya via Nginx port 80/443.** |

### 2.2 Service 1 — Rust Encryption Service

| Atribut | Nilai |
|---|---|
| **Bahasa** | Rust (edition 2021), dibangun dengan `rust:1.85-slim`. **Tidak ada komponen Go pada service ini** — Go dipakai secara terpisah untuk Absensi Gateway (lihat 2.3). |
| **Protokol** | **gRPC dengan TLS satu arah (server diverifikasi via sertifikat) + token** (header `x-api-key`, dicocokkan dengan `AUTH_TOKEN` di service). **BUKAN mTLS** — client (Laravel) tidak punya dan tidak butuh sertifikatnya sendiri, cukup trust `server.crt` milik server dan mengirim token yang valid di tiap RPC (termasuk `HealthCheck`). Tidak ada HTTP plain. |
| **Port (network privat)** | TCP 50051 |
| **Expose Publik** | ❌ TIDAK BOLEH. Hanya klien dengan token valid yang boleh konek (saat ini: Laravel). |
| **Sertifikat** | Cuma `server.crt` (**publik**, self-signed) yang dimount ke container klien (mis. Laravel) dari `services/encryption-engine/certs/`, read-only (`:ro`). **`server.key` (PRIVAT) TIDAK PERNAH dimount ke container manapun selain `encryption`/nginx load balancer-nya sendiri** — kalau sampai ter-mount ke container lain, itu kebocoran kunci server yang serius (siapa pun yang akses container itu bisa impersonate server). |
| **Akses dari Laravel** | `App\Contracts\EncryptionClientInterface` — Singleton pattern, auto reconnect. **JANGAN buat client baru tiap request.** |
| **Tugas (HANYA INI, JANGAN TAMBAH YANG LAIN!)** | ✓ `Encrypt()` / `Decrypt()` single field<br>✓ `BatchEncrypt()` / `BatchDecrypt()` untuk banyak field sekaligus dalam 1 RPC call (default limit 200 item/batch, bisa diubah lewat `MAX_BATCH_ITEMS`)<br>✓ `HealthCheck()` untuk monitoring DevOps (sudah terhubung ke Docker `HEALTHCHECK`). |
| **Pola Error** | **Belum final — masih perlu didesain bareng tim Rust, jangan diimplementasikan dari baris ini langsung.** Pertanyaan terbuka yang perlu dijawab dulu: (1) "fallback ke cache" itu cache hasil **decrypt** terakhir (ada risiko menampilkan data basi/stale ke user) atau **antrian encrypt yang ditunda** (pola queue, beda penanganan)? (2) Apakah perlu circuit breaker terpisah dari retry biasa? (3) Retry sebaiknya beda perlakuan per status gRPC — retry masuk akal untuk `UNAVAILABLE`/`DEADLINE_EXCEEDED`, tapi percuma (dan buang waktu) untuk `UNAUTHENTICATED`/`INVALID_ARGUMENT` yang tidak akan pernah berhasil walau diulang. |

### 2.3 Service 2 — Go Absensi Gateway

| Atribut | Nilai |
|---|---|
| **Bahasa** | Go 1.22+ |
| **Repo** | Repository eksternal: `eduzone/absensi-gateway` (BUKAN di folder laravel ini). |
| **Protokol Internal ke Laravel** | HTTP Client dari Go → Laravel `api/internal/sync/*` endpoints. Dilindungi **Middleware `sync.token`** header `X-Sync-Token`. Lihat: `routes/sync.php`. |
| **Komponen** | Device scanner (QR/Face/GPS) → `attendance_events` (mentah) → Agregasi internal → `attendance_daily` (rekap harian per orang). **TERMASUK deteksi status Terlambat dari `late_cutoff_time` tabel schools.** |
| **Apa yang SUDAH DINONAKTIFKAN di Laravel?** | ❌ **Jalankan command `php artisan absensi:aggregate-daily`** — SENGAJA dimatikan. Lihat komentar di `routes/console.php` baris 15-25. Command ini cuma reference, jika dijalankan AKAN MENIMPA status Terlambat yang benar dari Go menjadi "Hadir". **AWAS!** |
| **Yang TETAP dijalankan Laravel** | ✅ Scheduler tiap 10 menit → `php artisan absensi:sync-daily-to-main`. Ini tugas **men-copy** baris attendance_daily (yang diisi Go Absensi Gateway) ke tabel student_attendance di DB utama. Lihat `routes/console.php` baris 34. Command ini idempotent via updateOrCreate. |
| **Pola Kebijakan Data** | Go sebagai **Sumber Kebenaran Tunggal (Single Source of Truth)** pipeline absensi. PHP tidak pernah menulis langsung ke tabel attendance_events / attendance_daily — **hanya BACA & COPY**. |

### 2.4 Service 3 — PHP Laravel (Container: `app`, `queue`, `scheduler`, `reverb`)

Ini service tempat 90% bisnis logic berjalan. Dibagi menjadi 4 container berbagi image yang sama (`eduzone-app`) — beda command.

| Container | Command | Tugas Utama |
|---|---|---|
| `app` (PHP-FPM) | `php-fpm` listen 9000 | Handle HTTP request dari Nginx. CRUD, Auth RBAC, Blade rendering, Validation, API JSON. |
| `queue` (Horizon) | `php artisan horizon` | Proses semua queue jobs: email, generate laporan kecil, sync lambat. Monitor dashboard di `/horizon`. |
| `scheduler` | Loop `php artisan schedule:run` tiap 60 detik | Menjalankan Scheduled Command: `horizon:snapshot`, `absensi:sync-daily-to-main`. |
| `reverb` | `php artisan reverb:start --host=0.0.0.0 --port=8082` | **WebSocket Satu Arah** dari server → browser. Driver: laravel/reverb (native PHP, bukan Node). **BROADCAST_CONNECTION saat ini = `null` — BELUM diaktifkan, tinggal ganti ENV jadi `reverb` untuk start.** Cocok untuk notifikasi, pengumuman, broadcast event sederhana. |

#### Stack Frontend yang TERTANAM di Laravel:
- **Blade Template** + **Tailwind CSS** (build via Vite).
- **Alpine.js** untuk interaksi SPA ringkas tanpa page reload.
- **Laravel Echo + PusherJS** (`resources/js/echo.js`) = **cara berkomunikasi frontend dengan Reverb.** SUDAH ADA DAN TERSETUP. Tinggal pakai `window.Echo.channel('school.X').listen(EventName, e => {...})`.

---

## 3. Evaluasi 3 Opsi Arsitektur Realtime & Cross-Language

### 🅰️ OPSI 1: Tanpa Node.js — Reverb PHP Saja

| ✅ Keuntungan | ❌ Kekurangan |
|---|---|
| Zero infra baru. Tidak ada container baru, tidak ada Dockerfile baru. | PHP buruk untuk 1000+ WebSocket persistent connection. Memory grow per-connection > 2MB × 500 koneksi = 1 GB RAM (Node cuma ~5 MB total untuk jumlah sama). |
| Laravel Echo langsung connect tanpa setup auth tambahan. Pakai session yang sama dengan login user = **auto verified RBAC channel.** | Reverb tidak punya fitur Socket.IO: reconnect backoff logic, message acknowledgment (RPC), presence channel metadata, room event emit dengan ack. Tidak cocok untuk aplikasi 2-arah interaktif (chat, live quiz). |
| Deployment trivial: 1 container reverb yang sudah ada di `docker-compose.yml`. | Ecosystem PDF/Excel processing di PHP tidak sekuat Node.js (Puppeteer PDF > DOMPDF; Sharp > Intervention GD; ExcelJS Stream > PhpSpreadsheet memory-load). Batch export 1000 halaman → OOM worker. |

### 🅾️ OPSI 3: Ganti Total WebSocket ke Node.js Socket.io Semua

| ✅ Keuntungan | ❌ Kekurangan |
|---|---|
| Socket.io = raja WebSocket (beneran). 99% project dunia realtime pakai ini. | **Buang effort setup Reverb.** Container Reverb, config, echo.js yang sudah ter-testing — dibuang percuma. |
| Socket.io Redis Adapter = perfect scaling Docker Swarm multi-node tanpa sticky session. | **Refactor SEMUA Laravel Events implement `ShouldBroadcast`** — tadinya dikirim ke Reverb (langsung dari PHP), sekarang harus dispatch ke Redis Pub/Sub dulu yang didengar Node.js. Banyak regression risk. |
| | **Harus buat auth Socket.IO dari 0.** Session Laravel tidak otomatis terbaca Node.js. Perlu mekanisme JWT / Signed Token manual. Risk salah config → data sekolah A bisa nyasar ke sekolah B (bahaya multi-tenant!). |

### 🅱️ OPSI 2: HIBRIDA — Reverb (1-arah) BERDAMPINGAN Node.js Socket.IO (2-arah + Worker) ⭐

| ✅ Keuntungan | ❌ Kekurangan |
|---|---|
| **Tidak buang effort existing.** Reverb tetap jalan. Echo.js tetap dipakai untuk notifikasi biasa. Zero regression! | Butuh 2 setup WebSocket berbeda di frontend:<br>`window.Echo` (Reverb) + `window.io` (Socket.io) → tambah kompleksitas 5-10 KB bundle js. |
| **Incremental adoption.** Mulai dari fitur kecil (PDF export worker) tanpa menyentuh sistem realtime yang sudah jalan. | Anggota tim harus paham kapan harus trigger ke Reverb, kapan ke Socket.IO → butuh training / panduan ini sebagai acuan. |
| **Sesuai pola sukses yang sudah ada** (Encryption service + Go absensi gateway sebagai service external spesialis). Node.js adalah service spesialis berikutnya = konsisten pola arsitektur. | Biaya operasional tambah: 1 container baru, 1 image Docker baru, 1 set healthcheck baru. Tapi cost ini kecil dibanding manfaat yang didapat. |
| **Isolasi failure.** Node.js service crash? Reverb tetap jalan. Dashboard tidak total down = High Availability. Isolated failure = mudah debugging. | |

---

## 4. Keputusan Akhir — Arsitektur HIBRIDA 4 Service Specialist

> **✓ PILIH: OPSI 2 + Pertahankan Seluruh Go Service Existing.**

Arsitektur final = **4 service specialist**, masing-masing mengerjakan 1 hal dengan baik:

```
 ┌──────────────────────────────────────────────────────────────────┐
 │ 🔐 Shared Messaging & Storage: Postgres, Redis, MinIO            │
 └──────┬──────────────────────┬──────────────────────┬─────────────┘
        │                      │                      │
 ┌──────▼──────┐    ┌──────────▼─────────┐   ┌───────▼───────────┐
 │🟥 Rust      │    │ 🟢 Go Absensi      │   │ 🔵 PHP Laravel    │
 │ Encryption  │    │    Gateway         │   │  (4 containers:   │
 │ Service     │    │ (Pipeline Absensi) │   │  app/queue/sch/   │
 │(gRPC TLS+   │    │ (HTTP → Laravel)   │   │  reverb)          │
 │  token)     │    │                    │   │                    │
 └──────┬──────┘    └──────────┬─────────┘   └───────┬───────────┘
        │                      │                      │
        │                      │              ┌───────▼───────────┐
        └────── Redis / Postgres Shared Bus ──┤ 🟨 Node.js        │
                                              │   Realtime Serv.  │
                                              │(Socket.IO + BullMQ│
                                              │  + PDF/Img Worker)│
                                              └───────────────────┘
```

---

## 5. Pembagian Tugas JELAS per Service (TANPA OVERLAP!)

**❗ SELURUH TIM WAJIB HAFAL TABEL INI. JANGAN SAMPAI MEMINTA FITUR KE SERVICE YANG SALAH.**

| No | Tugas / Workload | Service yang Ditunjuk | Alasan — Kenapa Bukan yang Lain? |
|---|---|---|---|
| 1 | Enkripsi / Deskripsi field NISN, NIK, NoHP, Alamat detail. | 🟥 Rust Encryption Service | Rust = CPU intensive cepat. Komputasi kripto (AES-256-GCM) sub-milidetik; latency round-trip gRPC end-to-end (termasuk network) terukur **p50 6ms / p99 9.6ms** di concurrency 50 (diukur pakai `ghz`, sandbox 1 vCPU — server produksi berpotensi lebih cepat, tapi overhead network/framing gRPC tidak akan pernah hilang sampai <1ms). Sudah ada. **JANGAN enkripsi di PHP / Node!** |
| 2 | Ingest check-in device, agregasi event → harian, deteksi status "Terlambat". | 🟢 Go Absensi Gateway | Go channel = 1000 event/menit tanpa lag. SUDAH ADA. **PHP AGGREGATE DINONAKTIFKAN (lihat routes/console.php baris 25)!** |
| 3 | CRUD Siswa, Guru, Staff, Kurikulum, Kelas, Jadwal. | 🔵 PHP Laravel `app` | Eloquent ORM + BelongsToSchool scope otomatis = productivity tinggi. Form Request Validation = lebih cepat. |
| 4 | Authentication Session Login + RBAC Middleware Role. | 🔵 PHP Laravel `app` | Laravel Breeze / Session = matang. **Node & Go TIDAK BOLEH melakukan auth sendiri.** Semua auth harus verifikasi dengan token yang dikeluarkan PHP. |
| 5 | Queue Jobs Email, Notifikasi SMS kecil, Sync lambat bukan realtime. | 🔵 PHP Laravel Queue (Horizon) | Sudah ada dashboard /horizon dengan retry / failed job management. |
| 6 | **Broadcast Event 1 ARAH**: Notifikasi "Export selesai", Pengumuman Sekolah, Log Aktivitas baru, Dashboard TU "5 absen terbaru sudah tersync". | 🔵 PHP Laravel Reverb (Laravel Echo di Browser) | **SUDAH ADA echo.js!** Session otomatis = channel school.id authorization rules langsung di `routes/channels.php`. **TIDAK USAH PAKAI SOCKET.IO UNTUK INI!** |
| 7 | **Realtime INTERAKTIF 2 ARAH**: Live Chat (Guru↔Siswa/WaliMurid), Live Quiz + Ranking di kelas, Presence "Siapa Online", Whiteboard Kolaboratif, Notifikasi Typing. | 🟨 Node.js Socket.IO Server | Socket.IO punya: reconnect backoff, message acknowledgment (butuh konfirmasi penerima), presence channel metadata, room event loop optimasi. **Reverb TIDAK punya fitur ini sefleksibel Socket.io.** |
| 8 | **Batch Processing MEDIA & DOKUMEN BESAR**:<br>• Raport PDF 500 siswa (10.000 halaman) dengan CSS layout Tailwind sempurna.<br>• Export Excel rekap absensi 3 bulan (100.000 baris) — stream, memory tetap 100MB.<br>• Optimasi 200 foto siswa sekaligus (resize, WebP) saat PPDB.<br>• Cetak ID Card angkatan (300 kartu, double side PDF). | 🟨 Node.js BullMQ Worker | Puppeteer/Sharp/ExcelJS = NO 1 di ekosistem untuk hal ini. PHP library setara = DOMPDF (lemot, CSS jelek), PhpSpreadsheet (memory load semua data). |
| 9 | Webhook Payment Gateway (Midtrans / Xendit) callback pembayaran SPP masuk 100/menit. | 🟨 Node.js Express HTTP Worker (opsional, bisa juga tetap PHP) | Express = handler HTTP ringan tanpa bootstrap Laravel (~15ms vs PHP ~50ms). Tapi kalau volume <10/hari → BISA PHP saja. Tidak perlu Node. |
| 10 | **Dashboard Stat Cards** (yang dibuat hari ini: Jumlah Siswa Aktif, dll) | 🔵 PHP Laravel Query Langsung + Reverb broadcast event kalau ada perubahan | Stat cards = query COUNT sederhana ke tabel final OLTP. Tidak butuh engine agregasi apa-apa. **Sangat tidak perlu Node/Go!** Biar dekat dengan database utama. |

---

## 6. Daftar Fitur Target — Kapan Pakai Service Mana?

Berikut fitur-fitur rencana 6 bulan ke depan dan mapping ke service yang mengerjakan:

### Kuartal 3 (September - November 2026)
| Fitur | Service Pengolah | Detail Delivery |
|---|---|---|
| Export Raport Per Siswa PDF (Single Download) | 🔵 PHP Laravel (Browsershot) | Cuma 1-10 download per hari → TIDAK USAH Node.js. |
| Export Raport MASAL (1 angkatan = 300 siswa) via Dashboard TU. | 🟨 Node BullMQ (job type `pdf_raport_angkatan`) | User klik tombol. PHP kirim job BullMQ via Redis. Node.js selesai → upload MinIO → Reverb PHP broadcast event notifikasi "Export selesai ke user X". |
| Dashboard TU: "5 Absen Terbaru" (Live). | 🔵 PHP Reverb | Trigerred: Command `sync-daily-to-main` selesai → dispatch Event `NewStudentAttendanceSynced` → broadcast via Reverb channel `school.{id}.dashboard`. Echo listen di Alpine.js. **TIDAK USAH Node.js.** |
| Pengumuman Sekolah baru. | 🔵 PHP Reverb | `AnnouncementCreated` event broadcast. Sederhana. |

### Kuartal 4 (Desember 2026 - Februari 2027)
| Fitur | Service Pengolah | Detail Delivery |
|---|---|---|
| **Live Chat** — Guru BK ↔ Siswa (konsultasi online privat) | 🟨 Node Socket.IO | Room `private.chat:{userA}_{userB}`. Setiap message = emit Socket.IO dengan ack. Message disimpan Node ke Postgres tabel `chat_messages`. Offline message? PHP kirim notifikasi WA / email queue. |
| Online Presence — "12 Guru, 248 Siswa sedang online hari ini" di dashboard Kepsek. | 🟨 Node Socket.IO Presence Channels | Tanpa perlu PHP polling. Node.js socket `disconnect` event otomatis kurangi counter. |
| Export Rekap Absensi 3 Bulan (Excel) ~50k baris. | 🟨 Node BullMQ (job type `excel_rekap_absensi`) | Stream row-by-row via ExcelJS. Memory tetap stabil 100MB. |
| Optimasi Foto & Generate ID Card Siswa PPDB 2027. | 🟨 Node BullMQ (job `photo_idcard_ppdb`) | Sharp resize, lalu Puppeteer render kartu HTML → PDF zip. |

### Kuartal 1 (Maret - Mei 2027)
| Fitur | Service Pengolah | Detail Delivery |
|---|---|---|
| **Live Quiz** — Guru Mapel buat soal, 30 siswa jawab dalam 10 detik → ranking realtime di proyektor. | 🟨 Node Socket.IO | Need ack & latency <100ms. Butuh server emit "question:next" dengan countdown. Reverb PHP = tidak stabil untuk ini. |
| Payment Gateway Webhook (Midtrans SPP) — Auto update status "Lunas". | 🟨 Node Express HTTP Webhook | Jika volume < 100/hari → boleh PHP. Jika >100 → Node. Nilai tradeoff tentukan nanti. |
| Collaborative Edit Jurnal Guru (seperti Google Docs) — Wali kelas + semua mapel edit jurnal kelas bersama. | 🟨 Node Socket.IO (OT / CRDT algorithm) | Butuh transformasi operasi realtime. Go / PHP tidak cocok untuk ini. |

---

## 7. Diagram Alir Data — Seluruh Interaksi Antar Service

### 7.1 Contoh Flow: User TU Export Raport 1 Angkatan (PDF Batch)
Ilustrasi ini menunjukkan bagaimana **4 service bisa bekerja bersama dalam 1 fitur**, tanpa saling tabrak domain.

```
 Step 1: TU Klik Tombol di Browser
 🌐 Browser TU (Alpine)
   │ POST /tu/export/raport-angkatan {class_id: 10IPA}
   ▼
 🔵 PHP Laravel app (Middleware Auth: Role=tu)
   ├─ 🔍 Validasi: Apakah class_id ini milik sekolah user? (BelongsToSchool)
   ├─ 📝 INSERT ke tabel export_tasks (status: pending, user_id, type: pdf_raport)
   ├─ 📡 AMBIL SECRET NODE_SHARED_HMAC dari ENV
   ├─ 🧾 Hitung HMAC SHA256($payload, $secret) = $signature
   ├─ 📤 KIRIM Job ke Redis BullMQ Queue: "node:jobs:exports"
   │    payload = {task_id, class_id, school_id, callback_url_signed}
   │    signature = HMAC untuk dicek Node
   └─ 📨 Response JSON 202 "Menyedang diproses. Anda akan dapat notifikasi selesai."

 Step 2: Node.js Ambil Job
 🟨 Node.js BullMQ Worker (Container realtime-node)
   ├─ 👂 Pop job dari queue exports.
   ├─ 🔐 VERIFIKASI HMAC. Kalau gagal → mark failed, log security incident.
   ├─ 🗄️ Konek Postgres, query students di kelas itu (HANYA SELECT, tidak write).
   ├─ 🖨️ Loop 300 siswa:
   │   ├─ 🎨 Render HTML template Raport dengan Tailwind (seperti Blade, tapi Vue SSR / plain).
   │   ├─ 🦭 Puppeteer page.pdf() → Generate PDF 20 halaman per siswa.
   │   └─ 📦 Upload ke MinIO bucket /exports/raport/2026-09-06/10IPA/SISWA_NIK.pdf.
   ├─ 📝 Update Postgres: UPDATE export_tasks SET status='selesai', file_path='...' WHERE id=task_id.
   └─ 📤 PUBLISH ke Redis channel "php:callbacks:export_done" payload: {export_task_id}
      (TIDAK perlu panggil HTTP ke PHP — Redis sebagai callback bus lebih aman.)

 Step 3: PHP Kirim Notifikasi User
 🔵 PHP Laravel Horizon Queue Worker
   ├─ 👂 Subscribe Redis channel "php:callbacks:export_done" via queue:work redis --queue=php_callbacks.
   ├─ 📖 Dapatkan data export_tasks: user_id + file_path.
   ├─ 🔵 Broadcast via REVERB ke Channel private-user.{user_id} Event: UserExportCompleted(file_url, filename).
   └─ ✅ Opsional: Kirim Email Queue "Raport siap didownload" (Horizon Jobs).

 Step 4: Browser Terima Real-time Notifikasi
 🌐 Browser TU (Masih di halaman yang sama, TIDAK REFRESH)
   ├─ window.Echo.private('user.' + userId).listen('UserExportCompleted', e => {...})
   ├─ 🔔 Tampilkan toast pop-up "Raport 10 IPA (300 siswa) selesai! Download"
   └─ 👇 Klik toast → Download file dari MinIO signed URL (5 menit expire).
```

### 7.2 Contoh Flow: Live Chat Guru BK ↔ Siswa
```
  🔐 Auth Socket: User Login di PHP → attach cookie SIGNED_JWT_SOCKETIO (issued PHP dengan APP_KEY)

  🌐 Browser Siswa connect Socket.IO
  │ io('wss://eduzone.app/socket.io', { auth: { token: cookie.socket_io_jwt } })
  ▼
🟨 Node.js Socket Server (Middleware Auth)
  ├─ 🔐 JWT.verify(token, APP_KEY) → dapatkan payload: {user_id, school_id, role, username}
  ├─ ❌ Kalau token invalid → disconnect.
  ├─ ✅ Lanjut: socket.data.user = payload.
  ├─ 🚪 Join ROOM: socket.join(`school:${school_id}`)
  ├─ 🚪 Join ROOM: socket.join(`user:${user_id}`)
  └─ 🚪 Join PRESENCE CHANNEL: `school:${school_id}:presence` dengan metadata role/username.

  👩‍🏫 Guru BK kirim pesan?
  └─ emit 'chat.message' with { to: 12345, text: "Ada yang bisa dibantu?" }
     └─ Node.js:
        ├─ VERIFIKASI: HANYA boleh kirim ke user dalam SEKOLAH YANG SAMA.
        │    (jika target user_id school_id != socket.data.school_id → BLOCK!)
        ├─ 💾 Simpan ke tabel chat_messages (Node INSERT ke Postgres).
        ├─ ✅ ACK balik ke pengirim "message_id: X sudah diterima server".
        └─ 📤 io.to(`user:${target_user_id}`).emit('chat.message.new', messageObject);
              → Browser Siswa terima dalam < 100ms, bubble chat muncul.
```

---

## 8. Keamanan Antar Service (Redis, gRPC, Socket.IO, HTTP)

### 8.1 Aturan Multi-Tenant WAJIB di Semua Service:
**🚩 JANGAN SAMPAI SEKOLAH A BISA BACA DATA SEKOLAH B!**

1. **Semua SELECT query (baik itu PHP / Go / Node.js)** — WAJIB menyertakan `WHERE school_id = X` dimana X diambil dari context user / context job trigger. Di PHP ini otomatis via `BelongsToSchool`, di Go & Node.js **HARUS manual filter X**.
2. **Socket.IO Rooms:** User HANYA di-join ke room `school:${own_school_id}`. Tidak boleh join room sekolah lain. Server emit event hanya ke room school_id terkait.
3. **Redis Queue Job:** ID sekolah WAJIB ada di top-level payload job. BullMQ worker sebelum memproses: pastikan `school_id` valid (ada di tabel schools), tidak NULL, dan semua query pakai itu.
4. **Tabel Export Tasks:** Setiap baris punya kolom `school_id`. Sebelum download, PHP middleware cek `export_task.school_id === auth()->user()->school_id`.

### 8.2 Setiap Jalur Komunikasi + Cara Aman:
| Jalur | Source → Dest | Mekanisme Aman | Jangan Lakukan Ini ❌ |
|---|---|---|---|
| **Enkripsi Field** | PHP → Rust Encryption Service | gRPC dengan TLS satu arah (server diverifikasi via `server.crt`, di-mount `:ro` ke container Laravel) **+ token** (header `x-api-key`, dicocokkan `AUTH_TOKEN` di service) di setiap request. **Bukan mTLS** — Laravel tidak punya/butuh sertifikat client sendiri. | ❌ Kirim data plain tanpa enkripsi ke gRPC port. ❌ Gunakan plain HTTP port. ❌ Mount `server.key` (privat) ke container manapun selain service enkripsi itu sendiri. |
| **Reference Data People/School/Schedule** | Go Absensi → PHP Sync | HTTP endpoint `api/internal/sync/*` dengan header `X-Sync-Token`. Middleware `VerifySyncToken` validasi token sama ENV value `SYNC_TOKEN_SECRET`. | ❌ Expose endpoint sync.php ke publik internet. ❌ Gunakan token hardcode di kode. |
| **Job Export / PDF / Excel** | PHP → Node | Redis Queue BullMQ. Setiap job payload top-level ada field `hmac_sha256` (signatur rahasia bersama `NODE_SHARED_HMAC`). Worker Node.js compute ulang HMAC(payload body) → bandingkan. TIDAK SAMA → REJECT JOB. | ❌ Percaya saja job dari Redis tanpa signature check! Bisa ada malicious container di network inject job. |
| **Callback Selesai Job** | Node → PHP | **TIDAK PAKAI HTTP!** Pake Redis channel `php:callbacks:*` yang didengar PHP Horizon Worker Redis queue. No need expose internal endpoint PHP. | ❌ Node call `POST /api/internal/export-callback` ke PHP — tambah surface attack. |
| **Realtime Notifikasi 1 arah** | PHP → Browser | Reverb PHP + Laravel Echo. **Authorization rules per channel di `routes/channels.php`.** Contoh: `Broadcast::channel('school.{id}', fn(User $u, $id) => $u->school_id===$id);` | ❌ Tanpa channel auth = semua orang bisa listen channel manapun. |
| **Interactive Socket 2 arah** | Browser ↔ Node Socket.IO | **Signed JWT issued PHP saat login.** Diverifikasi dengan APP_KEY yang sama di Node. Join room hanya berdasarkan payload yang TIDAK BISA dimodifikasi (karena JWT signature). Server EMIT HANYA ke room milik user yang SCHOOL_ID-nya sama. | ❌ Percaya `user_id` yang dikirim dari query param / client send bebas → 100% kena hack cross-school. |
| **File Download (Raport, IDCard dll)** | Browser → MinIO | **Signed URL expire 5 menit** (dibuat PHP menggunakan MinIO SDK). User download file langsung dari object storage, tidak melewati PHP backend = hemat bandwith + tidak bikin FPM worker busy. | ❌ Proxy download lewat PHP `return response()->download()` untuk file >50MB. OOM risk. |

---

## 9. Deployment & Docker Swarm — Scaling Horizontal

### 9.1 Local Development (docker-compose.yml)
Service `realtime-node` yang baru — di-local 1 replika saja, debuggable.

```yaml
# ===== TAMBAHKAN KE docker-compose.yml (SETELAH service reverb) =====
  realtime-node:
    build:
      context: ./services/realtime-node
      dockerfile: Dockerfile
      target: development
    image: eduzone-realtime-node:dev
    container_name: eduzone_realtime_node
    working_dir: /app
    command: sh -c "npm install && npm run dev"
    volumes:
      - ./services/realtime-node:/app
      - /app/node_modules
      - C:/opt/docker/projects/services/encryption-engine/certs/server.crt:/etc/ssl/certs/ca-bundle-eduzone.crt:ro
    environment:
      NODE_ENV: development
      APP_KEY: ${APP_KEY}                             # Sama dengan Laravel! (Verif JWT Socket.IO)
      NODE_SHARED_HMAC: ${NODE_SHARED_HMAC}          # Secret untuk HMAC sign Redis job (BARU!)
      REDIS_URL: redis://redis:6379                  # Sama dengan Reverb/Horizon DB0
      POSTGRES_CONNECTION_STRING: pgsql://eduzone:${DB_PASSWORD}@postgres:5432/eduzone_tenant
      MINIO_ENDPOINT: http://minio:9000
      MINIO_ACCESS_KEY: ${MINIO_ACCESS_KEY}
      MINIO_SECRET_KEY: ${MINIO_SECRET_KEY}
      PORT: 6001
    networks:
      - network
    depends_on:
      - redis
      - app
    # ❗ JANGAN EXPOSE PORT 6001 KE LUAR NETWORK!
    # Public access Socket.IO HANYA boleh melalui Nginx location /socket.io.
```

### 9.2 Nginx Config Update (docker/nginx/default.conf & default.swarm.conf)
Tambahkan location baru **DI ATAS** location `/` yang proxy ke PHP-FPM:

```nginx
# ===== Location Socket.IO Node.js (Penting!) =====
location /socket.io/ {
    proxy_pass http://realtime-node:6001;
    proxy_http_version 1.1;

    # WebSocket upgrade headers (WAJIB untuk Socket.IO handshake!):
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";

    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    # Long timeout untuk persistent socket:
    proxy_read_timeout 86400;
    proxy_send_timeout 86400;
}

# Location Reverb PHP (pastikan sudah ada, harus berdampingan dengan Socket.IO)
location /apps/reverb/ {
    proxy_pass http://reverb:8082/apps/reverb/;
    # ... (sama upgrade header, setting default yang sudah ada)
}
```

### 9.3 Production Swarm (docker-compose.swarm.yml)
Key highlights untuk horizontal scaling:

```yaml
  realtime-node:
    image: registry.eduzone.app/eduzone-realtime-node:${TAG}
    # 🚩 HORIZONTAL SCALE! Socket.IO dengan Redis Adapter:
    #    - User A connect ke Node replica #1 emit event.
    #    - User B connect ke Node replica #2 dapat eventnya OTOMATIS.
    #    - (Karena kedua Node subscribe Redis adapter channel yang sama).
    #    - TIDAK PERLU sticky session / IP hash load balancer. ❤️
    deploy:
      replicas: 3
      resources:
        limits: { cpus: '2.0', memory: 512M }   # Node.js hemat memory!
        reservations: { cpus: '0.25', memory: 128M }
      update_config: { parallelism: 1, delay: 10s, order: start-first }
    secrets:
      - source: app_key-latest
        target: /run/secrets/APP_KEY
      - source: node_shared_hmac-latest
        target: /run/secrets/NODE_SHARED_HMAC
    # ... environment baca dari /run/secrets via fs di entrypoint script.
```

**Health check untuk semua 4 service (Swarm):**
- `app` → `php artisan health:check`
- `queue` (Horizon) → horizon status command
- `reverb` → HTTP GET /apps/reverb/health (built-in Reverb)
- `realtime-node` → Node Express route GET `/healthz` (port 6001 internal, cek Redis/Postgres/MINIO reachable)

---

## 10. Roadmap Implementasi Bertahap (Urutan Prioritas)

### 📅 MINGGU 1 (Target: 14 September 2026) — Setup Dasar Container Node.js
1. **Backend DevOps**:
   - Buat folder `services/realtime-node/`.
   - Dockerfile: `node:20-alpine` dengan multi-stage (builder + production lean image).
   - package.json: `socket.io`, `@socket.io/redis-adapter`, `bullmq`, `ioredis`, `jsonwebtoken`, `express`, `pg` (postgres), `minio`, `helmet`.
   - Tambahkan service `realtime-node` ke `docker-compose.yml` lokal.
   - Update `docker/nginx/default.conf` tambah location `/socket.io/`.
2. **Backend PHP**:
   - Tambah ENV variable baru: `NODE_SHARED_HMAC` (random 64 char hex) → di `.env.example`.
   - Tambah `config/node-realtime.php`: konfigurasi connection string, shared HMAC key.
   - Tambah helper: `app/Helpers/node_realtime_helper.php` → fungsi `node_push_job(string $queue, array $payload): bool` (Redis LPUSH dengan HMAC).
   - Buat command `php artisan node:test-ping`: kirim job hello ke queue → Node terima → log ke terminal.
3. **Acceptence Kriteria Minggu 1**:
   > `docker compose up -d realtime-node` → container jalan 0 error.<br>
   > Jalankan artisan test ping → di log Node (`docker logs eduzone_realtime_node`) ada pesan "Hello job diterima!".<br>
   > Browser kunjungi `https://eduzone.test/socket.io/?EIO=4&transport=polling` → response JSON Socket.IO handshake (tidak 404 / 502).

### 📅 MINGGU 2 (21 September 2026) — PoC Full Feature: EXPORT RAPORT MASAL
1. Database Migration: `export_tasks` table.
2. Backend PHP: Tambahkan route POST `/tu/export/raport-angkatan` (hanya role TU/kepsek). Kirim job ke Node via helper.
3. Node BullMQ Worker: Implement `pdf_raport_angkatan` job type. Test generate 300 PDF → upload MinIO → callback Redis pub-sub.
4. PHP Horizon: Queue listener `php_callbacks` + Job `HandleExportCompleted` + broadcast Reverb event `UserExportCompleted`.
5. Frontend: Tombol "Export Raport Angkatan" di dashboard TU. Alpine.js listen Echo channel user. Toast notifikasi.
6. **Acceptence Kriteria Minggu 2:**
   > Klik Export → halaman tidak refresh. ~5 menit kemudian muncul toast → klik toast download ZIP PDF 300 raport. Isi file di MinIO tidak corrupt. Bisa dibuka PDF reader. Nama kelas, nama siswa, school_id BENAR (tidak nyasar sekolah lain!).

### 📅 MINGGU 3-4 (Oktober 2026) — Live Chat & Presence
1. PHP: Middleware `AppendSocketIoJwtCookie` saat login sukses.
2. Node.js Socket.IO: Auth middleware JWT verify APP_KEY. Join rooms per-school + per-user. Presence channel.
3. Database Migration: `chat_messages`, `chat_conversations`.
4. Frontend: Chat widget sidebar kanan. Alpine.js listen socket events.
5. **Acceptence:** Buka 2 browser: login BK dan login Siswa (pakai URL signed auto login dari seed-test). Kirim chat dari BK → muncul di Siswa < 100ms. Balas → BK terima. Close browser → buka 1 jam kemudian → auto reconnect Socket.IO pesan terbaru sync.

### 📅 KUARTAL BERIKUTNYA (Lihat Bab 6 Daftar Fitur Target)
- Live Quiz.
- Excel Export Stream.
- Photo Processing PPDB.
- Payment Gateway Webhook.

---

## 11. Kriteria Kapan MULAI dan Kapan TIDAK MEMBUAT Node.js Service

> **Rule paling penting sebelum mulai coding Node service baru:** TANYA DIRI SENDIRI PERTAMA:
>
> **"Apakah ini bisa diselesaikan dengan cukup baik menggunakan stack yang ADA (PHP Reverb + Queue / Go)?"**
>
> Kalau jawabannya **"BISA"** — **JANGAN buat fitur di Node.js.** Pindahkan kembali ke stack existing.

### ✅ Kapan WAJIB Mulai Gunakan Node.js?
1. ✅ **Butuh WebSocket 2 ARAH DENGAN ACKNOWLEDGMENT / PRESENCE / REPLAY BUFFER.** (Chat, Live Quiz, Kolaborasi)
2. ✅ **Butuh Export Dokumen > 500 HALAMAN / > 50 MB PDF EXCEL.** (Batch Raport, Buku Induk Siswa Lengkap)
3. ✅ **Butuh Image Processing Batch > 50 foto sekaligus** (PPDB, ID Card)
4. ✅ **Butuh HTML dengan CSS Modern (Tailwind grid, flex, image base64) Render ke PDF SEMPURNA.** (Gunakan Puppeteer — Chromium engine).

### ❌ Kapan TIDAK USAH Node.js / Pakai Stack Existing?
1. ❌ **Cuma butuh broadcast notifikasi 1 arah "Pengumuman baru" / "Export selesai"** — Pakai **Reverb PHP + Laravel Echo** sudah cukup!
2. ❌ **Export kecil < 10 halaman PDF** (1 raport single siswa) — Pakai **PHP Browsershot** biasa.
3. ❌ **HTTP API endpoint CRUD data sekolah** — Pakai **PHP Laravel API Resources**.
4. ❌ **Agregasi Absensi harian** — Go Absensi Gateway mengelolanya! Jangan pernah ulang.
5. ❌ **Queue Jobs Email, Notifikasi WA** — PHP Horizon sudah sempurna.

---

## 12. Lampiran — Referensi File Kode Penting

Semua path ini **relatif ke root project Laravel (`c:\laragon\www\eduzone\`)**:

| Domain / Topik | File Penting untuk Direview |
|---|---|
| **Encyrption Service Pattern** (contoh gRPC client implementasi) | `app/Contracts/EncryptionClientInterface.php` → `app/Services/EncryptionClient.php` + `proto/encryption.proto` |
| **Absensi Scheduler** | `routes/console.php` (line 15-34: catatan kenapa aggregate dimatikan, dan sync command yang tetap jalan) |
| **Artisan Command Absensi** (reference / jangan dijalankan!) | `app/Console/Commands/Absensi/AggregateAttendanceDaily.php` (DIMATIKAN) |
| **Sync Command** (yang aktif) | `app/Console/Commands/Absensi/SyncAttendanceDailyToMain.php` |
| **Route Sync Internal** (Go Absensi Gateway panggil PHP) | `routes/sync.php` + Middleware `app/Http/Middleware/VerifySyncToken.php` (register di `bootstrap/app.php`) |
| **Multi-Tenant SchoolScope + BelongsToSchool** | `app/Multitenancy/Scopes/SchoolScope.php` + `app/Multitenancy/Concerns/BelongsToSchool.php` |
| **Initialize Tenancy per HTTP Request** | `app/Http/Middleware/InitializeTenancy.php` + Finder: `app/Multitenancy/TenantFinder/AuthUserTenantFinder.php` |
| **Realtime PHP Reverb Config** | `config/reverb.php` (scaling Redis sudah ada di line 40-51) + `config/broadcasting.php` (default = null, tinggal ganti ENV) |
| **Frontend Echo (untuk Reverb)** | `resources/js/echo.js` |
| **Docker Compose Existing** (untuk tambah service node) | `docker-compose.yml` + `docker/nginx/default.conf` |
| **Dashboard per-Role Terbaru** (yang dibuat hari ini, jadi referensi) | `resources/views/tenant/{tu,kurikulum,kesiswaan,bk,toolman,siswa,kepsek,guru}/dashboard/index.blade.php` + route setup di `routes/tenant.php` |
| **Tools Debugging Multi-Tenant CLI** (dibuat hari ini, berguna saat testing service node!) | `app/Console/Commands/Tenant/MimicTenantCommand.php` (switch context sekolah di Tinker) + `app/Console/Commands/Tenant/SeedTestUserCommand.php` (generate user test 9 role + signed login URL) |
| **Eloquent Macro Debug Tenant Scope** | `app/Providers/TenantDebugServiceProvider.php` → method `explainTenantScope()` / `dumpTenantScope()` |
| **Dokumen Pendukung Lainnya** | `ARCHITECTURE.md`, `DEPLOY-SWARM.md`, `SKILL.md`, `TENANT_DASHBOARDS_DEBUG_TOOLS.md` |

---

**Status Akhir:** DRAFT V1.0 (bagian Encryption Service direvisi 9 Sep 2026 sesuai review tim Rust)
**PIC Arsitektur:** [MOHON DIISI NAMA]
**Tanggal Review Tim:** [MOHON DIISI TANGGAL RAPAT REVIEW TEKNIS]
**PIC Go Team:** [MOHON DIISI NAMA] — Verifikasi peraturan domain absensi apakah sudah benar.
**PIC Node.js:** [MOHON DIISI NAMA] — Review roadmap urutan prioritas implementasi.
**PIC PHP Team:** [MOHON DIISI NAMA] — Review mapping jobs apakah ada yang overlap.
**PIC DevOps:** [MOHON DIISI NAMA] — Review docker-compose, Nginx location, Swarm replicas, health check apakah feasible.
