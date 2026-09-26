# Dokumentasi Modul Absensi — EduZone

> Disusun dari hasil eksplorasi codebase per 25 September 2026. Item yang ditandai
> **(belum diverifikasi langsung)** artinya disebut di dokumen kontrak/komentar kode
> tapi belum pernah dicek isinya langsung selama sesi ini — cek ulang sebelum
> dijadikan acuan pasti.

---

## 1. Arsitektur Umum

Modul Absensi melibatkan **3 sistem terpisah**:

1. **Laravel (`eduzone_saas`, DB utama)** — aplikasi SaaS EduZone yang sudah ada,
   nyimpen data siswa/guru/staff/jadwal yang sebenarnya.
2. **absensi-gateway (Go)** — service terpisah yang nangani check-in real-time
   (RFID/QR/Face/HP), pakai database sendiri.
3. **`eduzone_absensi` (Postgres, connection `pgsql_absensi` di Laravel)** —
   database milik gateway, tapi **diakses langsung oleh Laravel juga** (bukan cuma
   lewat HTTP API) untuk keperluan baca data absensi di semua dashboard.

Laravel dan gateway saling terhubung lewat 2 jalur:
- **Sync data referensi** (Laravel → gateway, pull setiap 5 menit): sekolah, orang,
  jadwal.
- **HTTP API check-in/enrollment** (device/HP/admin → gateway, real-time): tempat
  kejadian absen sebenarnya dicatat.

---

## 2. Alur Data (Flow)

### 2.1 Sinkronisasi data referensi (Laravel → Gateway)
```
Laravel (SyncController) --[pull tiap 5 menit]--> absensi-gateway
   /api/internal/sync/schools    -> schools_ref
   /api/internal/sync/people     -> people_ref
   /api/internal/sync/schedules  -> schedules_ref
```
Autentikasi pakai header `X-Sync-Token` (shared secret `ABSENSI_SYNC_TOKEN`,
terpisah dari JWT). Gateway yang **menjemput** (pull), Laravel cuma sedia endpoint
baca. UPSERT berdasar ID, aman dipanggil berulang.

### 2.2 Check-in via device tetap (RFID/QR/Face di kiosk/gerbang)
```
Device (X-Device-Key) --POST /checkin/device--> Gateway
   -> insert attendance_events (raw, insert-only)
   -> agregasi real-time -> UPSERT attendance_daily
      (termasuk deteksi status Terlambat, pakai schools_ref.late_cutoff_time)
   -> kalau device attached ke 1 kelas & ada jadwal aktif -> schedule_id ikut tercatat
```
Laravel **tidak terlibat** di jalur ini — baca doang dari `attendance_daily`/
`attendance_events` belakangan.

### 2.3 Absen mandiri via HP (halaman "Absen HP")
Dua jalur beda tergantung role, sengaja dipisah:
```
GURU (guru_mapel/wali_kelas):
  AbsenHpController -> GatewayTokenIssuer::issueForTeacher() [JWT role=teacher]
  -> POST gateway /checkin/teacher (gateway urus geofencing GPS + jaringan sekolah)
  -> attendance_events -> attendance_daily (sama seperti device)

SISWA & STAFF NON-GURU (kepsek/tu/kurikulum/kesiswaan/bk/toolman):
  AbsenHpController -> hitung jarak GPS sendiri (haversine) di Laravel
  -> INSERT langsung ke attendance_events (Laravel yang tulis, bukan gateway)
  -> menunggu agregasi gateway berikutnya buat masuk ke attendance_daily
```
Catatan: jalur siswa/staff ini sementara, karena gateway belum punya endpoint
setara `checkin/teacher` untuk role selain guru.

### 2.4 Enrollment kredensial QR (TU)
```
TU klik "Generate QR" -> CredentialController
  -> generate token acak (bin2hex random_bytes) - TIDAK PERNAH disimpan Laravel
  -> GatewayTokenIssuer::issueForAdmin() [JWT role=admin]
  -> POST gateway /enrollment/credentials {credential_value: token mentah}
  -> Gateway HASH token itu sendiri -> simpan ke tabel `credentials`
  -> Laravel terima sukses -> render QR (endroid/qr-code, SVG) dari token mentah
     yang masih ada di memori request -> tampil sekali di halaman cetak -> hilang
```
Kenapa lewat HTTP API, bukan tulis langsung ke tabel `credentials`: Laravel tidak
tahu algoritma hash gateway, jadi kalau nulis hash sendiri berisiko QR tidak
pernah cocok saat discan nanti.

