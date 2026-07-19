# PRD — EduZone
**Platform Manajemen Sekolah SaaS Multi-Tenant**

Status: Draft v0.1 · Disusun dari eksplorasi codebase (Juli 2026) · Perlu direview & dilengkapi oleh product owner

---

## 1. Ringkasan Produk

EduZone adalah platform SaaS multi-tenant yang memungkinkan banyak sekolah di Indonesia mengelola operasional akademik dan administrasi dalam satu sistem, dengan data terisolasi per sekolah meskipun berbagi satu database (shared database, single schema multi-tenancy).

**Konteks proyek:** EduZone adalah **rebuild/recoding ulang** dari sistem manajemen sekolah yang sudah ada sebelumnya (single-tenant, berbasis CodeIgniter), dibangun ulang dari nol sebagai sistem yang jauh lebih besar: multi-tenant, SaaS, arsitektur Laravel 11 + Filament + polyglot service (Go, Rust). Skema database (39+ tabel) sengaja dirancang lebih dulu secara menyeluruh sebelum controller/UI dibangun — jadi belum adanya controller di sebagian besar modul bukan project yang tertinggal, melainkan tahap awal yang disengaja dari rebuild ini.

**Masalah yang diselesaikan:** sekolah saat ini umumnya mengelola akademik, absensi, keuangan, dan kesiswaan secara manual atau lewat sistem terpisah-pisah (spreadsheet, aplikasi kasir, buku fisik) yang tidak terhubung satu sama lain dan sulit diaudit.

**Target pengguna:** sekolah menengah (SMA/SMK) di Indonesia — mulai dari kepala sekolah, staf tata usaha, guru, hingga siswa — dengan model bisnis SaaS berlangganan (subscription) per sekolah.

---

## 2. Tujuan Produk

- Menyatukan modul akademik, absensi, keuangan, kesiswaan, lab, dan ujian dalam satu platform.
- Memberi setiap sekolah ruang kerja yang terisolasi secara data (tenant) tanpa perlu instance/database terpisah per sekolah.
- Menyediakan kontrol terpusat bagi penyedia platform (superadmin) untuk mengelola langganan, audit, dan seluruh tenant.
- Menjadi produk komersial yang bisa dijual ke banyak sekolah dengan biaya operasional infrastruktur yang efisien.

---

## 3. Target Pengguna & Peran (Roles)

Platform melayani dua area akses yang terpisah:

### 3.1 Area Superadmin (penyedia platform)
Mengelola seluruh tenant/sekolah, subscription, dan audit lintas sekolah. Bukan bagian dari sekolah manapun.

### 3.2 Area Tenant (staf & siswa sekolah) — 9 role
| Role | Slug | Tanggung Jawab Utama |
|---|---|---|
| Kepala Sekolah | `kepsek` | Pengawasan menyeluruh operasional sekolah |
| Kurikulum | `kurikulum` | Jadwal pelajaran, konfigurasi nilai |
| Tata Usaha | `tu` | Administrasi umum, data induk |
| Guru Mapel | `guru_mapel` | Input nilai, jurnal mengajar, absensi mapel |
| Wali Kelas | `wali_kelas` | Pengelolaan kelas, absensi harian, sikap siswa |
| Kesiswaan | `kesiswaan` | Prestasi siswa, rekam jejak, pelanggaran |
| Bimbingan Konseling | `bk` | Sesi konseling siswa |
| Toolman | `toolman` | Booking & inventaris laboratorium |
| Siswa | `siswa` | Melihat nilai, jadwal, presensi diri |

Satu user bisa punya lebih dari satu role pada middleware route (mis. `role:guru_mapel,wali_kelas`).

---

## 4. Modul & Fitur (berdasarkan skema database yang sudah ada)

**Strategi rilis:** karena skala aplikasi besar (10+ modul, 39+ tabel), EduZone tidak dibangun sekaligus. Modul dibangun **satu per satu**, dimulai dari **fitur Absensi** sebagai modul pertama. Tujuannya dua hal:

