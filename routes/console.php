<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Horizon metrics snapshot — jalankan setiap 5 menit
// Ini yang mengisi grafik di Horizon dashboard
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// absensi:aggregate-daily SENGAJA DINONAKTIFKAN (bukan dihapus filenya) —
// absensi-gateway (Go) sudah punya agregasi attendance_events ->
// attendance_daily sendiri secara otomatis, TERMASUK deteksi status
// Terlambat via late_cutoff_time (lihat docs/status-dan-tugas-laravel.md
// repo gateway, Bagian 1). Command Laravel ini versi yang lebih lemah
// (cuma bisa 'Hadir', tidak pernah 'Terlambat') — kalau jalan bareng
// gateway, job ini berisiko menimpa status 'Terlambat' yang benar dari
// gateway jadi 'Hadir' kapan pun dia jalan belakangan. Command file-nya
// (App\Console\Commands\Absensi\AggregateAttendanceDaily) sengaja
// dibiarkan ada untuk referensi, bukan dihapus.
// Schedule::command('absensi:aggregate-daily')->everyFiveMinutes();

// absensi:sync-daily-to-main TETAP AKTIF — ini mengisi gap yang beneran
// belum ada di gateway ("sync balik" attendance_daily -> student_attendance
// DB utama, lihat docs/status-dan-tugas-laravel.md Bagian 2.3: "belum
// pernah dikirim balik ke database utama Eduzone... belum ada kontraknya
// sama sekali"). Job ini baca attendance_daily APAPUN isinya (baik yang
// diisi gateway maupun sisa data lama), jadi tetap valid dipakai meski
// yang mengisi attendance_daily sekarang cuma gateway.
Schedule::command('absensi:sync-daily-to-main')->everyTenMinutes();