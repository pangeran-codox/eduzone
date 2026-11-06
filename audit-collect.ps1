<#
  audit-collect.ps1 - kumpulkan bahan audit modul Absensi EduZone.

  Jalankan dari root proyek (C:\laragon\www\eduzone):
      powershell -ExecutionPolicy Bypass -File .\audit-collect.ps1

  Kalau folder repo gateway tidak terdeteksi otomatis:
      powershell -ExecutionPolicy Bypass -File .\audit-collect.ps1 -GatewayPath "C:\path\ke\absensi-gateway"

  Sifat skrip:
    - HANYA MEMBACA. Satu-satunya file yang ditulis ke proyek adalah storage\app\audit_db.php
      (sementara, dihapus lagi di akhir).
    - Secret di-mask (.env, config, docker, env container, log). File kode tidak diubah.
    - Query database hanya menghasilkan hitungan, ringkasan, dan UUID. Tidak ada nama atau foto orang.
  Hasil: audit-absensi-<waktu>.zip di folder proyek. Unggah zip itu ke chat.
#>
param(
    [string]$ProjectRoot = (Get-Location).Path,
    [string]$GatewayPath = ''
)

$ErrorActionPreference = 'Continue'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$out = Join-Path $ProjectRoot ("audit-absensi-" + $stamp)
New-Item -ItemType Directory -Path $out -Force | Out-Null

# ---------------------------------------------------------------- helper

function Mask-Line([string]$l) {
    if ($l -match '^\s*(#|//|\*|/\*)') { return $l }
    if ($l -match '(?i)(secret|token|passw|pwd|api[_-]?key|app[_-]?key|private[_-]?key|credential|salt|dsn|signature)') {
        if ($l -match '^(\s*[A-Za-z0-9_.\-]+\s*=)') { return ($Matches[1] + '***MASKED***') }
        if ($l -match '^(.*?(=>|:)\s*)') { return ($Matches[1] + '***MASKED***') }
        return '***MASKED LINE***'
    }
    return ($l -replace '(://[^:/\s@]+:)[^@\s/]+(@)', '$1***MASKED***$2')
}

function Save-Masked([string]$src, [string]$dst) {
    if (-not (Test-Path -LiteralPath $src)) { return }
    New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null
    Get-Content -LiteralPath $src -Encoding UTF8 | ForEach-Object { Mask-Line ([string]$_) } |
        Out-File -FilePath $dst -Encoding utf8
}