### 2.5 Input manual Izin/Sakit/Alpa (TU)
```
TU pilih orang + status -> ManualAttendanceController
  -> firstOrNew + save ke attendance_daily langsung dari Laravel (bukan lewat gateway)
```
⚠️ **Race condition diketahui**: kalau device sempat rekam check-in di hari yang
sama SETELAH input manual ini, agregasi gateway berikutnya bisa menimpa balik
status jadi "Hadir". Belum ada penanganan.

### 2.6 Dashboard per role (baca data, hari ini saja)
| Role | Sumber data | Cara baca |
|---|---|---|
| TU | `attendance_daily` + `people_ref` | Trust kolom `status` (Hadir/Terlambat/Sakit/Izin/Alpa) |
| Kepsek | `attendance_daily` + `people_ref` | Sama, versi ringkas (tanpa daftar nama) |
| Guru Mapel | `attendance_daily` (diri sendiri) + `attendance_events` per `schedule_id` (murid) | Status murid biner Hadir/Belum Absen — `attendance_period` kosong, tidak dipakai |
| Wali Kelas | `attendance_events` mentah | Biner Hadir/Belum Absen, alasan sama seperti Guru Mapel |
| Superadmin (Rekap) | `attendance_daily` + `people_ref`, digroup per `school_id` | Lintas sekolah, join manual di PHP (beda koneksi DB) |
| Superadmin (Health) | Gateway ping + DB ping + `devices.last_seen_at` + freshness sync | `HealthCheckService` |

### 2.7 Sync balik ke DB utama
```
attendance_daily (eduzone_absensi) --[tiap 10 menit]--> student_attendance / teacher_attendance (eduzone_saas)
```
Lewat 2 command terjadwal (lihat daftar file). Ini jalur yang bikin data absensi
akhirnya "kelihatan" di modul EduZone yang lain (rapor, dsb) — sebelum ini
dijalankan, data absensi HANYA ada di `eduzone_absensi`.

---

## 3. Daftar File & Fungsinya

### 3.1 Model (`app/Models/Absensi/`, connection `pgsql_absensi`)
| File | Fungsi |
|---|---|
| `AttendanceEvent.php` | Log mentah tiap tap/scan/GPS check-in. Insert-only. |
| `AttendanceDaily.php` | Rekap 1 baris per orang per hari. Status resmi (Hadir/Terlambat/Sakit/Izin/Alpa) diisi gateway (device) atau `ManualAttendanceController` (Laravel, manual). |
| `AttendancePeriod.php` | Rekap per jam pelajaran. **Tabel kosong total, belum ada proses apapun yang mengisi** — jangan dipakai sebagai sumber data sampai ada job agregasinya. |
| `PeopleRef.php` | Cache siswa/guru/staff dari DB utama. Composite key `(person_id, person_type)` — jangan pakai `::find()`. |
| `SchedulesRef.php` | Cache jadwal mengajar dari DB utama. `day_of_week`: 1=Senin..7=Minggu. |
| `SchoolRef.php` | Cache data sekolah (koordinat, radius geofence, `late_cutoff_time`). |
| `Credential.php` | Metode absensi per orang (hash RFID/QR/dll). Hash dihitung gateway, bukan di sini. |
| `Concerns/HasCompositePrimaryKey.php` | Trait pendukung model dengan composite PK **(belum diverifikasi langsung isinya)**. |
| `FaceTemplate.php` | Template wajah terenkripsi, terhubung ke `Credential` **(belum diverifikasi langsung — cuma diketahui dari relasi `Credential::faceTemplates()`)**. |

