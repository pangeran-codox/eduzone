# 📚 EduZone — Tenant Dashboards & Debug Tools
## Dokumen Tanggal: 6 September 2026

Dokumen ini mencakup **dua fitur besar** yang ditambahkan ke project EduZone pada 6 September 2026:

1. **Bagian A** — 5 Halaman Dashboard Tenant untuk Role Baru (Kurikulum, Kesiswaan, BK, Toolman, Siswa) beserta stat cards real-nya.
2. **Bagian B** — 3 Tools Debugging (Eloquent Scope Macro + 2 Artisan Commands) untuk mempercepat development multi-tenancy.

---

# 🅰️ Bagian A — Tenant Dashboard Baru

## Ringkasan
Sebelumnya (6 Sep pagi), hanya **3 dashboard tenant** yang memiliki view Blade sungguhan:
- `tenant.tu.dashboard.index` (Dashboard Tata Usaha — sudah punya stat cards real)
- `tenant.kepsek.dashboard.index` (Dashboard Kepala Sekolah — 6 placeholder cards)
- `tenant.guru.dashboard.index` (Dashboard Guru — 1 card aktif + 2 placeholder)

**5 Dashboard yang belum ada view** (masih pakai `eduzone_placeholder_dashboard()` dari closure route):
- `Kurikulum`, `Kesiswaan`, `BK`, `Toolman`, `Siswa`

Sekarang **SEMUA 8 dashboard tenant** sudah punya view Blade lengkap dengan pola yang konsisten, dan 5 dashboard baru sudah ditambahkan **stat cards real dengan data dari database**.

---

## A.1 Struktur File yang Ditambahkan / Dimodifikasi

### ✨ File View Baru (5 Dashboard)
Semua mengikuti path pattern `resources/views/tenant/{role}/dashboard/index.blade.php`:

| Role | File Path |
|---|---|
| Kurikulum | `resources/views/tenant/kurikulum/dashboard/index.blade.php` |
| Kesiswaan | `resources/views/tenant/kesiswaan/dashboard/index.blade.php` |
| BK | `resources/views/tenant/bk/dashboard/index.blade.php` |
| Toolman | `resources/views/tenant/toolman/dashboard/index.blade.php` |
| Siswa | `resources/views/tenant/siswa/dashboard/index.blade.php` |

### ✏️ File yang Dimodifikasi
| File Path | Perubahan |
|---|---|
| `routes/tenant.php` | 5 Closure route dashboard diubah dari pola `view()->exists() ? view() : placeholder()` → **menambahkan query database real** dan `view(..., $data)` untuk mempassing stat cards. **Plus:** route baru `tenant.test.auto-login` untuk signed auto-login (lihat Bagian B.3). |

---

## A.2 Pola Konsisten Dashboard (8 Dashboard)

**Setiap dashboard — tanpa terkecuali — punya struktur:**

```blade
@extends('tenant.layouts.app')
@section('title', 'Dashboard {Nama Role}')
@section('page-title', 'Dashboard {Nama Role}')

@section('content')
    @php $user = auth()->user(); @endphp

    {{-- 1. HEADER SELAMAT DATANG --}}
    <div class="mb-6">
        <h1>Selamat datang, {{ $user->username }} 👋</h1>
        <p>{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    {{-- 2. STAT CARDS (4 KOLOM) — HANYA DI 6 DASHBOARD: TU + 5 BARU --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card">... angka dari DB ...</div>
        <div class="stat-card">... angka dari DB ...</div>
        <div class="stat-card">... angka dari DB ...</div>
        <div class="stat-card">... angka dari DB ...</div>
    </div>

    {{-- 3. FEATURE CARDS (2-3 KOLOM, 6 ITEMS) — PLACEHOLDER OPACITY 0.5 --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="t-card p-5" style="opacity: 0.5;">
            {{-- icon SVG + title + "Segera hadir" --}}
        </div>
        {{-- ... 5 kartu lagi --}}
    </div>
@endsection
```

