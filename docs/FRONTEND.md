# FRONTEND.md — Konvensi Frontend EduZone

Stack: Blade + Tailwind CSS + Alpine.js, dibundle lewat Vite (`laravel-vite-plugin`). Dokumen ini merangkum konvensi supaya penambahan halaman/fitur baru tetap konsisten dan nggak bikin bundle JS/CSS numpuk seiring modul bertambah.

---

## 1. Kenapa Alpine.js, bukan Vue/React/Livewire

- Aplikasi ini server-rendered lewat Blade — nggak butuh SPA framework penuh.
- Alpine ditulis langsung di HTML (`x-data`, `x-show`, `x-on`, dll), jadi nggak perlu bikin file komponen terpisah cuma buat dropdown/modal/tab sederhana.
- Ringan (~15kb gzip), nggak nambah kompleksitas build.
- Kalau nanti butuh reaktivitas yang jauh lebih berat di satu halaman spesifik (mis. dashboard real-time yang kompleks), evaluasi ulang saat itu terjadi — jangan ganti stack di awal cuma karena antisipasi.

## 2. Struktur Entry Point — Per Area, Bukan Global atau Per-Modul

```
resources/
├── css/
│   └── app.css                 # satu entry Tailwind untuk SEMUA area (base/components/utilities)
└── js/
    ├── bootstrap.js            # setup axios, dipakai semua area
    ├── alpine.js               # inisialisasi Alpine.js, dipakai area yang butuh interaktivitas
    └── areas/
        ├── superadmin.js       # entry untuk seluruh halaman /superadmin/*
        ├── tenant.js           # entry untuk seluruh halaman tenant (semua role sekolah) + landing page
        └── kiosk.js            # entry untuk layar device absensi (RFID/QR/Face), sengaja minim dependency
```

**Kenapa per-area:**
- **Bukan satu bundle global** — superadmin (dark theme, tools internal) dan tenant (banyak role, tema beda) nggak perlu saling numpuk JS yang nggak relevan.
- **Bukan per-modul** (mis. `absensi.js`, `akademik.js` terpisah-pisah) — kepagian selama modul-modul itu belum punya UI. Kalau suatu saat satu area (`tenant.js`) sudah keburu berat karena banyak modul numpuk di situ, baru pertimbangkan pecah lebih granular per modul — bukan didesain granular dari awal.
- **CSS tetap satu entry** (`app.css`) untuk semua area. Tailwind sudah men-generate utility class berdasarkan `content` yang dipindai di `tailwind.config.js` (mencakup seluruh `resources/**/*.blade.php`), jadi memecah CSS per area tidak mengurangi ukuran bundle secara berarti — cuma menambah kerumitan tanpa manfaat nyata.

## 3. Aturan Menambah Entry/Halaman Baru

- **Halaman baru di area yang sudah ada** (superadmin atau tenant) → **jangan bikin entry baru**. Pakai `@vite(['resources/css/app.css', 'resources/js/areas/{area}.js'])` yang sama seperti halaman lain di area itu.
- **Area baru yang benar-benar berbeda karakter** (misalnya nanti ada portal khusus orang tua, atau API-only tanpa Blade) → baru bikin entry baru di `resources/js/areas/`, dan daftarkan di `input` pada `vite.config.js`.
- **Komponen yang dipakai lintas area** (mis. util formatting tanggal, helper fetch API) → taruh di file terpisah (`resources/js/shared/` misalnya) dan di-import dari entry area yang butuh — jangan copy-paste kode yang sama ke beberapa entry.

## 4. Blade — Cara Pakai

```blade
{{-- Halaman tenant biasa --}}
@vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])

{{-- Halaman superadmin --}}
@vite(['resources/css/app.css', 'resources/js/areas/superadmin.js'])

{{-- Layar kiosk device absensi --}}
@vite(['resources/css/app.css', 'resources/js/areas/kiosk.js'])
```

Alpine dipakai langsung di markup tanpa import tambahan di Blade, selama entry area yang di-load sudah termasuk `alpine.js` (superadmin & tenant sudah; kiosk sengaja tidak, lihat komentar di file-nya):

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Konten...</div>
</div>
```

## 5. Kiosk Berbeda Sengaja

Entry `kiosk.js` **tidak** meng-import Alpine secara default. Layar kiosk (RFID/QR/Face di lokasi fisik sekolah) idealnya sesederhana dan seringan mungkin — nyala berjam-jam, dan interaksinya lebih ke device API (kamera, scanner) daripada UI kompleks. Kalau modul Absensi mulai butuh interaktivitas ringan di kiosk, evaluasi dulu sebelum nambah Alpine ke sana — vanilla JS mungkin sudah cukup.

## 6. Menjalankan

```bash
docker exec -it eduzone_vite npm run dev     # dev, hot reload semua entry
docker exec eduzone_vite npm run build       # build production, hasil ke public/build
```

Vite otomatis meng-compile semua entry yang didaftarkan di `vite.config.js` sekaligus — tidak perlu jalankan terpisah per area.