### 3.2 Controller — Tenant (per role sekolah)
| File | Route utama | Fungsi |
|---|---|---|
| `Tenant/Tu/AttendanceDashboardController.php` | `/tu/absensi` | Dashboard rekap sekolah untuk TU (ringkasan + belum-absen + check-in terbaru), polling 8 detik |
| `Tenant/Tu/ManualAttendanceController.php` | `/tu/absensi/manual` **(nama route asumsi, cek ulang)** | Input manual status Izin/Sakit/Alpa, siswa & guru saja |
| `Tenant/Tu/CredentialController.php` | `/tu/kredensial` | Generate & cetak kredensial QR (per orang, per kelas untuk siswa, per tipe untuk guru/staff) |
| `Tenant/Kepsek/AttendanceMonitorController.php` | `/kepsek/absensi` | Monitoring ringkas untuk Kepsek (persentase, breakdown status, feed check-in), tanpa daftar nama |
| `Tenant/GuruMapel/AbsensiController.php` | `/guru-mapel/absensi`, `/guru-mapel/absensi/{scheduleId}` | `index()`: ringkasan absensi diri + card per jadwal (COUNT saja, ringan). `detail()`: daftar murid lengkap 1 jadwal, guard `teacher_id` |
| `Tenant/WaliKelas/AbsensiController.php` | `/absensi` | Dashboard absensi kelas untuk wali kelas (pre-existing, tidak dibuat di sesi ini) |
| `Tenant/Absensi/RekapController.php` | `/absensi/rekap` | Rekap semua kelas untuk TU & Kepsek, versi "per kelas" (pre-existing) |
| `Tenant/Absensi/AbsenHpController.php` | `/absen-hp` | Halaman & proses absen mandiri via HP, jalur beda untuk guru vs siswa/staff (pre-existing) |
| `Tenant/Guru/DashboardController.php` | `guru.dashboard` | Dashboard umum guru/wali kelas (bukan spesifik absensi, tapi menampilkan info kelas wali) (pre-existing) |

### 3.3 Controller — Superadmin
| File | Route utama | Fungsi |
|---|---|---|
| `Superadmin/AbsensiHealthController.php` | `/superadmin/absensi/health` | Shell view + endpoint JSON status kesehatan sistem (pre-existing) |
| `Superadmin/AbsensiRekapController.php` | `/superadmin/absensi/rekap` | Shell view + endpoint JSON rekap kehadiran lintas sekolah (dibuat sesi ini) |
| `Superadmin/DeviceController.php` | `/superadmin/absensi/devices` | CRUD device kiosk/RFID per sekolah (pre-existing) |

### 3.4 Service (`app/Services/Absensi/`)
| File | Fungsi |
|---|---|
| `HealthCheckService.php` | Logic cek gateway/DB/device/sync freshness, dipakai `AbsensiHealthController` (pre-existing) |
| `AttendanceOverviewService.php` | Logic rekap kehadiran lintas sekolah, dipakai `AbsensiRekapController` (dibuat sesi ini) |
| `GatewayTokenIssuer.php` | Terbitkan JWT HS256 (role `teacher`/`admin`) buat autentikasi ke gateway (pre-existing, dipakai `AbsenHpController` & `CredentialController`) |

### 3.5 Sync & Integrasi
| File | Fungsi |
|---|---|
| `Http/Controllers/Api/SyncController.php` | Expose 3 endpoint baca (`/api/internal/sync/schools,people,schedules`) buat dijemput gateway |
| `Http/Middleware/VerifySyncToken.php` **(belum diverifikasi langsung, nama sesuai dokumen kontrak)** | Validasi header `X-Sync-Token` pakai `hash_equals()` |
| `Console/Commands/Absensi/AggregateAttendanceDaily.php` | **DINONAKTIFKAN** (disabled di `routes/console.php`) — dibiarkan cuma untuk referensi, karena gateway sudah agregasi sendiri |
| `Console/Commands/Absensi/SyncAttendanceDailyToMain.php` | Terjadwal tiap 10 menit — salin `attendance_daily` (siswa) ke `student_attendance` DB utama |
| `Console/Commands/Absensi/SyncTeacherAttendanceDailyToMain.php` | Sama, versi guru ke `teacher_attendance` |

### 3.6 Views
| Path | Fungsi |
|---|---|
| `resources/views/tenant/tu/absensi/index.blade.php` | Dashboard TU |
| `resources/views/tenant/tu/absensi/manual.blade.php` **(nama asumsi)** | Form input manual Izin/Sakit/Alpa |
| `resources/views/tenant/tu/kredensial/index.blade.php` | Daftar orang + tombol generate QR |
| `resources/views/tenant/tu/kredensial/print.blade.php` | Halaman cetak QR (layout print sendiri, bukan extend layout tenant) |
| `resources/views/tenant/kepsek/absensi/index.blade.php` | Dashboard Kepsek |
| `resources/views/tenant/guru_mapel/absensi/index.blade.php` | Ringkasan + card jadwal Guru Mapel |
| `resources/views/tenant/guru_mapel/absensi/detail.blade.php` | Daftar murid 1 jadwal |
| `resources/views/tenant/absensi/dashboard.blade.php` | Dashboard Wali Kelas (pre-existing) |
| `resources/views/tenant/absensi/rekap.blade.php` | Rekap per kelas TU/Kepsek (pre-existing) |
| `resources/views/tenant/absensi/absen-hp.blade.php` | Halaman Absen HP (pre-existing) |
| `resources/views/superadmin/absensi/health.blade.php` | Status Layanan (pre-existing) |
| `resources/views/superadmin/absensi/rekap.blade.php` | Rekap Kehadiran lintas sekolah |
| `resources/views/superadmin/absensi/devices/{index,create,edit}.blade.php` | CRUD device (pre-existing) |