> **Catatan:** Dashboard `Kepsek` dan `Guru` sengaja **TIDAK** ditambahkan stat cards pada batch perubahan ini — karena keduanya sudah punya pola cards yang berbeda. Dashboard `Guru` bahkan sudah punya card **link aktif** ke `wali_kelas.absensi.dashboard`. Kalau nanti mau tambah stat cards, tinggal copy pola `TU` saja.

---

## A.3 Detail Stat Cards per Dashboard

Semua query **otomatis tenant-safe** karena model memakai trait `BelongsToSchool` (lihat Bagian B.1 — trait ini attach `SchoolScope` sebagai Global Scope yang otomatis menambahkan `WHERE school_id = School::current()->id`).

### 🟡 Dashboard Kurikulum
File view: `tenant/kurikulum/dashboard/index.blade.php`
Data dipassing dari route closure `routes/tenant.php` group `kurikulum.`:

| Nama Variabel | Query Model | Deskripsi |
|---|---|---|
| `$totalKelasAktif` | `SchoolClass::where('is_active', true)->count()` | Jumlah semua kelas yang aktif |
| `$totalGuruAktif` | `Teacher::where('is_active', true)->count()` | Jumlah guru aktif (guru_mapel + wali_kelas) |
| `$totalJadwalAktif` | `Schedule::where('is_active', true)->count()` | Jumlah baris jadwal pelajaran aktif |
| `$totalJurusanAktif` | `Major::where('is_active', true)->count()` | Jumlah jurusan / program keahlian |

### 🔵 Dashboard Kesiswaan
File view: `tenant/kesiswaan/dashboard/index.blade.php`

| Nama Variabel | Query Model | Deskripsi |
|---|---|---|
| `$totalSiswaAktif` | `Student::where('status', 'aktif')->count()` | Total siswa dengan status "aktif" |
| `$totalPrestasi` | `StudentAchievement::count()` | Total semua prestasi siswa (sepanjang waktu) |
| `$totalKasusSikap` | `StudentSikap::count()` | Total catatan sikap / pelanggaran |
| `$prestasiBulanIni` | `StudentAchievement::whereBetween('created_at', [startOfMonth, endOfMonth])->count()` | Prestasi yang diinput bulan ini |

### 🟢 Dashboard BK
File view: `tenant/bk/dashboard/index.blade.php`

| Nama Variabel | Query Model | Deskripsi |
|---|---|---|
| `$totalSiswaAktif` | `Student::where('status', 'aktif')->count()` | Populasi siswa aktif |
| `$totalSesiKonseling` | `CounselingSession::count()` | Total semua sesi BK (sepanjang waktu) |
| `$sesiHariIni` | `CounselingSession::whereDate('date', today())->count()` | Sesi yang terjadwal / terjadi hari ini |
| `$sesiBulanIni` | `CounselingSession::whereBetween('date', [startOfMonth, endOfMonth])->count()` | Sesi bulan berjalan |

### ⚫ Dashboard Toolman
File view: `tenant/toolman/dashboard/index.blade.php`

| Nama Variabel | Query Model | Deskripsi |
|---|---|---|
| `$totalItemInventaris` | `Inventory::count()` | Banyaknya JENIS barang (COUNT baris, bukan qty) |
| `$totalUnitInventaris` | `Inventory::sum('quantity')` | TOTAL SEMUA UNIT barang (SUM quantity) |
| `$laporanHariIni` | `ToolmanReport::whereDate('date', today())->count()` | Laporan aktivitas hari ini |
| `$laporanBulanIni` | `ToolmanReport::whereBetween('date', [startOfMonth, endOfMonth])->count()` | Laporan bulan berjalan |

### 🟠 Dashboard Siswa ⭐ PERSONALIZED
Dashboard siswa **BUKAN** agregat sekolah — **semua data difilter per user yang login**:
- Pertama resolve `auth()->user()->student` (relasi `hasOne` dari User ke Student via `user_id`)
- Ambil `student_id` dan `class_id` dari relasi itu
- Semua query di-filter khusus ID tersebut