1. Memperkenalkan EduZone ke sekolah lebih cepat — sekolah sudah bisa mulai memakai platform meski baru sebatas absensi, tidak perlu menunggu semua modul selesai.
2. Validasi arsitektur inti (auth, multi-tenancy, role, docker) lewat satu fitur nyata yang dipakai end-to-end, sebelum modul lain menyusul di atas fondasi yang sama.

**Keputusan arsitektur — database Absensi terpisah:** karena Absensi diperkirakan menangani beban tinggi (banyak siswa/guru absen dalam rentang waktu singkat, tiap hari sekolah), database untuk modul ini **dipisah dari database utama EduZone**. Tujuannya agar beban tinggi di Absensi tidak mengganggu development/performa fitur lain yang masih berjalan di database utama. Skema database Absensi sudah disiapkan sebagai **turunan (derivative)** dari skema database EduZone utama — jadi strukturnya selaras/konsisten, hanya databasenya yang dipisah secara fisik.

Urutan modul setelah Absensi belum ditentukan — akan diputuskan bertahap seiring modul sebelumnya selesai dan dipakai.

| Modul | Cakupan | Status (tahap rebuild saat ini) |
|---|---|---|
| **Autentikasi & Akses** | Login tenant, login superadmin terpisah, rate limiting, role middleware | ✅ Fondasi selesai |
| **Multi-tenancy & Sekolah** | Isolasi data per sekolah, subscription plan | ✅ Schema + scope aktif |
| **Absensi** | Absensi multi-metode (RFID, QR scan, face recognition, kiosk manual) untuk siswa/guru/staf, plus geofencing GPS untuk check-in guru via HP | 🚧 **Modul pertama yang digarap** — target MVP, database terpisah (`eduzone_absensi`) untuk high concurrency (lihat detail di ARCHITECTURE.md §2) |
| **Akademik** | Jurusan, kelas, jadwal, wali kelas, jurnal mengajar | 🔲 Schema selesai, menyusul setelah Absensi |
| **Penilaian** | Konfigurasi nilai, nilai siswa, bank soal ujian | 🔲 Schema selesai, menyusul setelah Absensi |
| **Kesiswaan** | Sikap siswa, prestasi, rekam jejak, konseling BK | 🔲 Schema selesai, menyusul setelah Absensi |
| **Laboratorium** | Booking lab, kunjungan lab, inventaris, laporan toolman | 🔲 Schema selesai, menyusul setelah Absensi |
| **Keuangan** | Pemasukan/pengeluaran, dana BOS, pengajuan & realisasi anggaran, audit keuangan | 🔲 Schema selesai, menyusul setelah Absensi |
| **Pengumuman** | Broadcast informasi ke sekolah | 🔲 Schema selesai, menyusul setelah Absensi |
| **Superadmin** | Dashboard, manajemen sekolah, subscription, audit log | 🟡 Dashboard dasar ada |
| **Keamanan Data Sensitif** | Enkripsi data sensitif siswa/guru/staf via gRPC service terpisah (Rust) | ✅ Service & proto ada |
| **Notifikasi Real-time** | Laravel Reverb (WebSocket) untuk notifikasi booking/absensi | 🟡 Infrastruktur siap, fitur belum dipetakan |

> **Catatan:** Ini adalah rebuild dari sistem sekolah single-tenant sebelumnya menjadi platform SaaS multi-tenant yang jauh lebih besar. Skema database (39+ tabel) dirancang lebih dulu secara menyeluruh; controller/UI dibangun bertahap menyusul, dimulai dari Absensi.

### 4.1 Cakupan MVP Absensi

Modul Absensi dirancang sebagai sistem absensi multi-metode, bukan sekadar tombol hadir/tidak:

- **Metode absensi:** RFID (tap kartu), QR scan, face recognition (kamera), kiosk manual — satu orang bisa punya beberapa metode sekaligus.
- **Geofencing guru:** check-in guru via HP divalidasi dengan kombinasi lokasi GPS (radius dari titik sekolah) dan jaringan IP sekolah yang terdaftar, untuk mencegah absen dari luar sekolah.
- **Device management:** sekolah bisa punya banyak terminal absensi (kamera, RFID reader, QR scanner) yang terdaftar dan dipantau (`last_seen_at`).
- **Anti-fraud & auditability:** setiap tap/scan dicatat sebagai raw log yang tidak bisa diubah (termasuk yang gagal dikenali), dengan deteksi anomali dasar (mis. tap ganda dalam <5 detik).
- **Data biometrik ditangani khusus:** embedding wajah disimpan terenkripsi, terpisah dari data kredensial lain — sejalan dengan pola penanganan data sensitif yang sudah dipakai di modul lain.
- **Lapisan keamanan lanjutan disiapkan tapi ditunda:** hash chaining anti-tamper, device signing (kunci kriptografi per alat), QR token yang berotasi (anti screenshot-share), dan alur approval untuk koreksi data — strukturnya sudah disiapkan di database supaya tidak perlu migrasi besar nanti, tapi **sengaja belum diaktifkan** di rilis pertama. Fokus rilis pertama: absen jalan dengan baik dan akurat dulu.

---

## 5. Arsitektur Multi-Tenancy (ringkas)

- Model: **shared database, single schema**, isolasi via kolom `school_id` + global scope (`SchoolScope`).
- Tenant ditentukan otomatis dari `school_id` milik user yang login (`AuthUserTenantFinder`).
- Superadmin tidak terikat tenant manapun — bisa akses lintas sekolah dengan `withoutTenant()`.
- Primary key: UUID untuk seluruh tabel utama, kecuali tabel keuangan (integer auto-increment, karena sifatnya nomor transaksi berurutan).

*(Detail teknis lengkap ada di `SKILL.md` dan `ARCHITECTURE.md`.)*

---

## 6. Non-Functional Requirements

- **Keamanan data**: data sensitif siswa/guru/staf dienkripsi lewat service gRPC terpisah (Rust), tidak disimpan plain di database utama.
- **Skalabilitas**: arsitektur mendukung penambahan tenant baru tanpa provisioning infrastruktur baru.
- **Concurrency tinggi**: endpoint tertentu (mis. bulk attendance) direncanakan dilayani microservice Go Fiber, bukan lewat Laravel langsung.
- **Observability**: Laravel Horizon (queue) dan Telescope (debug/query) tersedia khusus untuk superadmin.
- **Real-time**: notifikasi booking/absensi via Laravel Reverb (WebSocket).
- **Containerized deployment**: seluruh service (app, nginx, queue, scheduler, vite) berjalan di Docker, dipisahkan dari config infrastruktur shared (postgres, redis, reverb, nginx-proxy-manager).

---

## 7. Yang Masih Perlu Diklarifikasi

Bagian ini sengaja dikosongkan untuk diisi bersama product owner — dokumen ini disusun dari pembacaan kode, bukan dari requirement bisnis asli:

- [ ] Model bisnis subscription: tier apa saja, harga, batasan per tier?
- [ ] Prioritas modul mana yang harus punya UI/endpoint jalan duluan (MVP scope)?
- [ ] Target jumlah sekolah/tenant di fase awal (mempengaruhi kapasitas infra)?
- [ ] Apakah ada rencana aplikasi mobile (selain web) untuk siswa/guru/ortu?
- [ ] Kebijakan retensi data & compliance (mis. UU PDP) untuk data sensitif siswa?
- [ ] Definisi "selesai"/acceptance criteria per modul untuk rilis pertama (khususnya: kapan modul Absensi dianggap cukup matang untuk dipakai sekolah beneran).
- [ ] Apakah ada fitur/data dari sistem lama (single-tenant) yang wajib dipertahankan atau dimigrasikan, atau ini murni rebuild dari nol.

---

## 8. Referensi

- `README.md` — dokumentasi teknis lengkap (setup, docker, konvensi kode)
- `SKILL.md` — panduan kerja untuk Claude/AI assistant saat membantu development EduZone
- `ARCHITECTURE.md` — detail arsitektur teknis (multi-tenancy, docker, service terpisah)
