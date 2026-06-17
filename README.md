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
│                  Docker Environment                  │
│                                                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────────┐    │
│  │  nginx   │   │   app    │   │    vite      │    │
│  │:8083→80  │──▶│  php-fpm │   │ :5174 (dev)  │    │
│  └──────────┘   │  :9000   │   └──────────────┘    │
│                 └────┬─────┘                        │
│  ┌──────────┐        │        ┌──────────────┐      │
│  │ postgres │◀───────┤        │    redis     │      │
│  │  :5432   │        │        │    :6379     │      │
│  └──────────┘        │        └──────────────┘      │
│                      │                              │
│  ┌──────────┐         └──────▶ ┌──────────────┐    │
│  │  reverb  │                  │    queue     │    │
│  │  :8082   │                  │   (worker)   │    │
│  └──────────┘                  └──────────────┘    │
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
| PHP | PHP | 8.3 |
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
│   │   │   ├── Auth/                    # Login, Dashboard router tenant
│   │   │   └── Superadmin/             # Semua controller superadmin
│   │   │       ├── Auth/               # Login/logout superadmin
│   │   │       └── DashboardController.php
│   │   └── Middleware/
│   │       ├── InitializeTenancy.php   # Set search_path + tenant
│   │       ├── RoleMiddleware.php      # Cek role user
│   │       ├── EnsureUserIsActive.php  # Cek is_active
│   │       └── SuperadminOnly.php      # Guard superadmin area
│   ├── Models/                         # 39 Eloquent models
│   ├── Multitenancy/
│   │   ├── Concerns/
│   │   │   └── BelongsToSchool.php     # Trait untuk semua model tenant
│   │   ├── Scopes/
│   │   │   └── SchoolScope.php         # Global scope filter school_id
│   │   └── TenantFinder/
│   │       └── AuthUserTenantFinder.php
│   └── Providers/
│       └── AppServiceProvider.php      # Set search_path PostgreSQL
│
├── database/
│   ├── migrations/                     # 39 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       ├── SuperadminSeeder.php
│       └── SchoolSeeder.php
│
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── welcome.blade.php           # Landing page
│       ├── auth/
│       │   └── login.blade.php         # Login tenant
│       ├── superadmin/
│       │   ├── auth/login.blade.php    # Login superadmin (dark theme)
│       │   ├── layouts/app.blade.php   # Layout superadmin
│       │   └── dashboard/index.blade.php
│       └── tenant/                     # Dashboard per role (segera)
│           ├── kepsek/
│           ├── guru/
│           ├── siswa/
│           └── ...
│
├── routes/
│   ├── web.php                         # Public + tenant auth
│   ├── tenant.php                      # Dashboard routes per role
│   ├── superadmin.php                  # Semua route superadmin
│   └── console.php
│
├── docker/
│   ├── nginx/default.conf
│   ├── php/
│   │   ├── php-fpm.conf
│   │   ├── php-dev.ini
│   │   └── php-prod.ini
│   └── postgres/
│       └── init-multiple-db.sh
│
└── bootstrap/app.php                   # Route loader + middleware alias
```

---

## Setup & Instalasi

### Prasyarat

- Docker Desktop
- Git

### Langkah Instalasi

```bash
# 1. Clone repository
git clone <repo-url> eduzone
cd eduzone

# 2. Copy environment file
cp .env.example .env

# 3. Install dependencies via container
docker exec eduzone_app composer install
docker exec eduzone_vite npm install

# 4. Generate app key
docker exec eduzone_app php artisan key:generate

# 5. Jalankan migration
docker exec eduzone_app php artisan migrate

# 6. Jalankan seeder
docker exec eduzone_app php artisan db:seed

# 7. Build assets
docker exec eduzone_vite npm run build
```

### Development Mode (Hot Reload)

```bash
docker exec -it eduzone_vite npm run dev
```

---

## Konfigurasi Docker

### Container Services

| Container | Fungsi | Port |
|---|---|---|
| `eduzone_app` | PHP-FPM (Laravel) | 9000 (internal) |
| `eduzone_nginx` | Web server | 8083 |
| `eduzone_vite` | Asset bundler | 5174 |
| `eduzone_queue` | Laravel Horizon — queue worker | — |
| `eduzone_scheduler` | Laravel Scheduler — cron setiap menit | — |
| `postgres` | Database (shared) | 5432 |
| `redis` | Cache/session/queue | 6379 |
| `reverb` | WebSocket | 8082 |

### Perintah Docker Umum

```bash
# Artisan
docker exec eduzone_app php artisan <command>

# Composer
docker exec -u root eduzone_app composer <command>

# Build assets (WAJIB setelah ubah CSS/JS)
docker exec eduzone_vite npm run build

# Masuk ke container
docker exec -it eduzone_app sh

# Lihat log
docker logs eduzone_app -f
docker logs eduzone_nginx -f

# Restart container
docker restart eduzone_app
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

### Fitur Login

- Login menggunakan **email ATAU username**
- Cek `is_active` — akun nonaktif langsung ditolak
- Update `last_login_at` setiap login sukses
- Rate limiting pada superadmin login (5x per menit per IP)
- Session regenerate setelah login untuk mencegah session fixation

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
└── console.php      # Artisan commands

# Prefix otomatis dari bootstrap/app.php:
# - superadmin.php → /superadmin/* dengan name superadmin.*
# - tenant.php     → /* tanpa prefix tambahan
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
├── superadmin/
│   ├── auth/
│   │   └── login.blade.php        # Login superadmin (dark theme)
│   ├── layouts/
│   │   └── app.blade.php          # Layout: sidebar + topbar dark
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── schools/
│   │   └── index.blade.php
│   └── users/
│       └── index.blade.php
│
└── tenant/
    ├── layouts/
    │   └── app.blade.php          # Shared layout untuk semua tenant
    ├── kepsek/
    │   └── dashboard/
    │       └── index.blade.php
    ├── guru/
    │   └── dashboard/
    │       └── index.blade.php
    ├── siswa/
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