Data dipassing dari closure route:
| Nama Variabel | Query / Logika | Deskripsi |
|---|---|---|
| `$totalJadwalMapel` | `Schedule::where('class_id', $classId)->distinct('subject')->count('subject')` | Jumlah MATA PELAJARAN UNIK (bukan jumlah jadwal jam) |
| `$rataRataNilai` | `StudentGrade::where('student_id', $studentId)->avg('nilai_akhir')` → `number_format(..., 2, ',', '.')` | Rata-rata seluruh nilai_akhir siswa. Kalau kosong jadi `-`. |
| `$kehadiranHadir` | `StudentAttendance` bulan ini → `WHERE status = 'hadir'` → COUNT | Banyaknya hari hadir bulan berjalan |
| `$totalKehadiranBulanIni` | `StudentAttendance` bulan ini → COUNT (apapun status) | Total hari absen tercatat bulan berjalan |
| `$totalPrestasiSiswa` | `StudentAchievement::where('student_id', $studentId)->count()` | Jumlah prestasi per siswa |
| `$studentName` | `$student->full_name ?? $user->username` | Nama lengkap siswa (fallback username) |
| `$className` | `$student->class->nama_kelas ?? '-'` | Nama kelas siswa (misal "X IPA 1") |

> **Header Khusus Siswa:** Baris subtitle bukan hanya tanggal — tapi **`Kelas {nama_kelas} • {tanggal}`** (jika kelas tidak `-`).

---

## A.4 Feature Cards Placeholder — Isinya Apa Saja?

Semua dashboard memiliki **6 kartu fitur placeholder** (opacity 0.5, tidak jadi link) dibawah stat cards. Kalau nanti modulnya sudah dibuat, tinggal ganti `<div class="t-card p-5" style="opacity:0.5">` jadi `<a href="{{ route(...) }}" class="t-card p-5 block hover:opacity-90 transition-opacity">` — seperti contoh card **Absensi Kelas** di dashboard Guru.

Daftar 6 fitur placeholder per role bisa dilihat langsung di file view masing-masing.

---

# 🅱️ Bagian B — Tools Debugging Multi-Tenancy

Terdiri dari **1 Service Provider (macro Eloquent)** + **2 Artisan Command** + **1 Signed Route Auto-Login**.

---

## B.1 Tool 1 — Eloquent Macro: `explainTenantScope()`, `dumpTenantScope()`, `ddTenantScope()`

### File Penyedia Fitur
📁 **Provider:** `app/Providers/TenantDebugServiceProvider.php`
📁 **Diregistrasikan di:** `bootstrap/providers.php` (ditambahkan baris `App\Providers\TenantDebugServiceProvider::class`)

### Kapan Aktif?
✅ HANYA jika `APP_ENV=local` ATAU `development` ATAU `testing`
❌ **Production:** Service Provider di-boot tapi `boot()` return lebih awal → **tidak ada macro terdaftar, tidak ada overhead**.

### 3 Macros yang Tersedia (ke SEMUA `Illuminate\Database\Eloquent\Builder`)

#### 🔍 `explainTenantScope(): array`
Mengembalikan array **debug info lengkap** tentang apakah tenant scope benar-benar bekerja di query itu.

```php
// Contoh di Tinker
$info = \App\Models\Student::query()->explainTenantScope();
dd($info);
```

**Key output array:**
| Key | Tipe | Deskripsi |
|---|---|---|
| `model` | `string` | FQCN Model (misal `App\Models\Student`) |
| `table` | `string` | Nama tabel (misal `students`) |
| `has_school_scope` | `bool` | Apakah `SchoolScope` ada di `$builder->appliedScopes`? |
| `current_tenant_id` | `?string` | UUID `School::current()->id` — NULL jika belum mimic |
| `current_tenant` | `?string` | Format `"Nama Sekolah (slug)"` — cepat baca |
| `tenant_scope_applied_in_sql` | `bool` | Scanning raw SQL: apakah ada substring `.school_id` / `"school_id"` |
| `sql_preview` | `string` | Hasil `toRawSql()` — periksa langsung query jadi |
| `bindings_count` | `int` | Jumlah parameter PDO bindings |

