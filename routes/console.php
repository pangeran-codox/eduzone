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