function Copy-Dir([string]$rel) {
    $src = Join-Path $ProjectRoot $rel
    if (Test-Path -LiteralPath $src) {
        $dst = Join-Path $out ('laravel\' + $rel)
        New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null
        Copy-Item -LiteralPath $src -Destination $dst -Recurse -Force
    }
}

function Copy-One([string]$rel) {
    $src = Join-Path $ProjectRoot $rel
    if (Test-Path -LiteralPath $src) {
        $dst = Join-Path $out ('laravel\' + $rel)
        New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null
        Copy-Item -LiteralPath $src -Destination $dst -Force
    }
}

# Jalankan perintah, simpan outputnya. $mask = true untuk output yang mungkin memuat secret.
function Run([string]$name, [scriptblock]$sb, [bool]$mask = $false) {
    $path = Join-Path $out $name
    New-Item -ItemType Directory -Force -Path (Split-Path $path) | Out-Null
    try {
        $lines = & $sb 2>&1 | ForEach-Object { "$_" }
        if ($mask) { $lines = $lines | ForEach-Object { Mask-Line ([string]$_) } }
        $lines | Out-File -FilePath $path -Encoding utf8
    } catch {
        ("ERROR: " + $_.Exception.Message) | Out-File -FilePath $path -Encoding utf8
    }
}

Write-Host "[1/7] Menyalin kode Laravel..."

# ---------------------------------------------------------------- kode Laravel

foreach ($d in @(
    'app\Http\Controllers\Tenant', 'app\Http\Controllers\Superadmin', 'app\Http\Controllers\Api',
    'app\Http\Controllers\Kiosk', 'app\Http\Controllers\Auth', 'app\Http\Middleware',
    'app\Console', 'app\Services', 'app\Exports', 'app\Multitenancy', 'app\Models',
    'app\Providers', 'app\Policies', 'app\Jobs', 'app\Events', 'app\Listeners', 'app\Observers',
    'routes', 'database\seeders',
    'resources\views\tenant', 'resources\views\superadmin', 'resources\views\kiosk',
    'resources\js'
)) { Copy-Dir $d }

# file top-level di app\Http\Controllers (PersonPhotoController, Controller, dst)
$ctrlTop = Join-Path $ProjectRoot 'app\Http\Controllers'
if (Test-Path $ctrlTop) {
    Get-ChildItem -LiteralPath $ctrlTop -File -Filter *.php | ForEach-Object {
        Copy-One ('app\Http\Controllers\' + $_.Name)
    }
}

# migration yang berkaitan dengan absensi
$migDir = Join-Path $ProjectRoot 'database\migrations'
if (Test-Path $migDir) {
    Get-ChildItem -LiteralPath $migDir -File -Filter *.php | Where-Object {
        $_.Name -match 'absen|attendance|_ref|credential|device|face_|sync_log|substitution|student_attendance|teacher_attendance|manual_' -or
        (Select-String -LiteralPath $_.FullName -Pattern 'pgsql_absensi' -Quiet)
    } | ForEach-Object { Copy-One ('database\migrations\' + $_.Name) }
}

# test yang berkaitan
$testDir = Join-Path $ProjectRoot 'tests'
if (Test-Path $testDir) {
    Get-ChildItem -LiteralPath $testDir -Recurse -File -Filter *.php | Where-Object {
        $_.Name -match 'Absen|Attendance|Credential|Substitution|Sync|Kiosk|Gateway'
    } | ForEach-Object {
        $rel = $_.FullName.Substring($ProjectRoot.Length).TrimStart('\')
        Copy-One $rel
    }
    Get-ChildItem -LiteralPath $testDir -Recurse -File | ForEach-Object { $_.FullName.Substring($ProjectRoot.Length).TrimStart('\') } |
        Out-File -FilePath (Join-Path $out 'laravel\tests-file-list.txt') -Encoding utf8
}

foreach ($f in @('composer.json', 'package.json', 'vite.config.js', 'docker-compose.yml')) { Copy-One $f }

Write-Host "[2/7] Menyalin konfigurasi (secret di-mask)..."

# ---------------------------------------------------------------- konfigurasi ter-mask

Save-Masked (Join-Path $ProjectRoot '.env') (Join-Path $out 'laravel\env-masked.txt')
foreach ($c in @('services.php', 'database.php', 'broadcasting.php', 'reverb.php', 'queue.php', 'session.php', 'app.php', 'cache.php', 'multitenancy.php', 'auth.php')) {
    Save-Masked (Join-Path $ProjectRoot ('config\' + $c)) (Join-Path $out ('laravel\config\' + $c))
}
foreach ($c in @('build.ps1', 'Dockerfile', '.env.example')) {
    Save-Masked (Join-Path $ProjectRoot $c) (Join-Path $out ('laravel\' + $c))
}
Get-ChildItem -LiteralPath $ProjectRoot -File -Filter 'docker-compose*.yml' -ErrorAction SilentlyContinue | ForEach-Object {
    Save-Masked $_.FullName (Join-Path $out ('laravel\' + $_.Name))
}
$dockerDir = Join-Path $ProjectRoot 'docker'
if (Test-Path $dockerDir) {
    Get-ChildItem -LiteralPath $dockerDir -Recurse -File | Where-Object { $_.Length -lt 200KB } | ForEach-Object {
        $rel = $_.FullName.Substring($ProjectRoot.Length).TrimStart('\')
        Save-Masked $_.FullName (Join-Path $out ('laravel\' + $rel))
    }
}

Write-Host "[3/7] Mencatat metadata, git, dan file yang baru berubah..."

# ---------------------------------------------------------------- metadata

Run 'meta\docker-ps.txt' { docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}' }
Run 'meta\docker-networks.txt' { docker inspect -f '{{.Name}}: {{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' eduzone_app eduzone_nginx eduzone_queue eduzone_scheduler eduzone_vite reverb npm postgres redis absensi-gateway-absensi-gateway-1 }
Run 'meta\git-branch.txt' { git -C $ProjectRoot branch --show-current }
Run 'meta\git-log.txt' { git -C $ProjectRoot log --date=iso --pretty=format:'%h %ad %an %s' -n 80 }
Run 'meta\git-status.txt' { git -C $ProjectRoot status --short }
Run 'meta\clock-laravel-container.txt' { docker exec eduzone_app date }
Run 'meta\clock-php.txt' { docker exec eduzone_app php -r 'echo date_default_timezone_get().PHP_EOL.date(DATE_ATOM).PHP_EOL;' }
Run 'meta\clock-gateway-container.txt' { docker exec absensi-gateway-absensi-gateway-1 date }
Run 'meta\gateway-env-masked.txt' { docker exec absensi-gateway-absensi-gateway-1 env } $true

$modList = foreach ($d in @('app', 'routes', 'resources\views', 'database\migrations', 'config')) {
    $p = Join-Path $ProjectRoot $d
    if (Test-Path $p) { Get-ChildItem -LiteralPath $p -Recurse -File }
}
$modList | Sort-Object LastWriteTime -Descending | Select-Object -First 150 | ForEach-Object {
    ($_.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss') + '  ' + $_.Length.ToString().PadLeft(8) + '  ' + $_.FullName.Substring($ProjectRoot.Length).TrimStart('\'))
} | Out-File -FilePath (Join-Path $out 'meta\files-recently-modified.txt') -Encoding utf8

Write-Host "[4/7] Mengambil info runtime Laravel..."

# ---------------------------------------------------------------- runtime Laravel

Run 'runtime\routes.json' { docker exec eduzone_app php artisan route:list --json --except-vendor }
Run 'runtime\schedule-list.txt' { docker exec eduzone_app php artisan schedule:list }
Run 'runtime\migrate-status.txt' { docker exec eduzone_app php artisan migrate:status }
Run 'runtime\about.txt' { docker exec eduzone_app php artisan about --only=environment,cache,drivers }
Run 'runtime\composer-direct.txt' { docker exec eduzone_app composer show --direct --no-ansi }

$logDir = Join-Path $ProjectRoot 'storage\logs'
if (Test-Path $logDir) {
    $lastLog = Get-ChildItem -LiteralPath $logDir -Filter *.log | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($lastLog) {
        Get-Content -LiteralPath $lastLog.FullName -Tail 300 | ForEach-Object { Mask-Line ([string]$_) } |
            Out-File -FilePath (Join-Path $out 'runtime\laravel-log-tail.txt') -Encoding utf8
    }
}

Write-Host "[5/7] Mengambil log container dan konfigurasi nginx..."

# ---------------------------------------------------------------- log dan nginx

Run 'runtime\log-gateway.txt'   { docker logs --tail 300 absensi-gateway-absensi-gateway-1 } $true
Run 'runtime\log-reverb.txt'    { docker logs --tail 80 reverb } $true
Run 'runtime\log-scheduler.txt' { docker logs --tail 200 eduzone_scheduler } $true
Run 'runtime\log-queue.txt'     { docker logs --tail 80 eduzone_queue } $true
Run 'runtime\log-nginx.txt'     { docker logs --tail 80 eduzone_nginx } $true
Run 'runtime\nginx-default.conf.txt' { docker exec eduzone_nginx cat /etc/nginx/conf.d/default.conf } $true
Run 'runtime\npm-proxy-hosts.txt' { docker exec npm sh -c 'cat /data/nginx/proxy_host/*.conf' } $true

Write-Host "[6/7] Menyalin kode gateway (Go)..."

# ---------------------------------------------------------------- gateway

$gw = $GatewayPath
if (-not $gw) {
    try {
        $labels = docker inspect absensi-gateway-absensi-gateway-1 --format '{{json .Config.Labels}}' | ConvertFrom-Json
        $gw = $labels.'com.docker.compose.project.working_dir'
    } catch { }
}
$gdst = Join-Path $out 'gateway'
New-Item -ItemType Directory -Force -Path $gdst | Out-Null
if ($gw -and (Test-Path -LiteralPath $gw)) {
    "Sumber gateway: $gw" | Out-File -FilePath (Join-Path $gdst '_SOURCE.txt') -Encoding utf8
    Get-ChildItem -Path $gw -Recurse -File -Include *.go,*.sql,*.md,go.mod,Dockerfile*,*.yml,*.yaml,*.sh,.env.example |
        Where-Object { $_.FullName -notmatch '\\(\.git|vendor|node_modules|bin|tmp)\\' -and $_.Length -lt 500KB } |
        ForEach-Object {
            $rel = $_.FullName.Substring($gw.Length).TrimStart('\')
            $dst = Join-Path $gdst $rel
            if ($_.Extension -in '.yml', '.yaml' -or $_.Name -like '.env*' -or $_.Name -like 'Dockerfile*') {
                Save-Masked $_.FullName $dst
            } else {
                New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null
                Copy-Item -LiteralPath $_.FullName -Destination $dst -Force
            }
        }
} else {
    "Folder repo gateway tidak terdeteksi. Jalankan ulang dengan -GatewayPath, atau unggah folder gateway terpisah." |
        Out-File -FilePath (Join-Path $gdst 'NOT_FOUND.txt') -Encoding utf8
}

Write-Host "[7/7] Menjalankan probe database (hanya membaca)..."

# ---------------------------------------------------------------- probe database

$phpProbe = @'
<?php
// audit_db.php - dibuat sementara oleh audit-collect.ps1. HANYA membaca. Output JSON ke stdout.
use Illuminate\Support\Facades\DB;

$A = DB::connection('pgsql_absensi');
$M = DB::connection();
$out = [];

$probe = function (string $name, callable $fn) use (&$out) {
    try {
        $out[$name] = $fn();
    } catch (\Throwable $e) {
        $out[$name] = ['ERROR' => substr($e->getMessage(), 0, 300)];
    }
};

$rows = function ($conn, string $sql) {
    return array_map(fn ($r) => (array) $r, $conn->select($sql));
};

$probe('meta', function () use ($A, $M, $rows) {
    return [
        'app_timezone' => config('app.timezone'),
        'php_now' => now()->toIso8601String(),
        'php_today' => now()->toDateString(),
        'pg_main' => $rows($M, "select now()::text as now, current_setting('TimeZone') as tz, current_database() as db"),
        'pg_absensi' => $rows($A, "select now()::text as now, current_setting('TimeZone') as tz, current_database() as db"),
    ];
});

$probe('A_table_counts', function () use ($A, $rows) {
    $res = [];
    foreach ($rows($A, "select table_name from information_schema.tables where table_schema = 'public' and table_type = 'BASE TABLE' order by 1") as $t) {
        $name = $t['table_name'];
        try { $res[$name] = $A->table($name)->count(); } catch (\Throwable $e) { $res[$name] = 'ERR'; }
    }
    return $res;
});

$probe('A_columns', fn () => $rows($A, "select table_name, column_name, data_type, is_nullable, column_default from information_schema.columns where table_schema = 'public' order by table_name, ordinal_position"));
$probe('A_constraints', fn () => $rows($A, "select conrelid::regclass::text as tbl, conname, contype, pg_get_constraintdef(oid) as def from pg_constraint where connamespace = 'public'::regnamespace order by 1, 2"));
$probe('A_indexes', fn () => $rows($A, "select tablename, indexname, indexdef from pg_indexes where schemaname = 'public' order by 1, 2"));

$mainRel = "'student_attendance','teacher_attendance','schedules','teachers','students','staff','users','schools','teaching_substitutions','homeroom_assignments','classes'";
$probe('M_columns', fn () => $rows($M, "select table_name, column_name, data_type, is_nullable from information_schema.columns where table_schema = 'public' and table_name in ($mainRel) order by table_name, ordinal_position"));
$probe('M_constraints', fn () => $rows($M, "select conrelid::regclass::text as tbl, conname, contype, pg_get_constraintdef(oid) as def from pg_constraint where connamespace = 'public'::regnamespace and conrelid::regclass::text in ($mainRel) order by 1, 2"));
$probe('M_indexes', fn () => $rows($M, "select tablename, indexname, indexdef from pg_indexes where schemaname = 'public' and tablename in ($mainRel) order by 1, 2"));
$probe('M_table_counts', function () use ($M) {
    $res = [];
    foreach (['users', 'schools', 'students', 'teachers', 'staff', 'classes', 'schedules', 'homeroom_assignments', 'student_attendance', 'teacher_attendance', 'teaching_substitutions'] as $t) {
        try { $res[$t] = $M->table($t)->count(); } catch (\Throwable $e) { $res[$t] = 'ERR'; }
    }
    return $res;
});

// ---- probe kualitas data: database absensi
$sqlA = [
    'events_summary' => "select method, event_type, is_valid, count(*) as n, min(recorded_at)::text as first_at, max(recorded_at)::text as last_at from attendance_events group by 1, 2, 3 order by 1, 2, 3",
    'events_recent' => "select id, method, event_type, recorded_at::text as recorded_at, pg_typeof(recorded_at)::text as recorded_at_type, (schedule_id is not null) as has_schedule, (device_id is not null) as has_device, is_valid, flagged_reason, (row_hash is not null) as has_hash, (signature is not null) as has_signature from attendance_events order by recorded_at desc limit 10",
    'events_hour_histogram' => "select extract(hour from recorded_at)::int as hour_as_stored, count(*) as n from attendance_events group by 1 order by 1",
    'events_hash_coverage' => "select method, count(*) as n, count(row_hash) as with_hash, count(signature) as with_signature from attendance_events group by 1 order by 1",
    'events_orphan_people' => "select count(*) as n from attendance_events e where e.person_id is not null and not exists (select 1 from people_ref p where p.person_id = e.person_id and p.person_type = e.person_type)",
    'events_null_person' => "select count(*) as n from attendance_events where person_id is null",
    'events_flagged' => "select flagged_reason, count(*) as n from attendance_events where flagged_reason is not null group by 1 order by 2 desc limit 20",
    'events_schedule_coverage' => "select count(*) as total, count(schedule_id) as with_schedule, count(device_id) as with_device from attendance_events",
    'daily_by_date' => "select date::text as day, person_type, status, count(*) as n from attendance_daily where date >= current_date - 14 group by 1, 2, 3 order by 1, 2, 3",
    'daily_status_checkin' => "select status, count(*) as n, count(first_check_in) as with_check_in, count(notes) as with_notes, count(*) filter (where has_anomaly) as anomalies from attendance_daily group by 1 order by 1",
    'daily_checkin_hour' => "select extract(hour from first_check_in)::int as hour_as_stored, count(*) as n from attendance_daily where first_check_in is not null group by 1 order by 1",
    'daily_orphan_people' => "select count(*) as n from attendance_daily d where not exists (select 1 from people_ref p where p.person_id = d.person_id and p.person_type = d.person_type)",
    'daily_vs_events_14d' => "select e.d::text as day, e.people as event_people, coalesce(x.n, 0) as daily_rows from (select recorded_at::date as d, count(distinct person_id) as people from attendance_events where person_id is not null and recorded_at >= now() - interval '14 days' group by 1) e left join (select date as d, count(*) as n from attendance_daily group by 1) x on x.d = e.d order by 1",
    'people_summary' => "select person_type, is_active, count(*) as n, count(class_id) as with_class, max(synced_at)::text as last_synced from people_ref group by 1, 2 order by 1, 2",
    'schools_ref' => "select school_id, (latitude is not null and longitude is not null) as has_gps, geofence_radius_meters, late_cutoff_time, is_active from schools_ref",
    'schedules_summary' => "select day_of_week, is_active, count(*) as n, max(synced_at)::text as last_synced from schedules_ref group by 1, 2 order by 1, 2",
    'schedules_orphan_teacher' => "select count(*) as n from schedules_ref s where not exists (select 1 from people_ref p where p.person_id = s.teacher_id and p.person_type = 'teacher')",
    'devices' => "select id, school_id, device_type, is_active, last_seen_at::text as last_seen_at, (default_class_id is not null) as has_default_class from devices order by last_seen_at desc nulls last",
    'credentials_summary' => "select person_type, method, is_active, count(*) as n from credentials group by 1, 2, 3 order by 1, 2, 3",
    'credentials_multi_active' => "select person_type, method, count(*) as persons_with_multiple_active from (select person_id, person_type, method from credentials where is_active group by 1, 2, 3 having count(*) > 1) t group by 1, 2",
    'credentials_orphan_people' => "select count(*) as n from credentials c where not exists (select 1 from people_ref p where p.person_id = c.person_id and p.person_type = c.person_type)",
    'credentials_inconsistent' => "select count(*) as n from credentials where (is_active and revoked_at is not null) or (not is_active and revoked_at is null)",
    'period_count' => "select count(*) as n from attendance_period",
    'overrides_summary' => "select person_type, status, count(*) as n from manual_attendance_overrides group by 1, 2 order by 1, 2",
    'override_mismatch' => "select count(*) as n from manual_attendance_overrides o join attendance_daily d on d.school_id = o.school_id and d.person_id = o.person_id and d.person_type = o.person_type and d.date = o.date where d.status <> o.status",
    'override_missing_daily' => "select count(*) as n from manual_attendance_overrides o where not exists (select 1 from attendance_daily d where d.school_id = o.school_id and d.person_id = o.person_id and d.person_type = o.person_type and d.date = o.date)",
];
foreach ($sqlA as $name => $sql) {
    $probe('A_' . $name, function () use ($A, $rows, $sql) { return $rows($A, $sql); });
}

$probe('A_sync_log', function () {
    $m = new \App\Models\Absensi\SyncLog();
    return [
        'connection' => $m->getConnectionName(),
        'table' => $m->getTable(),
        'by_status' => $m->getConnection()->table($m->getTable())
            ->select('status', DB::raw('count(*) as n'), DB::raw('max(attempted_at) as last_attempt'))
            ->groupBy('status')->get(),
        'top_errors' => $m->getConnection()->table($m->getTable())
            ->where('status', 'failed')
            ->select('error_message', DB::raw('count(*) as n'))
            ->groupBy('error_message')->orderByDesc('n')->limit(10)->get(),
    ];
});

// ---- probe kualitas data: database utama
$sqlM = [
    'student_att_by_date' => "select date::text as day, status, count(*) as n from student_attendance where date >= current_date - 14 group by 1, 2 order by 1, 2",
    'teacher_att_by_date' => "select date::text as day, status, count(*) as n from teacher_attendance where date >= current_date - 14 group by 1, 2 order by 1, 2",
    'student_att_duplicates' => "select count(*) as groups_with_duplicates from (select school_id, student_id, date from student_attendance group by 1, 2, 3 having count(*) > 1) t",
    'teacher_att_duplicates' => "select count(*) as groups_with_duplicates from (select school_id, teacher_id, date from teacher_attendance group by 1, 2, 3 having count(*) > 1) t",
    'student_att_orphans' => "select count(*) as n from student_attendance a where not exists (select 1 from students s where s.id = a.student_id)",
    'student_att_status_values' => "select status, count(*) as n from student_attendance group by 1 order by 1",
    'teacher_att_status_values' => "select status, count(*) as n from teacher_attendance group by 1 order by 1",
    'users_roles' => "select role, count(*) as n from users group by 1 order by 1",
    'teacher_accounts_without_row' => "select u.role, count(*) as n from users u where u.role in ('guru_mapel', 'wali_kelas') and not exists (select 1 from teachers t where t.user_id = u.id) group by 1",
    'staff_accounts_without_row' => "select u.role, count(*) as n from users u where u.role in ('kepsek', 'tu', 'kurikulum', 'kesiswaan', 'bk', 'toolman') and not exists (select 1 from staff s where s.user_id = u.id) group by 1",
    'student_accounts_without_row' => "select count(*) as n from users u where u.role = 'siswa' and not exists (select 1 from students s where s.user_id = u.id)",
    'teachers_summary' => "select count(*) as total, count(user_id) as with_user, count(*) filter (where is_active) as active from teachers",
    'students_summary' => "select count(*) as total, count(*) filter (where status = 'aktif') as active, count(*) filter (where status = 'aktif' and class_id is null) as active_without_class from students",
    'staff_summary' => "select count(*) as total, count(*) filter (where is_active) as active from staff",
    'schedules_quality' => "select count(*) as total, count(*) filter (where is_active) as active, count(*) filter (where teacher_id is null) as without_teacher, count(*) filter (where class_id is null) as without_class, count(*) filter (where day not in ('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')) as invalid_day from schedules",
    'schedules_by_day' => "select day, count(*) as n from schedules where is_active group by 1 order by 2 desc",
    'schedules_teacher_overlaps' => "select count(*) as n from schedules a join schedules b on a.teacher_id = b.teacher_id and a.day = b.day and a.id < b.id and a.is_active and b.is_active and a.start_time < b.end_time and a.end_time > b.start_time",
    'schools' => "select id, (latitude is not null and longitude is not null) as has_gps, geofence_radius_meters, late_cutoff_time, is_active from schools",
    'substitutions' => "select count(*) as total, count(*) filter (where date >= current_date) as today_or_later from teaching_substitutions",
];
foreach ($sqlM as $name => $sql) {
    $probe('M_' . $name, function () use ($M, $rows, $sql) { return $rows($M, $sql); });
}

// ---- probe lintas database
$probe('X_sync_gap', function () use ($A, $M, $rows) {
    $res = [];
    foreach (['student' => 'student_attendance', 'teacher' => 'teacher_attendance'] as $type => $tbl) {
        $a = collect($rows($A, "select date::text as d, count(*) as n from attendance_daily where person_type = '$type' and date >= current_date - 14 group by 1"))->pluck('n', 'd');
        $m = collect($rows($M, "select date::text as d, count(*) as n from $tbl where date >= current_date - 14 group by 1"))->pluck('n', 'd');
        foreach ($a->keys()->merge($m->keys())->unique()->sort() as $d) {
            $res[$type][$d] = ['attendance_daily' => $a[$d] ?? 0, $tbl => $m[$d] ?? 0];
        }
    }
    return $res;
});

$probe('X_people_counts', function () use ($A, $M, $rows) {
    return [
        'main_active' => [
            'students' => $M->table('students')->where('status', 'aktif')->count(),
            'teachers' => $M->table('teachers')->where('is_active', true)->count(),
            'staff' => $M->table('staff')->where('is_active', true)->count(),
        ],
        'people_ref_active' => $rows($A, "select person_type, count(*) as n from people_ref where is_active group by 1 order by 1"),
    ];
});

echo json_encode($out, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), PHP_EOL;
'@

$probePath = Join-Path $ProjectRoot 'storage\app\audit_db.php'
try {
    [System.IO.File]::WriteAllText($probePath, $phpProbe, (New-Object System.Text.UTF8Encoding($false)))
    Run 'runtime\db-probe.json' { docker exec eduzone_app php artisan tinker '--execute=require(base_path(''storage/app/audit_db.php''));' }
} finally {
    if (Test-Path -LiteralPath $probePath) { Remove-Item -LiteralPath $probePath -Force }
}

# ---------------------------------------------------------------- zip

$zip = $out + '.zip'
Compress-Archive -Path (Join-Path $out '*') -DestinationPath $zip -Force
$fileCount = (Get-ChildItem -LiteralPath $out -Recurse -File | Measure-Object).Count
$sizeMb = [math]::Round((Get-Item -LiteralPath $zip).Length / 1MB, 2)

Write-Host ""
Write-Host "Selesai. $fileCount file, $sizeMb MB."
Write-Host "Zip : $zip"
Write-Host "Folder (boleh dihapus setelah zip dikirim): $out"
Write-Host ""
Write-Host "Sebelum mengunggah, buka runtime\db-probe.json dan pastikan tidak kosong/ERROR total."
Write-Host "Secret sudah di-mask, tapi tetap cek sekilas laravel\env-masked.txt kalau ragu."