> **Use case utama:** Debug kenapa `Student::count()` hasilnya 0 — cukup cek `current_tenant_id` (NULL berarti SchoolScope jalan tapi tidak ada tenant → WHERE school_id = NULL).

#### 🖨️ `dumpTenantScope(): Builder` (Chainable!)
Sama seperti `explainTenantScope()`, tapi:
1. Memanggil `dump($info)` terlebih dahulu (print pretty ke browser / Tinker / Laravel Log)
2. **Return `$this` (Builder)** → bisa dilanjut chain method lain!

```php
// ✅ Chainable!
$jumlahSiswaAktif = \App\Models\Student::where('status', 'aktif')
    ->dumpTenantScope()   // ← dump scope info dulu
    ->count();            // ← query tetap JALAN
```

#### 💥 `ddTenantScope(): never`
Sama dengan `dumpTenantScope()` tapi **panggil `dd($info)` → exit (tidak return apapun)**. Berguna untuk stop script dan lihat saja.

### Bonus Macro: `toRawSql()`
Kalau project menggunakan versi Laravel yang **belum punya** `Builder::toRawSql()` (sebelum Laravel 10.x / 11.x), provider ini otomatis register macro `toRawSql()` yang menggabungkan `toSql()` + bindings ter-escape ke SQL string manusiawi. Bisa dipanggil di **Eloquent Builder dan Query Builder (BaseBuilder)**.

---

## B.2 Tool 2 — Artisan Command: `tenant:mimic`

### File Implementasi
📁 `app/Console/Commands/Tenant/MimicTenantCommand.php`

### Tujuan
**Aktifkan context `School::current()` dari CLI / Tinker.**

Ini tools PALING PENTING untuk debugging aplikasi multi-tenant. Masalah klasik multi-tenant: **semua query di Tinker menghasilkan 0 record** — karena SchoolScope butuh `School::current()` yang biasanya hanya diset lewat HTTP request (dari `AuthUserTenantFinder`). `tenant:mimic` menyelesaikannya.

### Signature
```bash
php artisan tenant:mimic
    {identifier? : Slug / Nama / UUID sekolah}
    {--list : Tampil daftar sekolah aktif}
    {--reset : Reset tenant context ke NULL (tanpa scope filter)}
```

### Cara Pakai (Per Flags)

#### 1. `--list` (Lihat Daftar Sekolah)
```bash
php artisan tenant:mimic --list
```
Menampilkan hingga 15 sekolah pertama dalam bentuk tabel:
```
┌─────────────┬──────────────────┬──────────────┬────────────┬───────┐
│ UUID (poto) │ Nama             │ Slug         │ NPSN       │ Aktif │
├─────────────┼──────────────────┼──────────────┼────────────┼───────┤
│ 0190e1ab... │ SMA Negeri 1     │ sma-negeri-1 │ 20102030   │ ✅    │
│ 0191f2bc... │ SMA Negeri 2     │ sma-negeri-2 │ 20102031   │ ✅    │
└─────────────┴──────────────────┴──────────────┴────────────┴───────┘
```

#### 2. Mimic Tanpa Flags (Pakai Identifier)
```bash
# By SLUG — PALING DIREKOMENDASIKAN (singkat, pasti unik)
php artisan tenant:mimic sma-negeri-1

# By UUID (dari tabel --list)
php artisan tenant:mimic 0190e1ab-1234-5678-9abc-def012345678

# By NAMA (bisa >1 hasil → ada prompt pilih nomor)
php artisan tenant:mimic "SMA Negeri"

# By NPSN (string numeric 10 digit)
php artisan tenant:mimic 20102030
```