### 3.7 Dokumen referensi (repo `absensi-gateway`, bukan Laravel)
| File | Isi |
|---|---|
| `docs/api_contract.md` | Kontrak lengkap semua endpoint HTTP gateway (check-in, enrollment, heartbeat, media foto) |
| `docs/laravel-sync-contract.md` | Kontrak 3 endpoint sync yang harus disediakan Laravel |
| `docs/status-dan-tugas-laravel.md` | Status per fitur + PR yang harus dikerjakan sisi Laravel (sudah banyak yang selesai per sesi ini) |

---

## 4. Tabel Database Kunci (`eduzone_absensi`, connection `pgsql_absensi`)

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `attendance_events` | `id, school_id, device_id, schedule_id, person_id, person_type, method, event_type, confidence_score, is_valid, flagged_reason, raw_payload, recorded_at, row_hash, prev_hash, signature` | Insert-only, punya hash-chaining (belum tentu aktif dipakai) |
| `attendance_daily` | `id, school_id, person_id, person_type, date, first_check_in, last_check_out, status, primary_method, total_events, has_anomaly, updated_at, notes` | `status` check constraint: `Hadir/Terlambat/Sakit/Izin/Alpa`; `person_type`: `student/teacher/staff` |
| `attendance_period` | sama seperti `attendance_daily` + `schedule_id` | **Kosong, tidak dipakai** |
| `people_ref` | `person_id, school_id, person_type, full_name, photo_url, class_id, grade, is_active, synced_at` | Composite key `(person_id, person_type)` |
| `schedules_ref` | `schedule_id, school_id, class_id, subject_name, teacher_id, day_of_week, start_time, end_time, is_active, synced_at` | `day_of_week` 1=Senin..7=Minggu |
| `schools_ref` | (termasuk `latitude, longitude, geofence_radius_meters, late_cutoff_time`) | Sekolah tanpa koordinat GPS di-skip dari sync |
| `credentials` | `id, school_id, person_id, person_type, method, credential_hash, is_active, enrolled_at, revoked_at` | Hash dihitung gateway, bukan Laravel |
| `devices` | `id, school_id, device_code, name, device_type, location, default_class_id, ip_address, api_key_hash, last_seen_at, is_active, capabilities` | |

---

## 5. Status Fitur — Ringkasan

**Selesai & teruji:**
- Check-in device gerbang/kiosk (data asli 322 orang)
- Absen HP guru (geofencing via gateway) & siswa/staff (direct-write)
- Kredensial QR: generate + cetak (satuan, per kelas, per tipe guru/staff)
- Dashboard TU, Kepsek, Guru Mapel, Wali Kelas, Superadmin (health + rekap)
- Sync data referensi (Laravel→Gateway) & sync balik hasil absen (Gateway DB→DB utama)
- Input manual Izin/Sakit/Alpa (siswa & guru)

**Belum ada / belum lengkap:**
1. Rekap historis (rentang tanggal) + export Excel/PDF — semua dashboard baru "hari ini"
2. `attendance_period` kosong, tidak ada proses pengisian — absen per-mapel tanpa histori
3. Manual Izin/Sakit/Alpa belum mendukung staff (`staff_attendance` belum ada di DB utama)
4. Race condition: device check-in belakangan bisa menimpa input manual
5. Tidak ada sisi orang tua/wali murid (view maupun notifikasi)
6. Anomali cuma tampil pasif, tidak ada notifikasi proaktif
7. Worker face recognition belum ada (job selalu "processing")
8. Tidak ada tombol revoke kredensial di Laravel (siswa keluar/lulus)
9. Keamanan endpoint foto profil (tanpa auth, foto anak di bawah umur) belum diputuskan untuk production
10. Kualitas data dummy (nama guru terpakai untuk siswa) mengganggu demo/QA