**Urutan resolve identifier (paling cepat → paling lambat):**
1. Cari UUID → `School::find($identifier)` (exact match, via primary key = tercepat)
2. Cari Slug → `WHERE slug = ?` (unique)
3. Cari Nama → `WHERE LOWER(name) LIKE ?` (case-insensitive, wildcard). **Kalau hasil 3+ → interactive prompt pilih nomor.**
4. Cari NPSN → jika identifier `ctype_digit` (numeric all) → `WHERE npsn = ?`

Jika berhasil, output memberikan info ringkas + TIPS debugging (macro explainTenantScope, toRawSql, withoutTenant).

#### 3. `--reset` (Matikan Scope Sementara — Lihat Lintas Sekolah)
```bash
php artisan tenant:mimic --reset
```
- Memanggil `School::forgetCurrent()` (Spatie method)
- `School::current()` jadi NULL
- **Efek ke SchoolScope:** Scope tetap ter-apply, tapi di `apply()` terdapat blok:
  ```php
  $tenant = School::current();
  if (! $tenant) return; // ⬅️ TIDAK menambahkan WHERE → query TANPA filter!
  ```
- Hasilnya: `Student::count()` akan menampilkan SEMUA siswa DARI SEMUA SEKOLAH.
- Kalau mau balik lagi ke tenant tertentu — jalankan `tenant:mimic <identifier>` lagi (TIDAK perlu reset terlebih dahulu — override langsung).

---

## B.3 Tool 3 — Artisan Command: `tenant:seed-test` + Signed Auto-Login URL

### File Implementasi + Routing
📁 **Command:** `app/Console/Commands/Tenant/SeedTestUserCommand.php`
📁 **Route handler:** `routes/tenant.php` → `GET /__test/auto-login` bernama `tenant.test.auto-login`

### Tujuan
Generate **user test per-role dalam 1 detik** + **signed URL auto-login** yang jika diklik di browser langsung masuk ke dashboard sesuai role — TANPA perlu buka form login, TANPA input username/password, TANPA pilih role di sidebar.

### 🔐 Lapis Keamanan Auto-Login Route
**ROUTE INI SENGAAJA DIBUAT AMAN, BISA DEPLOY KE PRODUCTION TANPA BAHAYA:**
1. **Environment Guard** — handler route baris pertama: `if (!app()->environment(['local','dev','testing'])) { abort(403); }`. Di production → langsung 403 mentah-mentah.
2. **Middleware `signed`** — URL **harus dibuat via `URL::temporarySignedRoute()`** dengan expiration 1 hari. Kalau ada 1 karakter di URL di-modify → `InvalidSignatureException` → 403.
3. **User / School ID Cocok Check** — `$user->school_id !== $school->id` → abort 403 (tidak bisa cross-sekolah paksa login).
4. **Masa Aktif 1 Hari** — URL yang dibuat hari ini, besok sudah expired (automatic dari Laravel signed URL mechanism).

### Command Signature
```bash
php artisan tenant:seed-test
    {--school= : Identifier sekolah (slug / UUID / nama). Default: sekolah pertama aktif}
    {--role= : Role tertentu (9 opsi). Default: semua 9 role.}
    {--all : Generate untuk SEMUA sekolah aktif (bukan cuma 1)}
    {--show-urls : Tampilkan tabel signed URL auto-login. WAJIB ini kalau mau copy URL login.}
    {--password= : Password untuk semua user test (default: "password")}
    {--fresh : Hapus SEMUA user test (username prefix `test_`) SEBELUM generate.}
```

**9 Role Valid (const `ROLE_META` di command):**
`kepsek`, `kurikulum`, `tu`, `guru_mapel`, `wali_kelas`, `kesiswaan`, `bk`, `toolman`, `siswa`

### Username Pattern & Data User
| Atribut | Nilai |
|---|---|
| Username | `test_{role}` → contoh: `test_tu`, `test_bk`, `test_siswa` |
| Email | `test+{role}@{slug-sekolah}.local` → contoh: `test+siswa@sma-negeri-1.local` (jika slug sekolah null → `@eduzone.local`) |
| Password | Default `password` (bisa diganti via `--password=...`) — bcrypt sebelum save |
| `school_id` | UUID sekolah target |
| `is_active` | `true` (aktif, lolos middleware `EnsureUserIsActive`) |

### Contoh Penggunaan Paling Umum

#### 🚀 Use Case 1: Test Dashboard 5 Role Baru (Hari Ini!)
```bash
php artisan tenant:seed-test --show-urls
```
Output:
```
🎉 Selesai: 9 user test siap dipakai.
   Password default semua user: password

🔑 Signed URL auto-login:
┌────────────┬──────────────────┬────────────────┬──────────────────────────┐
│ Sekolah    │ Role             │ Username       │ Login URL                │
├────────────┼──────────────────┼────────────────┼──────────────────────────┤
│ SMA N 1    │ Staff Kurikulum  │ test_kurikulum │ http://eduzone.local/__… │
│ SMA N 1    │ Staff Kesiswaan  │ test_kesiswaan │ http://eduzone.local/__… │
│ SMA N 1    │ Guru BK          │ test_bk        │ http://eduzone.local/__… │
│ SMA N 1    │ Toolman          │ test_toolman   │ http://eduzone.local/__… │
│ SMA N 1    │ Siswa            │ test_siswa     │ http://eduzone.local/__… │
│ ...        │ ...              │ ...            │ ...                      │
└────────────┴──────────────────┴────────────────┴──────────────────────────┘
```
Tinggal copy paste URL ke 5 tab browser → kelima dashboard langsung bisa dilihat dalam 10 detik!

#### 🚀 Use Case 2: Hanya Test 1 Role + Password Custom
```bash
php artisan tenant:seed-test --role=siswa --password=siswa123 --show-urls
```

#### 🚀 Use Case 3: Generate untuk SEMUA Sekolah + Bersihkan User Lama Dulu
```bash
# Hati-hati: --all bisa generate ribuan user jika sekolah ada 10+ (9 user/school × 10 = 90)
php artisan tenant:seed-test --all --fresh --show-urls
```

#### 🚀 Use Case 4: Spesifik Sekolah + 1 Role
```bash
php artisan tenant:seed-test --school=sma-2 --role=kepsek --show-urls
```

### Flow Auto-Login Ketika URL Diklik
1. Browser mengakses `GET /__test/auto-login?user=xxx&school=yyy&signature=zzz&expires=1694xxxxx`
2. Laravel **pertama-tama** menjalankan middleware `signed` → validasi HMAC signature + expire time. **Kalau gagal → 403 sebelum handler disentuh.**
3. Handler disentuh: cek environment (production → 403).
4. Cari user & sekolah via `withoutGlobalScopes()` (tanpa filter tenant — karena mau cross-schools sebelum makeCurrent).
5. Cek kecocokan: `$user->school_id === $school->id` → tidak → 403.
6. **Sekolah diaktifkan:** `$school->makeCurrent()` → SchoolScope siap bekerja.
7. **Login via Auth:** `Auth::login($user)` + `session()->regenerate()` → standard Laravel auth security.
8. **Redirect ke dashboard role:** pakai `$roleDashboardMap` (10 mapping role → route name). Default fallback: `dashboard`.
9. **Flash message:** `test-auto-login=true` + `logged-in-as="test_siswa (siswa) @ SMA Negeri 1"` — bisa ditampilkan di layout kalau mau nambah banner info.

---

## B.4 Cheat Sheet Ringkas (Tempel di Sticky Notes!)

| Apa Yang Dilakukan | Command / Kode |
|---|---|
| Lihat daftar sekolah | `php artisan tenant:mimic --list` |
| Aktifkan sekolah X sebagai context | `php artisan tenant:mimic sma-negeri-1` |
| Nonaktifkan context (lihat semua data) | `php artisan tenant:mimic --reset` |
| Generate 9 user test + URL login | `php artisan tenant:seed-test --show-urls` |
| Generate 1 role + password custom | `php artisan tenant:seed-test --role=bk --password=bk123` |
| Regenerate bersih (hapus user lama) | `php artisan tenant:seed-test --fresh --show-urls` |
| Cek scope query benar atau tidak | `Model::query()->dumpTenantScope()` |
| Hentikan script + lihat scope | `Model::query()->ddTenantScope()` |
| Lihat SQL jadi + semua binding value | `Model::where(...)->toRawSql()` |
| Query LINTAS SEMUA SEKOLAH | `Model::withoutTenant()->get()` (macro dari `SchoolScope::extend`) |

---

## 📋 B.5 Ringkasan Seluruh File Baru + Modifikasi Hari Ini

### ✨ File File BARU
| # | Path | Ukuran Fitur |
|---|---|---|
| 1 | `app/Providers/TenantDebugServiceProvider.php` | Eloquent Macro x3 + toRawSql fallback (Tool B.1) |
| 2 | `app/Console/Commands/Tenant/MimicTenantCommand.php` | Artisan `tenant:mimic` (Tool B.2) |
| 3 | `app/Console/Commands/Tenant/SeedTestUserCommand.php` | Artisan `tenant:seed-test` (Tool B.3) |
| 4 | `resources/views/tenant/kurikulum/dashboard/index.blade.php` | Dashboard Kurikulum (A.3) |
| 5 | `resources/views/tenant/kesiswaan/dashboard/index.blade.php` | Dashboard Kesiswaan (A.3) |
| 6 | `resources/views/tenant/bk/dashboard/index.blade.php` | Dashboard BK (A.3) |
| 7 | `resources/views/tenant/toolman/dashboard/index.blade.php` | Dashboard Toolman (A.3) |
| 8 | `resources/views/tenant/siswa/dashboard/index.blade.php` | Dashboard Siswa — PERSONALIZED (A.3) |
| 9 | `TENANT_DASHBOARDS_DEBUG_TOOLS.md` | Dokumen ini (sendiri 😄) |

### ✏️ File File YANG DIUBAH
| # | Path | Yang Diubah |
|---|---|---|
| 1 | `routes/tenant.php` | **(a)** 5 closure dashboard (Kurikulum, Kesiswaan, BK, Toolman, Siswa) ditambahkan query model & pass data ke view. **(b)** route baru `tenant.test.auto-login` untuk signed URL auto-login (Tool B.3). |
| 2 | `bootstrap/providers.php` | Menambahkan `App\Providers\TenantDebugServiceProvider::class` sebagai provider terakhir (setelah `TelescopeServiceProvider`). |

---

## 🔜 Next Steps / Rekomendasi Lanjutan (TIDAK termasuk pekerjaan hari ini)

1. **Refactor Closure Routes → DashboardController per Role** (Rekomendasi 1 sebelumnya) — biar testable, tidak ada query di route file, konsisten dengan `StudentController` pattern.
2. **Extract Blade Components `x-dashboard-stat-card` & `x-dashboard-placeholder-card`** — 8 dashboard saat ini full duplikasi markup stat cards. Bisa jadi `<x-dashboard-stat-card label="Siswa Aktif" :value="$total" />` 1 baris.
3. **Dashboard `Kepsek` dan `Guru` tambahkan stat cards** — sekarang masih beda pola (langsung feature cards).
4. **Banner Info "Mode Test Auto-Login"** di sidebar — jika session ada `test-auto-login=true` → tampilkan banner kecil di bawah topbar "Anda login via test URL signed (berlaku s.d. XX) — Keluar".
5. **Tambahkan test case PHPUnit / Pest:**
   - `TenantScopeDebugMacroTest` — verify explain berisi key yang benar.
   - `SeedTestUserCommandTest` — assert 9 user tercipta dengan `role` field benar.
   - `TenantDashboardsAccessibleTest` — login via `actingAs($user)` → assert 200 semua route dashboard.
