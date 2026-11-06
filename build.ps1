#Requires -Version 5.1

<#
.SYNOPSIS
Build script aman untuk project EduZone (Laravel + Vite + Docker multi-stage).

.DESCRIPTION
Script build end-to-end untuk EduZone SaaS:
  * Validasi prasyarat (PHP >= 8.2, Composer, Node >= 20, Docker opsional)
  * Copy .env dari .env.example jika belum ada + generate APP_KEY otomatis
  * Composer install (--no-dev --optimize-autoloader untuk Production)
  * NPM ci (frozen lockfile) / npm install + Vite build (public/build/)
  * Laravel optimize (config/route/view/event cache di Prod; optimize:clear di Dev)
  * Storage:link, ensure storage directories writable
  * Migrate / migrate:fresh / seed (Production butuh konfirmasi interaktif)
  * Docker image build (multi-stage target development/production)
  * Docker compose up -d (dengan --force-recreate --build opsional)
  * Image push ke registry (untuk deploy Docker Swarm ke production)

Semua step destructive (migrate:fresh, production push, dll) memiliki konfirmasi
interaktif, kecuali diberi flag -Force (untuk CI).

.PARAMETER Mode
Build mode: 'Development' (default, dengan dev-deps, cache cleared setiap build)
atau 'Production' (--no-dev, autoload optimize, env critical check, caches di-cache).

.PARAMETER SkipComposer
Skip composer install & dump-autoload step.

.PARAMETER SkipNpm
Skip npm install & vite build step (misal hanya ubah kode backend tanpa asset).

.PARAMETER SkipOptimize
Skip Laravel optimize step (config:cache, route:cache dkk di Production;
optimize:clear di Development).

.PARAMETER Migrate
Jalankan php artisan migrate setelah langkah build selesai.
Di Production auto pakai --force, tapi pastikan .env DB production benar.

.PARAMETER Seed
Jalankan php artisan db:seed setelah migrate (harus dipakai bareng -Migrate atau -Fresh).

.PARAMETER Fresh
Jalankan migrate:fresh (DROP SEMUA TABEL lalu re-run migration).
DESTRUKTIF -- ada konfirmasi, terutama di Production.

.PARAMETER Clean
Hapus vendor/, node_modules/, public/build/, dan file bootstrap/cache/*.php
SEBELUM memulai build (fresh reproducible build).

.PARAMETER DockerBuild
Build Docker image sesuai target stage (development vs production) via Dockerfile.
Catatan: build stage production compile ext-grpc dari source, bisa 20-60 menit.

.PARAMETER DockerUp
Jalankan docker compose up -d setelah semua step selesai.
(Tidak otomatis build image kecuali dipasangkan dengan -DockerBuild atau -DockerRecreate).

.PARAMETER DockerRecreate
Jalankan docker compose up -d --force-recreate --build (berguna untuk restart stack
bersih dengan rebuild image).

.PARAMETER Tag
Tag Docker image (string versi, mis. 'v1.2.3' atau '2026.10.03').
Jika -Push aktif dan -Tag kosong -> script akan PROMPT interaktif minta versi,
dengan opsi auto-increment patch dari git tag terbaru (jika ada).

.PARAMETER Registry
Prefix registry untuk Docker image push, mis. 'ghcr.io/username', 'docker.io/namamu',
atau registry pribadi 'registry.acme.co.id/apps'.
Image akan di-tag sebagai <Registry>/eduzone-app:<Tag> + :latest alias.
*LEBIH MUDAH:* pakai -DockerHub <username> untuk Docker Hub (default registry docker.io).

.PARAMETER DockerHub
Shortcut untuk push ke Docker Hub. Isi dengan Docker Hub username (mis. -DockerHub maikel).
Script otomatis set -Registry = 'docker.io/<username>', cek login Docker Hub,
dan auto-tag dengan <username>/eduzone-app:<Tag> (tanpa perlu docker.io/ prefix).

.PARAMETER Push
Push image ke registry (butuh salah satu: -Registry ATAU -DockerHub).
Kalau -DockerBuild tidak aktif, script assume image sudah dibuild sebelumnya
dengan tag yang SAMA PERSIS (cek dulu via docker image ls).

.PARAMETER Release
Shortcut ONE-COMMAND untuk production release:
  * Auto-set -Mode Production
  * Auto-set -DockerBuild
  * Auto-set -Push
  * Kalau -Tag kosong -> prompt versi + semver increment dari latest tag
  * Kalau registry belum diset -> prompt -DockerHub / -Registry
Tujuan: cukup ketik `.\build.ps1 -Release` lalu jawab 2-3 pertanyaan, selesai.

.PARAMETER Bump
Digunakan BERSAMA -Release (atau -Push tanpa -Tag) untuk auto-increment semver
tanpa prompt interaktif. Pilihan: 'Major' | 'Minor' | 'Patch' | 'None' (default prompt).
Contoh: latest tag = v1.2.5 -> -Bump Patch -> v1.2.6; -Bump Minor -> v1.3.0; -Bump Major -> v2.0.0.

.PARAMETER Force
Skip SEMUA konfirmasi interaktif (migrate:fresh, production dengan missing env vars, dll).
Gunakan HANYA untuk CI/CD pipeline, NEVER untuk manual production deploy.

.PARAMETER TimeoutSeconds
Timeout (detik) per command eksternal. Default 1800 (30 menit) — dinaikkan karena
compile ext-grpc/protobuf di Docker bisa makan waktu lama.

.PARAMETER IgnorePlatformReq
Array of platform requirements untuk di-ignore oleh Composer.
DEFAULT OTOMATIS DI ISI WINDOWS: @('ext-pcntl') karena ext-pcntl (Process Control)
hanya ada di PHP Linux, TIDAK PERNAH ada untuk PHP Windows build (Laravel Horizon
membutuhkannya, tapi aman di-ignore untuk step build lokal Windows — Docker image
production pakai PHP Linux yang ext-pcntl aktif).
Contoh tambahan jika ada extension lain yang ingin diskip:
  -IgnorePlatformReq @('ext-pcntl','ext-posix','ext-sockets')

.EXAMPLE
.\build.ps1 -Mode Development -Migrate -Seed
Build Development biasa + jalankan migration & seeder (user interaktif konfirmasi kalau ada destructive ops).

.EXAMPLE
.\build.ps1 -Clean -Mode Production -SkipOptimize
Fresh reproducible build Production tanpa cache optimize step (berguna untuk staging smoke test).

.EXAMPLE
.\build.ps1 -Mode Production -DockerBuild -Tag v1.2.0 -Registry ghcr.io/acme -Push
Build image production, tag ghcr.io/acme/eduzone-app:v1.2.0 + :latest, push ke registry.

.EXAMPLE
.\build.ps1 -Mode Development -DockerBuild -DockerRecreate -Migrate
(Local dev) Build dev image, recreate all containers, then run migrate inside container context.

.EXAMPLE
.\build.ps1 -Mode Production -Migrate -Force -TimeoutSeconds 3600
Production build + migrate tanpa konfirmasi, timeout dinaikkan ke 60 menit (untuk CI runner lambat).

.EXAMPLE
.\build.ps1 -DockerHub maikel -Tag v1.3.0 -Mode Production -DockerBuild -Push
Push ke Docker Hub user 'maikel' -- image jadi maikel/eduzone-app:v1.3.0 + maikel/eduzone-app:latest.
(Pertama kali jalankan `docker login` dulu ya.)

.EXAMPLE
.\build.ps1 -Release
ONE-COMMAND release production. Script prompt: tag versi (dengan semver suggestion dari git),
registry / Docker Hub username -> lalu build + tag + push OTOMATIS.

.EXAMPLE
.\build.ps1 -Release -DockerHub maikel -Bump Patch -Force
Full CI-style release tanpa prompt: auto-bump patch dari latest git tag, build production,
push ke maikel/eduzone-app:<new_ver> + :latest ke Docker Hub.

.NOTES
File Path : build.ps1
Author    : EduZone Engineering
Requires  : PowerShell 5.1+ (Windows), PHP 8.2+, Composer, Node 20+, Docker Desktop (opsional)
#>

[CmdletBinding(SupportsShouldProcess = $true, ConfirmImpact = 'Medium', DefaultParameterSetName = 'Default')]
param(
    [ValidateSet('Development', 'Production')]
    [string]$Mode = 'Development',

    [switch]$SkipComposer,
    [switch]$SkipNpm,
    [switch]$SkipOptimize,

    [switch]$Migrate,
    [switch]$Seed,
    [switch]$Fresh,

    [switch]$Clean,

    [switch]$DockerBuild,
    [switch]$DockerUp,
    [switch]$DockerRecreate,

    [string]$Tag = '',
    [string]$Registry = '',
    [string]$DockerHub = '',
    [switch]$Push,

    [Parameter(ParameterSetName = 'Release')]
    [switch]$Release,

    [ValidateSet('Major', 'Minor', 'Patch', 'None')]
    [string]$Bump = 'None',

    [string[]]$IgnorePlatformReq = @(),

    [switch]$Force,
    [int]$TimeoutSeconds = 5400
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ================================================================
# Pre-processing: Shortcut logic (-Release, -DockerHub resolution)
# ================================================================

if ($Release.IsPresent) {
    if ($Mode -ne 'Production') { $Mode = 'Production' }
    $DockerBuild = $true
    $Push = $true
}

if (-not [string]::IsNullOrWhiteSpace($DockerHub)) {
    if (-not [string]::IsNullOrWhiteSpace($Registry)) {
        Write-Warn "Keduanya -DockerHub dan -Registry diisi. -DockerHub akan OVERRIDE -Registry (prioritas Docker Hub shortcut)."
    }
    $Registry = "docker.io/$($DockerHub.Trim())"
    Write-Verbose "DockerHub shortcut: registry diset = $Registry"
}

# Windows: ext-pcntl dan ext-posix TIDAK PERNAH tersedia untuk PHP CLI build Windows
# (Hanya ada di Linux/Mac PHP). Keduanya dibutuhkan oleh Laravel Horizon untuk
# platform deploy (Linux). Aman di-ignore HANYA untuk build step lokal di Windows,
# karena Docker image production pakai PHP Linux (Alpine) yang ext-pcntl dan ext-posix aktif.
if (($ENV:OS -like '*Windows*') -or ($IsWindows) -or (-not (Get-Variable IsWindows -ErrorAction SilentlyContinue))) {
    $windowsExtsToIgnore = @('ext-pcntl', 'ext-posix')
    foreach ($extName in $windowsExtsToIgnore) {
        if (-not ($IgnorePlatformReq -contains $extName)) {
            [string[]]$IgnorePlatformReq = @($extName) + @($IgnorePlatformReq)
            Write-Verbose "Windows detected: auto inject --ignore-platform-req=$($extName) ke Composer ($extName not available on Windows PHP)"
        }
    }
    Write-Host "  [INFO] Composer ignore platform (Windows auto): $($windowsExtsToIgnore -join ', ')" -ForegroundColor DarkGray
}

# ================================================================
# Helper Functions
# ================================================================

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "[STEP] $Message" -ForegroundColor Cyan
    Write-Host ("-" * [Math]::Min($Message.Length + 10, 80)) -ForegroundColor DarkCyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Message)
    Write-Host "[WARN]  $Message" -ForegroundColor Yellow
}

function Write-ErrorMsg {
    param([string]$Message)
    Write-Host "[ERR] $Message" -ForegroundColor Red
}

function _Quote-Arg {
    param([string]$A)
    if ([string]::IsNullOrEmpty($A)) { return '""' }
    if ($A -match '[\s"]') {
        $escaped = $A -replace '"', '\"'
        return '"' + $escaped + '"'
    }
    return $A
}

function _Resolve-CommandPath {
    param([string]$CmdName)
    try {
        $cmd = Get-Command $CmdName -ErrorAction Stop
        if ($cmd.CommandType -eq 'Application') {
            return [PSCustomObject]@{
                Kind        = 'Exe'
                FullPath    = $cmd.Source
                DisplayName = Split-Path $cmd.Source -Leaf
            }
        }
        if ($cmd.CommandType -eq 'ExternalScript') {
            $ext = [System.IO.Path]::GetExtension($cmd.Source).ToLowerInvariant()
            if ($ext -eq '.ps1') {
                return [PSCustomObject]@{
                    Kind        = 'Ps1'
                    FullPath    = $cmd.Source
                    DisplayName = Split-Path $cmd.Source -Leaf
                }
            }
        }
        # Fallback: get real .bat/.cmd/.exe via -All where commandtype=Application
        $apps = Get-Command $CmdName -All -ErrorAction SilentlyContinue | Where-Object { $_.CommandType -eq 'Application' }
        if ($apps -and $apps.Count -gt 0) {
            $src = $apps[0].Source
            $ext = [System.IO.Path]::GetExtension($src).ToLowerInvariant()
            if ($ext -in '.bat','.cmd') {
                return [PSCustomObject]@{
                    Kind        = 'Bat'
                    FullPath    = $src
                    DisplayName = Split-Path $src -Leaf
                }
            }
            return [PSCustomObject]@{
                Kind        = 'Exe'
                FullPath    = $src
                DisplayName = Split-Path $src -Leaf
            }
        }
        if ($cmd.Source -and (Test-Path $cmd.Source)) {
            $ext = [System.IO.Path]::GetExtension($cmd.Source).ToLowerInvariant()
            if ($ext -in '.bat','.cmd') {
                return [PSCustomObject]@{
                    Kind        = 'Bat'
                    FullPath    = $cmd.Source
                    DisplayName = Split-Path $cmd.Source -Leaf
                }
            }
            return [PSCustomObject]@{
                Kind        = 'Exe'
                FullPath    = $cmd.Source
                DisplayName = Split-Path $cmd.Source -Leaf
            }
        }
        return $null
    } catch {
        return $null
    }
}

function Invoke-SafeCommand {
    param(
        [Parameter(Mandatory)]
        [string]$Command,
        [string[]]$Arguments = @(),
        [string]$WorkingDirectory = $PWD.Path,
        [int]$TimeoutSec = $Script:TimeoutSeconds,
        [bool]$Critical = $true
    )

    $quoted = @($Arguments | ForEach-Object { _Quote-Arg $_ })
    $fullCmd = $Command
    if ($quoted.Count -gt 0) {
        $fullCmd += " " + ($quoted -join " ")
    }
    Write-Verbose "-> $fullCmd"

    try {
        $resolve = _Resolve-CommandPath -CmdName $Command
        if (-not $resolve) {
            if ($Critical) {
                throw "Command tidak ditemukan di PATH: $Command"
            } else {
                Write-Verbose "Command tidak ada di PATH (non-critical): $Command"
                return [PSCustomObject]@{
                    ExitCode = -1
                    StdOut   = ''
                    StdErr   = "Command '$Command' not found in PATH"
                }
            }
        }

        Write-Verbose "[Resolve] $Command -> $($resolve.Kind): $($resolve.FullPath)"

        $pinfo = New-Object System.Diagnostics.ProcessStartInfo
        $realArgString = if ($quoted.Count -gt 0) { ($quoted -join " ") } else { "" }

        if ($resolve.Kind -eq 'Bat') {
            # .bat/.cmd harus dijalankan via cmd.exe /d /c, karena ProcessStartInfo tidak
            # bisa langsung invoke batch file ketika UseShellExecute=$false.
            # Tambahkan "exit /b %errorlevel%" supaya %ERRORLEVEL% propagate ke exit code process.
            $batQuoted = _Quote-Arg $resolve.FullPath
            $innerCmd = if ([string]::IsNullOrEmpty($realArgString)) {
                "$batQuoted"
            } else {
                "$batQuoted $realArgString"
            }
            $wrapper = """$innerCmd & exit /b %errorlevel%"""
            $pinfo.FileName = 'cmd.exe'
            $pinfo.Arguments = "/d /s /c $wrapper"
            Write-Verbose "[Bat wrapper] cmd.exe /d /s /c $wrapper"
        } elseif ($resolve.Kind -eq 'Ps1') {
            $ps1Quoted = _Quote-Arg $resolve.FullPath
            $innerCmd = if ([string]::IsNullOrEmpty($realArgString)) {
                "& $ps1Quoted ; exit `$LASTEXITCODE"
            } else {
                "& $ps1Quoted $realArgString ; exit `$LASTEXITCODE"
            }
            $pinfo.FileName = 'powershell.exe'
            $pinfo.Arguments = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -Command $innerCmd"
            Write-Verbose "[Ps1 wrapper] powershell.exe ... -Command $innerCmd"
        } else {
            # Native executable (.exe / binary)
            $pinfo.FileName = $resolve.FullPath
            if (-not [string]::IsNullOrEmpty($realArgString)) {
                $pinfo.Arguments = $realArgString
            }
        }

        $pinfo.WorkingDirectory = $WorkingDirectory
        $pinfo.RedirectStandardOutput = $true
        $pinfo.RedirectStandardError = $true
        $pinfo.UseShellExecute = $false
        $pinfo.CreateNoWindow = $true
        $pinfo.StandardOutputEncoding = [System.Text.Encoding]::UTF8
        $pinfo.StandardErrorEncoding = [System.Text.Encoding]::UTF8

        $proc = New-Object System.Diagnostics.Process
        $proc.StartInfo = $pinfo
        $started = $proc.Start()
        Write-Verbose "Process started: PID=$($proc.Id) Started=$started"

        $stdout = New-Object System.Text.StringBuilder
        $stderr = New-Object System.Text.StringBuilder

        $outputTask = $proc.StandardOutput.ReadToEndAsync()
        $errorTask = $proc.StandardError.ReadToEndAsync()

        $completed = $proc.WaitForExit($TimeoutSec * 1000)
        if (-not $completed) {
            $proc.Kill()
            throw "Command timeout after ${TimeoutSec}s: $fullCmd"
        }

        $stdoutText = $outputTask.GetAwaiter().GetResult()
        $stderrText = $errorTask.GetAwaiter().GetResult()

        if (-not [string]::IsNullOrWhiteSpace($stdoutText)) {
            Write-Verbose "STDOUT:"
            Write-Verbose $stdoutText
        }
        if (-not [string]::IsNullOrWhiteSpace($stderrText)) {
            Write-Verbose "STDERR:"
            Write-Verbose $stderrText
        }

        if ($proc.ExitCode -ne 0) {
            if ($Critical) {
                throw "Command FAILED (exit=$($proc.ExitCode)): $fullCmd`n--- STDOUT ---`n$stdoutText`n--- STDERR ---`n$stderrText"
            } else {
                Write-Warn "Command non-zero exit ($($proc.ExitCode)): $fullCmd"
            }
        }

        return [PSCustomObject]@{
            ExitCode = $proc.ExitCode
            StdOut   = $stdoutText
            StdErr   = $stderrText
        }
    }
    catch {
        throw
    }
}

function Invoke-StreamingCommand {
    param(
        [Parameter(Mandatory)]
        [string]$Command,
        [string[]]$Arguments = @(),
        [string]$WorkingDirectory = $PWD.Path,
        [int]$TimeoutSec = $Script:TimeoutSeconds,
        [bool]$Critical = $true
    )

    $quoted = @($Arguments | ForEach-Object { _Quote-Arg $_ })
    $fullCmd = $Command
    if ($quoted.Count -gt 0) {
        $fullCmd += " " + ($quoted -join " ")
    }
    Write-Verbose "-> $fullCmd"

    try {
        $resolve = _Resolve-CommandPath -CmdName $Command
        if (-not $resolve) {
            if ($Critical) {
                throw "Command tidak ditemukan di PATH: $Command"
            } else {
                Write-Verbose "Command tidak ada di PATH (non-critical): $Command"
                return [PSCustomObject]@{
                    ExitCode = -1
                    StdOut   = ''
                    StdErr   = "Command '$Command' not found in PATH"
                }
            }
        }

        Write-Verbose "[Resolve] $Command -> $($resolve.Kind): $($resolve.FullPath)"

        $pinfo = New-Object System.Diagnostics.ProcessStartInfo
        $realArgString = if ($quoted.Count -gt 0) { ($quoted -join " ") } else { "" }

        if ($resolve.Kind -eq 'Bat') {
            $batQuoted = _Quote-Arg $resolve.FullPath
            $innerCmd = if ([string]::IsNullOrEmpty($realArgString)) {
                "$batQuoted"
            } else {
                "$batQuoted $realArgString"
            }
            $wrapper = """$innerCmd & exit /b %errorlevel%"""
            $pinfo.FileName = 'cmd.exe'
            $pinfo.Arguments = "/d /s /c $wrapper"
        } elseif ($resolve.Kind -eq 'Ps1') {
            $ps1Quoted = _Quote-Arg $resolve.FullPath
            $innerCmd = if ([string]::IsNullOrEmpty($realArgString)) {
                "& $ps1Quoted ; exit `$LASTEXITCODE"
            } else {
                "& $ps1Quoted $realArgString ; exit `$LASTEXITCODE"
            }
            $pinfo.FileName = 'powershell.exe'
            $pinfo.Arguments = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -Command $innerCmd"
        } else {
            $pinfo.FileName = $resolve.FullPath
            if (-not [string]::IsNullOrEmpty($realArgString)) {
                $pinfo.Arguments = $realArgString
            }
        }

        $pinfo.WorkingDirectory = $WorkingDirectory
        $pinfo.RedirectStandardOutput = $true
        $pinfo.RedirectStandardError = $true
        $pinfo.UseShellExecute = $false
        $pinfo.CreateNoWindow = $true
        $pinfo.StandardOutputEncoding = [System.Text.Encoding]::UTF8
        $pinfo.StandardErrorEncoding = [System.Text.Encoding]::UTF8

        $proc = New-Object System.Diagnostics.Process
        $proc.StartInfo = $pinfo

        $stdoutSb = New-Object System.Text.StringBuilder
        $stderrSb = New-Object System.Text.StringBuilder

        $outEvent = Register-ObjectEvent -InputObject $proc -EventName OutputDataReceived -Action {
            if (-not [string]::IsNullOrWhiteSpace($EventArgs.Data)) {
                [System.Console]::Out.WriteLine($EventArgs.Data)
                $Event.MessageData.AppendLine($EventArgs.Data) | Out-Null
            }
        } -MessageData $stdoutSb

        $errEvent = Register-ObjectEvent -InputObject $proc -EventName ErrorDataReceived -Action {
            if (-not [string]::IsNullOrWhiteSpace($EventArgs.Data)) {
                [System.Console]::Error.WriteLine($EventArgs.Data)
                $Event.MessageData.AppendLine($EventArgs.Data) | Out-Null
            }
        } -MessageData $stderrSb

        $started = $proc.Start()
        Write-Verbose "Process started (streaming): PID=$($proc.Id) Started=$started"

        $proc.BeginOutputReadLine()
        $proc.BeginErrorReadLine()

        $completed = $proc.WaitForExit($TimeoutSec * 1000)
        if (-not $completed) {
            try { Unregister-Event -SourceIdentifier $outEvent.Name -ErrorAction SilentlyContinue } catch {}
            try { Unregister-Event -SourceIdentifier $errEvent.Name -ErrorAction SilentlyContinue } catch {}
            try { $proc.Kill() } catch {}
            throw "Command timeout after ${TimeoutSec}s: $fullCmd"
        }

        $proc.WaitForExit()
        Start-Sleep -Milliseconds 200
        try { Unregister-Event -SourceIdentifier $outEvent.Name -ErrorAction SilentlyContinue } catch {}
        try { Unregister-Event -SourceIdentifier $errEvent.Name -ErrorAction SilentlyContinue } catch {}
        try { Remove-Job -Name $outEvent.Name -Force -ErrorAction SilentlyContinue } catch {}
        try { Remove-Job -Name $errEvent.Name -Force -ErrorAction SilentlyContinue } catch {}

        $stdoutText = $stdoutSb.ToString()
        $stderrText = $stderrSb.ToString()

        if ($proc.ExitCode -ne 0) {
            if ($Critical) {
                throw "Command FAILED (exit=$($proc.ExitCode)): $fullCmd"
            } else {
                Write-Warn "Command non-zero exit ($($proc.ExitCode)): $fullCmd"
            }
        }

        return [PSCustomObject]@{
            ExitCode = $proc.ExitCode
            StdOut   = $stdoutText
            StdErr   = $stderrText
        }
    }
    catch {
        try {
            if ($outEvent) { Unregister-Event -SourceIdentifier $outEvent.Name -ErrorAction SilentlyContinue }
            if ($errEvent) { Unregister-Event -SourceIdentifier $errEvent.Name -ErrorAction SilentlyContinue }
        } catch {}
        throw
    }
}

function Test-CommandExists {
    param([string]$Name)
    try {
        $null = Get-Command $Name -ErrorAction Stop
        return $true
    } catch {
        return $false
    }
}

function Confirm-Action {
    param(
        [string]$Message,
        [bool]$Danger = $false,
        [bool]$DefaultYes = $false
    )
    if ($Force -or $WhatIfPreference) { return $true }
    $color = if ($Danger) { 'Red' } else { 'Yellow' }
    Write-Host ""
    Write-Host "[WARN]  KONFIRMASI DIBUTUHKAN" -ForegroundColor $color
    Write-Host "   $Message" -ForegroundColor $color
    Write-Host ""
    $default = if ($DefaultYes) { 'Y' } else { 'N' }
    $prompt = Read-Host "   Lanjutkan? (y/N) [$default]"
    if ([string]::IsNullOrWhiteSpace($prompt)) { $prompt = $default }
    return ($prompt -match '^(y|yes)$')
}

function Get-GitSha {
    if (Test-CommandExists 'git') {
        try {
            $result = Invoke-SafeCommand -Command 'git' -Arguments @('rev-parse', '--short', 'HEAD') -Critical $false
            if ($result.ExitCode -eq 0 -and -not [string]::IsNullOrWhiteSpace($result.StdOut)) {
                return $result.StdOut.Trim()
            }
        } catch {}
    }
    return 'latest'
}

function Read-EnvFile {
    param([string]$Path)
    $envData = @{}
    if (-not (Test-Path $Path)) { return $envData }
    Get-Content $Path -Encoding UTF8 | ForEach-Object {
        $line = $_.Trim()
        if ([string]::IsNullOrWhiteSpace($line) -or $line.StartsWith('#')) { return }
        $eqIdx = $line.IndexOf('=')
        if ($eqIdx -gt 0) {
            $key = $line.Substring(0, $eqIdx).Trim()
            $value = $line.Substring($eqIdx + 1).Trim()
            if ($value.StartsWith('"') -and $value.EndsWith('"')) {
                $value = $value.Substring(1, $value.Length - 2)
            }
            $envData[$key] = $value
        }
    }
    return $envData
}

function Get-LatestGitSemverTag {
    if (-not (Test-CommandExists 'git')) { return $null }
    try {
        $r = Invoke-SafeCommand -Command 'git' -Arguments @('tag', '-l', '--sort=-v:refname') -Critical $false
        if ($r.ExitCode -ne 0 -or [string]::IsNullOrWhiteSpace($r.StdOut)) { return $null }
        $tags = $r.StdOut.Trim() -split "`n" | ForEach-Object { $_.Trim() } | Where-Object { $_ -match '^v?\d+\.\d+\.\d+$' }
        if (-not $tags -or $tags.Count -eq 0) { return $null }
        $t = $tags[0]
        if ($t -match '^v(\d+)\.(\d+)\.(\d+)$') {
            return [PSCustomObject]@{
                Raw      = $t
                PrefixV  = $true
                Major    = [int]$Matches[1]
                Minor    = [int]$Matches[2]
                Patch    = [int]$Matches[3]
            }
        } elseif ($t -match '^(\d+)\.(\d+)\.(\d+)$') {
            return [PSCustomObject]@{
                Raw      = $t
                PrefixV  = $false
                Major    = [int]$Matches[1]
                Minor    = [int]$Matches[2]
                Patch    = [int]$Matches[3]
            }
        }
        return $null
    } catch {
        Write-Verbose "Get-LatestGitSemverTag error: $_"
        return $null
    }
}

function New-BumpedSemver {
    param(
        [Parameter(Mandatory)]
        [PSCustomObject]$Current,
        [ValidateSet('Major', 'Minor', 'Patch')]
        [string]$Level = 'Patch'
    )
    $v = @{
        Major = $Current.Major
        Minor = $Current.Minor
        Patch = $Current.Patch
    }
    switch ($Level) {
        'Major' { $v.Major += 1; $v.Minor = 0; $v.Patch = 0 }
        'Minor' { $v.Minor += 1; $v.Patch = 0 }
        'Patch' { $v.Patch += 1 }
    }
    $prefix = if ($Current.PrefixV) { 'v' } else { '' }
    return "$($prefix)$($v.Major).$($v.Minor).$($v.Patch)"
}

function Test-DockerLoggedIn {
    param([string]$RegistryHint = '')
    if (-not (Test-CommandExists 'docker')) { return $false }
    $dockerConfigDir = if ($ENV:DOCKER_CONFIG) { $ENV:DOCKER_CONFIG } else { Join-Path $HOME '.docker' }
    $configJson = Join-Path $dockerConfigDir 'config.json'
    if (-not (Test-Path $configJson)) { return $false }
    try {
        $cfg = Get-Content $configJson -Raw -Encoding UTF8 | ConvertFrom-Json -ErrorAction Stop
        if (-not $cfg.PSObject.Properties.Match('auths') -or -not $cfg.auths) { return $false }
        if ([string]::IsNullOrWhiteSpace($RegistryHint)) {
            return ($cfg.auths.PSObject.Properties.Count -gt 0)
        }
        foreach ($regKey in $cfg.auths.PSObject.Properties.Name) {
            if ($regKey -like "*$RegistryHint*") { return $true }
            if ($RegistryHint -like "*$regKey*") { return $true }
        }
        return $false
    } catch {
        Write-Verbose "Parse docker config.json error: $_"
        return $false
    }
}

function Request-VersionTag {
    param(
        [string]$DefaultBump = 'Patch'
    )
    $latest = Get-LatestGitSemverTag
    if (-not $latest) {
        Write-Warn "Belum ada git tag dengan format semver (v1.0.0). Default pertama: v0.1.0"
        $suggest = 'v0.1.0'
    } else {
        $sPatch  = New-BumpedSemver -Current $latest -Level Patch
        $sMinor  = New-BumpedSemver -Current $latest -Level Minor
        $sMajor  = New-BumpedSemver -Current $latest -Level Major
        Write-Host ""
        Write-Host "  Latest git tag: $($latest.Raw)" -ForegroundColor DarkCyan
        Write-Host "  Suggested bump:" -ForegroundColor DarkCyan
        Write-Host "    1) Patch  -> $sPatch  (bugfix / minor)" -ForegroundColor Cyan
        Write-Host "    2) Minor  -> $sMinor  (new feature)" -ForegroundColor Cyan
        Write-Host "    3) Major  -> $sMajor  (breaking change)" -ForegroundColor Cyan
        Write-Host "    4) Custom -> ketik manual (mis. v2.0.0-beta.1)" -ForegroundColor Cyan
    }
    Write-Host ""

    if ($Bump -ne 'None' -and $latest) {
        return (New-BumpedSemver -Current $latest -Level $Bump)
    }
    if ($Bump -ne 'None' -and -not $latest) {
        return 'v0.1.0'
    }

    do {
        if ($latest) {
            $prompt = Read-Host "  Pilih [1/2/3/4] atau ketik custom tag [default: 1 = $sPatch]"
            if ([string]::IsNullOrWhiteSpace($prompt)) { $prompt = '1' }
            switch -Wildcard ($prompt) {
                '1'     { return $sPatch }
                '2'     { return $sMinor }
                '3'     { return $sMajor }
                '4'     {
                    $custom = Read-Host "  Masukkan tag versi (mis. v1.2.3-rc1)"
                    if (-not [string]::IsNullOrWhiteSpace($custom)) { return $custom.Trim() }
                }
                default {
                    if ($prompt -match '^v?\d') { return $prompt.Trim() }
                    Write-Warn "Input tidak valid. Ulangi."
                }
            }
        } else {
            $custom = Read-Host "  Masukkan tag versi [default: v0.1.0]"
            if ([string]::IsNullOrWhiteSpace($custom)) { return 'v0.1.0' }
            return $custom.Trim()
        }
    } while ($true)
}

function Request-Registry {
    Write-Host ""
    Write-Host "  --- Pilih Registry Target ---" -ForegroundColor DarkCyan
    Write-Host "    1) Docker Hub (paling umum) -> perlu -DockerHub <username>" -ForegroundColor Cyan
    Write-Host "    2) GitHub Container Registry (ghcr.io/username)" -ForegroundColor Cyan
    Write-Host "    3) Custom Registry (registry.acme.id/prefix)" -ForegroundColor Cyan
    Write-Host ""

    if ($Force) {
        Write-ErrorMsg "Force mode tapi registry/DockerHub tidak diset. Set -DockerHub <user> atau -Registry <prefix>."
        exit 1
    }

    do {
        $choice = Read-Host "  Pilih [1/2/3]"
        switch ($choice) {
            '1' {
                $u = Read-Host "  Docker Hub username"
                if (-not [string]::IsNullOrWhiteSpace($u)) { return "docker.io/$($u.Trim())" }
            }
            '2' {
                $u = Read-Host "  GitHub username/org (ghcr.io/<INI>)"
                if (-not [string]::IsNullOrWhiteSpace($u)) { return "ghcr.io/$($u.Trim())" }
            }
            '3' {
                $r = Read-Host "  Registry prefix lengkap (mis. registry.acme.co.id/apps)"
                if (-not [string]::IsNullOrWhiteSpace($r)) { return $r.Trim() }
            }
            default { Write-Warn "Pilihan tidak valid (1/2/3)." }
        }
    } while ($true)
}

# ================================================================
# Banner & Context
# ================================================================

$PROJECT_ROOT = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $PROJECT_ROOT

$HOST_INFO = $($ENV:COMPUTERNAME)
if (-not $HOST_INFO) { $HOST_INFO = 'localhost' }

Write-Host ""
Write-Host "#================================================================#" -ForegroundColor DarkCyan
Write-Host "#  [EDUZONE]  EDUZONE -- BUILD SCRIPT                                  #" -ForegroundColor DarkCyan
Write-Host "#================================================================#" -ForegroundColor DarkCyan
Write-Host ""
Write-Host "  Mode         : $Mode"
Write-Host "  Host         : $HOST_INFO"
Write-Host "  Working Dir  : $PROJECT_ROOT"
Write-Host "  Started      : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Host ""

if ($Mode -eq 'Production') {
    Write-Warn "MODE PRODUCTION AKTIF -- semua operasi akan pakai flag --no-dev, --optimize-autoloader, dan cek keamanan tambahan"
    Write-Host ""
}

# ================================================================
# 1. Prerequisite Checks
# ================================================================
Write-Step "Pengecekan Prasyarat"

$prereqOk = $true

if (-not (Test-CommandExists 'php')) {
    Write-ErrorMsg "PHP tidak ditemukan di PATH. Install PHP 8.2+ atau aktifkan di Laragon."
    $prereqOk = $false
} else {
    $ver = (Invoke-SafeCommand -Command 'php' -Arguments @('-v') -Critical $false).StdOut.Trim()
    if ($ver -match 'PHP (\d+\.\d+\.\d+)') {
        $ver = $Matches[1]
    } elseif ($ver -match 'PHP (\d+\.\d+)') {
        $ver = $Matches[1]
    }
    if ($ver -match '^(\d+)\.(\d+)') {
        $maj = [int]$Matches[1]; $min = [int]$Matches[2]
        if ($maj -lt 8 -or ($maj -eq 8 -and $min -lt 2)) {
            Write-ErrorMsg "PHP version $ver terlalu tua. Butuh >= 8.2."
            $prereqOk = $false
        } else {
            Write-Success "PHP $ver -- OK"
        }
    }
}

if (-not (Test-CommandExists 'composer')) {
    Write-ErrorMsg "Composer tidak ditemukan di PATH."
    $prereqOk = $false
} else {
    $composerVer = (Invoke-SafeCommand -Command 'composer' -Arguments @('--version') -Critical $false).StdOut.Trim()
    if ($composerVer) { Write-Success "Composer -- OK ($($composerVer -split "`n" | Select-Object -First 1))" }
}

if (-not $SkipNpm) {
    if (-not (Test-CommandExists 'node')) {
        Write-ErrorMsg "Node.js tidak ditemukan di PATH. Install Node 20+."
        $prereqOk = $false
    } else {
        $nodeVer = (Invoke-SafeCommand -Command 'node' -Arguments @('-v') -Critical $false).StdOut.Trim()
        if ($nodeVer -match '^v(\d+)') {
            if ([int]$Matches[1] -lt 20) {
                Write-ErrorMsg "Node $nodeVer terlalu tua. Butuh >= 20."
                $prereqOk = $false
            } else {
                Write-Success "Node.js $nodeVer -- OK"
            }
        }
    }

    if (-not (Test-CommandExists 'npm')) {
        Write-ErrorMsg "npm tidak ditemukan di PATH."
        $prereqOk = $false
    } else {
        $npmVer = (Invoke-SafeCommand -Command 'npm' -Arguments @('-v') -Critical $false).StdOut.Trim()
        Write-Success "npm $npmVer -- OK"
    }
}

if ($DockerBuild -or $DockerUp -or $DockerRecreate) {
    if (-not (Test-CommandExists 'docker')) {
        Write-ErrorMsg "Docker tidak ditemukan di PATH. Install Docker Desktop."
        $prereqOk = $false
    } else {
        $dockerVer = (Invoke-SafeCommand -Command 'docker' -Arguments @('--version') -Critical $false).StdOut.Trim()
        Write-Success "$dockerVer -- OK"
        $composeOk = Invoke-SafeCommand -Command 'docker' -Arguments @('compose', 'version') -Critical $false
        if ($composeOk.ExitCode -eq 0) {
            Write-Success "Docker Compose plugin -- OK"
        } else {
            Write-ErrorMsg "Docker Compose plugin tidak tersedia (docker compose version gagal)."
            $prereqOk = $false
        }
    }
}

if (-not $prereqOk) {
    Write-ErrorMsg "Prasyarat belum terpenuhi. Perbaiki error di atas lalu jalankan kembali."
    exit 1
}

Write-Host ""

# ================================================================
# 2. Environment Validation (.env file)
# ================================================================
Write-Step "Validasi Environment (.env)"

$envPath = Join-Path $PROJECT_ROOT '.env'
$envExamplePath = Join-Path $PROJECT_ROOT '.env.example'

if (-not (Test-Path $envPath)) {
    if (Test-Path $envExamplePath) {
        Write-Warn "File .env tidak ditemukan. Copy dari .env.example..."
        if ($PSCmdlet.ShouldProcess('.env', 'Copy dari .env.example')) {
            Copy-Item $envExamplePath $envPath -Force
            Write-Success ".env dicopy dari template"
            Write-Warn "Isi nilai-nilai di .env (APP_KEY, DB_PASSWORD, REVERB_*, ABSENSI_*, ENCRYPTION_SERVICE_*) sebelum lanjut!"
        }
    } else {
        Write-ErrorMsg ".env.example juga tidak ada. Tidak bisa lanjut."
        exit 1
    }
}

$envData = Read-EnvFile -Path $envPath
$needReGenerateKey = $false
if ([string]::IsNullOrWhiteSpace($envData['APP_KEY'])) {
    if ($PSCmdlet.ShouldProcess('.env', 'Generate APP_KEY via artisan key:generate')) {
        Write-Warn "APP_KEY kosong. Generate APP_KEY baru..."
        $result = Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'key:generate', '--ansi') -Critical $false
        if ($result.ExitCode -eq 0) {
            Write-Success "APP_KEY berhasil digenerate"
            $envData = Read-EnvFile -Path $envPath
        } else {
            Write-ErrorMsg "Gagal generate APP_KEY. Periksa autoload/vendor."
            $needReGenerateKey = $true
        }
    }
} else {
    Write-Success "APP_KEY ada -- OK"
}

if ($Mode -eq 'Production') {
    Write-Warn "Mode Production -- cek environment variables critical..."
    $criticalEnvVars = @(
        'APP_KEY',
        'DB_PASSWORD',
        'REVERB_APP_ID',
        'REVERB_APP_KEY',
        'REVERB_APP_SECRET',
        'ABSENSI_GATEWAY_JWT_SECRET',
        'ABSENSI_SYNC_TOKEN',
        'ENCRYPTION_SERVICE_API_KEY'
    )
    $missingCritical = @()
    foreach ($var in $criticalEnvVars) {
        if ([string]::IsNullOrWhiteSpace($envData[$var])) {
            $missingCritical += $var
        }
    }
    if ($missingCritical.Count -gt 0) {
        Write-ErrorMsg "ENVIRONMENT VARIABLES KOSONG (CRITICAL DI PRODUCTION):"
        foreach ($v in $missingCritical) { Write-ErrorMsg "  - $v" }
        if (-not (Confirm-Action -Message "Production build dengan env vars kosong di atas? (APP_DEBUG=false otomatis, tapi missing keys bisa bikin runtime error)" -Danger $true)) {
            Write-ErrorMsg "Build dibatalkan user."
            exit 1
        }
    } else {
        Write-Success "Semua critical env vars terisi -- OK"
    }

    if ($envData['APP_DEBUG'] -ne 'false') {
        Write-Warn "APP_DEBUG di .env belum 'false'. Production sebaiknya APP_DEBUG=false."
    }
    if ($envData['APP_ENV'] -ne 'production') {
        Write-Warn "APP_ENV di .env adalah '$($envData['APP_ENV'])' (seharusnya 'production' untuk production build)."
    }
}

Write-Host ""

# ================================================================
# 3. Clean Step (optional)
# ================================================================
if ($Clean) {
    Write-Step "Clean Build Artefak"
    $toClean = @(
        @{ Path = 'vendor'; Desc = 'PHP vendor directory' },
        @{ Path = 'node_modules'; Desc = 'Node modules directory' },
        @{ Path = 'public\build'; Desc = 'Vite build output' }
    )
    foreach ($item in $toClean) {
        $fullPath = Join-Path $PROJECT_ROOT $item.Path
        if (Test-Path $fullPath) {
            if ($PSCmdlet.ShouldProcess($item.Path, 'Remove')) {
                Remove-Item $fullPath -Recurse -Force -ErrorAction SilentlyContinue
                Write-Success "Dihapus: $($item.Desc)"
            }
        }
    }

    # Clear bootstrap cache files (exclude .gitignore)
    $bootstrapCache = Join-Path $PROJECT_ROOT 'bootstrap\cache'
    if (Test-Path $bootstrapCache) {
        Get-ChildItem $bootstrapCache -File -Exclude '.gitignore' | ForEach-Object {
            if ($PSCmdlet.ShouldProcess($_.Name, 'Remove from bootstrap/cache')) {
                Remove-Item $_.FullName -Force
                Write-Verbose "Dihapus: bootstrap/cache/$($_.Name)"
            }
        }
    }
    Write-Host ""
}

# ================================================================
# 4. Composer Dependencies
# ================================================================
if (-not $SkipComposer) {
    Write-Step "Instalasi Dependencies PHP (Composer)"

    # Bentuk array --ignore-platform-req=<NAME> (bisa multiple values)
    $ignorePlatformFlags = @()
    if ($IgnorePlatformReq -and $IgnorePlatformReq.Count -gt 0) {
        foreach ($name in $IgnorePlatformReq) {
            if (-not [string]::IsNullOrWhiteSpace($name)) {
                $ignorePlatformFlags += "--ignore-platform-req=$($name.Trim())"
            }
        }
        Write-Verbose "Composer ignore platform reqs: $($ignorePlatformFlags -join ', ')"
        Write-Host "  [INFO] Composer ignore platform: $($IgnorePlatformReq -join ', ')" -ForegroundColor DarkGray
    }

    $composerArgs = @('install', '--prefer-dist', '--no-interaction', '--no-progress')
    if ($ignorePlatformFlags.Count -gt 0) { $composerArgs += $ignorePlatformFlags }

    if ($Mode -eq 'Production') {
        $composerArgs += @('--no-dev', '--optimize-autoloader')
    }

    if ($PSCmdlet.ShouldProcess('composer install', "Run dengan args: $($composerArgs -join ' ')")) {
        Invoke-SafeCommand -Command 'composer' -Arguments $composerArgs -WorkingDirectory $PROJECT_ROOT
        Write-Success "Composer dependencies terinstall ($Mode)"
    }

    # Post autoload dump (production use authoritative classmap)
    if ($Mode -eq 'Production') {
        $dumpArgs = @('dump-autoload', '--optimize', '--classmap-authoritative', '--no-interaction')
        if ($ignorePlatformFlags.Count -gt 0) { $dumpArgs += $ignorePlatformFlags }
        $dumpDisplay = $dumpArgs -join ' '
        if ($PSCmdlet.ShouldProcess('composer dump-autoload', $dumpDisplay)) {
            Invoke-SafeCommand -Command 'composer' -Arguments $dumpArgs
            Write-Success "Autoloader dioptimize (authoritative classmap)"
        }
    }

    if ($needReGenerateKey) {
        Write-Warn "Retry generate APP_KEY setelah vendor terinstall..."
        if ($PSCmdlet.ShouldProcess('.env', 'Generate APP_KEY')) {
            Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'key:generate', '--ansi') -Critical $false | Out-Null
            Write-Success "APP_KEY regenerated"
        }
    }
    Write-Host ""
}

# ================================================================
# 5. NPM & Frontend Assets
# ================================================================
if (-not $SkipNpm) {
    Write-Step "Instalasi & Build Frontend (npm + Vite)"

    $lockPath = Join-Path $PROJECT_ROOT 'package-lock.json'
    $npmInstallArgs = @('install', '--no-audit', '--no-fund')
    if (Test-Path $lockPath) {
        if ($Mode -eq 'Production') {
            $npmInstallArgs = @('ci', '--frozen-lockfile', '--no-audit', '--no-fund')
        }
    } else {
        Write-Warn "package-lock.json tidak ada -- fallback ke npm install (tidak reproducible)"
    }

    if ($PSCmdlet.ShouldProcess('npm install', "$($npmInstallArgs -join ' ')")) {
        Invoke-SafeCommand -Command 'npm' -Arguments $npmInstallArgs -WorkingDirectory $PROJECT_ROOT
        Write-Success "npm dependencies terinstall"
    }

    if ($PSCmdlet.ShouldProcess('npm run build', 'Vite production build assets')) {
        Invoke-SafeCommand -Command 'npm' -Arguments @('run', 'build') -WorkingDirectory $PROJECT_ROOT
        Write-Success "Frontend assets built (resources/* -> public/build/)"
    }
    Write-Host ""
}

# ================================================================
# 6. Laravel Setup -- Storage Permissions, Cache, Optimize
# ================================================================
Write-Step "Konfigurasi Laravel (storage, cache, optimize)"

# Pastikan storage folder structure ada & writable
$storageDirs = @(
    'storage\app\private',
    'storage\app\public',
    'storage\logs',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\testing',
    'storage\framework\views',
    'bootstrap\cache'
)
foreach ($dir in $storageDirs) {
    $fullDir = Join-Path $PROJECT_ROOT $dir
    if (-not (Test-Path $fullDir)) {
        if ($PSCmdlet.ShouldProcess($dir, 'Create directory')) {
            $null = New-Item -Path $fullDir -ItemType Directory -Force
            Write-Verbose "Dibuat: $dir"
        }
    }
}
Write-Success "Storage & cache directories siap"

# Publish vendor assets (force only on non-dev? No, safer always because of reverb/horizon/telescope)
if (-not $SkipComposer) {
    if ($PSCmdlet.ShouldProcess('vendor:publish', 'Laravel assets (reverb, telescope, horizon, multitenancy)')) {
        $publishResult = Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'vendor:publish', '--tag=laravel-assets', '--ansi', '--no-interaction') -Critical $false
        Write-Verbose ($publishResult.StdOut + $publishResult.StdErr)
    }
}

# Cache strategy
if ($Mode -eq 'Production' -and -not $SkipOptimize) {
    if ($PSCmdlet.ShouldProcess('artisan optimize:*', 'Cache config, route, view, event')) {
        $optimizeResult = Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'optimize', '--ansi') -Critical $false
        if ($optimizeResult.ExitCode -eq 0) {
            Write-Success "Laravel dioptimize (config + routes cached)"
        } else {
            Write-Warn "artisan optimize non-zero exit; jalankan manual per-cache command:"
            Write-Host "   php artisan config:cache  (exit non-0 = route/view juga perlu dicache terpisah)" -ForegroundColor DarkYellow
        }

        # View cache (often not included in default artisan optimize)
        Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'view:cache') -Critical $false | Out-Null
        Write-Success "View templates dicache"
    }
} else {
    if ($PSCmdlet.ShouldProcess('artisan optimize:clear', 'Clear ALL caches (dev mode)')) {
        Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'optimize:clear') -Critical $false | Out-Null
        Write-Success "Semua cache Laravel dihapus (mode dev = always fresh)"
    }
}

# Storage:link (safe to run multiple times)
if ($PSCmdlet.ShouldProcess('public/storage', 'Symlink via storage:link')) {
    $linkResult = Invoke-SafeCommand -Command 'php' -Arguments @('artisan', 'storage:link', '--ansi') -Critical $false
    if ($linkResult.ExitCode -eq 0) {
        Write-Success "storage:link OK"
    } else {
        Write-Verbose "storage:link sudah ada atau tidak perlu -- aman diabaikan"
    }
}

Write-Host ""

# ================================================================
# 7. Database Operations (migrate / seed / fresh)
# ================================================================
if ($Migrate -or $Seed -or $Fresh) {
    Write-Step "Operasi Database"

    if ($Fresh) {
        if ($Mode -eq 'Production') {
            if (-not (Confirm-Action -Message "MIGRATE:FRESH di PRODUCTION akan DROP SEMUA TABEL + DATA. Ini BUKAN rollback. Data hilang PERMANEN!" -Danger $true -DefaultYes $false)) {
                Write-ErrorMsg "DB Fresh dibatalkan user."
                exit 1
            }
        } else {
            if (-not (Confirm-Action -Message "migrate:fresh akan DROP SEMUA tabel di DB utama & DB absensi, lalu re-run migration. Yakin?" -Danger $true -DefaultYes $false)) {
                Write-ErrorMsg "DB Fresh dibatalkan user."
                exit 1
            }
        }
    }

    if ($Fresh -and $PSCmdlet.ShouldProcess('migrate:fresh', 'Drop all tables and remigrate')) {
        $migrateArgs = @('artisan', 'migrate:fresh', '--ansi')
        if ($Mode -eq 'Production' -or $Force) { $migrateArgs += '--force' }
        Invoke-SafeCommand -Command 'php' -Arguments $migrateArgs
        Write-Success "migrate:fresh SELESAI (semua tabel dibuat ulang)"
    } elseif ($Migrate -and $PSCmdlet.ShouldProcess('migrate', 'Run pending migrations')) {
        $migrateArgs = @('artisan', 'migrate', '--ansi')
        if ($Mode -eq 'Production' -or $Force) { $migrateArgs += '--force' }
        Invoke-SafeCommand -Command 'php' -Arguments $migrateArgs
        Write-Success "Migration SELESAI"
    }

    if ($Seed -and $PSCmdlet.ShouldProcess('db:seed', 'Seed test/initial data')) {
        $seedArgs = @('artisan', 'db:seed', '--ansi')
        if ($Mode -eq 'Production' -or $Force) { $seedArgs += '--force' }
        Invoke-SafeCommand -Command 'php' -Arguments $seedArgs
        Write-Success "Seeder SELESAI"
    }
    Write-Host ""
}

# ================================================================
# 8. Docker (Build / Up / Push)
# ================================================================
$Script:DockerReleaseTag = $null   # disimpan buat summary section (git tag suggestion)

if ($DockerBuild -or $DockerUp -or $DockerRecreate -or $Push) {
    Write-Step "Docker Operations"

    $targetStage = if ($Mode -eq 'Production') { 'production' } else { 'development' }
    $imageName = 'eduzone-app'

    # -- Resolve registry (Push butuh registry; prompt kalo kosong) --
    if ($Push -and [string]::IsNullOrWhiteSpace($Registry)) {
        Write-Warn "-Push aktif tapi -Registry/-DockerHub belum diset."
        $Registry = Request-Registry
        Write-Success "Registry target: $Registry"
    }

    if (-not [string]::IsNullOrWhiteSpace($Registry)) {
        $Registry = $Registry.TrimEnd('/', '\')
    }

    # -- Resolve tag versi (Push + kosong = prompt interaktif semver) --
    if ([string]::IsNullOrWhiteSpace($Tag)) {
        if ($Push) {
            Write-Warn "Tag versi belum diset (-Tag kosong) + -Push aktif."
            $imageTag = Request-VersionTag -DefaultBump 'Patch'
        } else {
            $imageTag = Get-GitSha
        }
    } else {
        $imageTag = $Tag
    }
    $Script:DockerReleaseTag = $imageTag

    # -- Docker login check (HANYA kalo -Push) --
    if ($Push -and -not (Test-DockerLoggedIn -RegistryHint $Registry)) {
        Write-Host ""
        Write-ErrorMsg "BELUM LOGIN ke registry target: $Registry"
        Write-Host "  Silakan login DULU via terminal:" -ForegroundColor Yellow
        if ($Registry -like "docker.io/*") {
            Write-Host "    docker login" -ForegroundColor Cyan
        } elseif ($Registry -like "ghcr.io/*") {
            Write-Host "    docker login ghcr.io -u <GITHUB_USERNAME> -p <GITHUB_PAT_CLASSIC>" -ForegroundColor Cyan
        } else {
            Write-Host "    docker login $($Registry -replace '/[^/]+$','')" -ForegroundColor Cyan
        }
        Write-Host ""
        if (-not $Force -and (Confirm-Action -Message "Sudah login sekarang? (kalau jawab Y tapi belum login, push akan ERROR)" -DefaultYes $false)) {
            # user mengklaim sudah login -- lanjut
        } else {
            if ($Force) {
                Write-ErrorMsg "Force mode tapi belum login ke registry. Push akan di-skip. Login dulu lalu jalankan ulang dengan -Push (tanpa -DockerBuild kalo image sudah ada)."
            } else {
                Write-Warn "Push dibatalkan (user belum login). Image akan tetap di-build lokal dengan tag $imageTag."
            }
            $Push = $false
        }
    }

    # -- Bentuk nama image final --
    $fullImageName = $imageName
    if (-not [string]::IsNullOrWhiteSpace($Registry)) {
        $fullImageName = "$Registry/$imageName"
    }
    $fullImageName += ":$imageTag"
    $latestAlias = if (-not [string]::IsNullOrWhiteSpace($Registry)) { "$Registry/${imageName}:latest" } else { "${imageName}:latest" }

    Write-Host ""
    Write-Host "  --- Docker Target Summary ---" -ForegroundColor DarkCyan
    Write-Host "  Stage        : $targetStage"
    Write-Host "  Image tag    : $fullImageName"
    Write-Host "  :latest alias: $latestAlias"
    if (-not [string]::IsNullOrWhiteSpace($Registry)) {
        Write-Host "  Push target  : $Registry"
    }
    Write-Host ""

    # -- Build Image --
    if ($DockerBuild) {
        if ($PSCmdlet.ShouldProcess($fullImageName, "docker build --target $targetStage")) {
            $buildArgs = @(
                'build',
                '--progress', 'plain',
                '--target', $targetStage,
                '-t', $fullImageName,
                '-t', $latestAlias,
                '.'
            )
            Write-Warn "Docker build: compile ext-grpc dari source bisa MEMAKAN WAKTU LAMA (~20-60 menit). Timeout=${TimeoutSeconds}s. Ini NORMAL, bukan hang."
            Write-Host "  [INFO] Output docker build akan di-stream LIVE ke terminal di bawah ini (lihat progress compile ext-grpc)." -ForegroundColor DarkGray
            Write-Host ""
            $buildStart = Get-Date
            Invoke-StreamingCommand -Command 'docker' -Arguments $buildArgs -WorkingDirectory $PROJECT_ROOT -TimeoutSec $TimeoutSeconds
            $buildDur = (Get-Date) - $buildStart
            Write-Success "Docker image built: $fullImageName (+ $latestAlias) [selesai dalam $($buildDur.ToString('hh\:mm\:ss'))]"
        }
    }

    # -- Push ke Registry --
    if ($Push) {
        if (-not $DockerBuild) {
            # Cek image exist di local kalo tidak baru dibuild
            $imgExists = Invoke-SafeCommand -Command 'docker' -Arguments @('image', 'inspect', $fullImageName) -Critical $false
            if ($imgExists.ExitCode -ne 0) {
                Write-ErrorMsg "Image $fullImageName TIDAK ADA di local. Jalankan dengan -DockerBuild, atau build manual dulu:"
                Write-ErrorMsg "  docker build -t $fullImageName -t $latestAlias --target $targetStage ."
                exit 1
            }
            Write-Success "Image ada di local -> siap push"
        }

        if ($PSCmdlet.ShouldProcess($fullImageName, 'docker push')) {
            Write-Host "  [INFO] Output docker push akan di-stream LIVE ke terminal (lihat progress layer upload)." -ForegroundColor DarkGray
            Write-Host ""
            $pushStart = Get-Date
            Invoke-StreamingCommand -Command 'docker' -Arguments @('push', $fullImageName) -TimeoutSec $TimeoutSeconds
            Write-Success "Push OK: $fullImageName"
            if ($imageTag -ne 'latest') {
                Invoke-StreamingCommand -Command 'docker' -Arguments @('push', $latestAlias) -TimeoutSec $TimeoutSeconds -Critical $false
                Write-Success "Push OK: $latestAlias"
            }
            $pushDur = (Get-Date) - $pushStart
            Write-Verbose "Total push time: $($pushDur.ToString('hh\:mm\:ss'))"
        }
    }

    # -- Docker Up / Recreate (local compose) --
    if ($DockerUp -or $DockerRecreate) {
        if (-not (Test-Path (Join-Path $PROJECT_ROOT 'docker-compose.yml'))) {
            Write-ErrorMsg "docker-compose.yml tidak ditemukan di project root."
        } else {
            $upArgs = @('compose', 'up', '-d')
            if ($DockerRecreate) { $upArgs += '--force-recreate', '--build' }
            if ($PSCmdlet.ShouldProcess('docker compose up -d', "Start containers")) {
                Invoke-SafeCommand -Command 'docker' -Arguments $upArgs -WorkingDirectory $PROJECT_ROOT
                Write-Success "Docker compose stack started"
            }
        }
    }
    Write-Host ""
}

# ================================================================
# 9. Final Summary
# ================================================================
Write-Step "BUILD SELESAI"

$nginxPort = if (-not [string]::IsNullOrWhiteSpace($envData['NGINX_PORT'])) { $envData['NGINX_PORT'] } else { '8083' }
$vitePort = if (-not [string]::IsNullOrWhiteSpace($envData['VITE_PORT'])) { $envData['VITE_PORT'] } else { '5174' }
$reverbPort = if (-not [string]::IsNullOrWhiteSpace($envData['REVERB_PORT'])) { $envData['REVERB_PORT'] } else { '8082' }

Write-Host ""
Write-Host "  Mode build          : $Mode" -ForegroundColor Green
Write-Host "  Selesai pada        : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Green
if ($Script:DockerReleaseTag) { Write-Host "  Release Tag         : $Script:DockerReleaseTag" -ForegroundColor Yellow }
Write-Host ""
Write-Host "  --- Lokasi Akses (Docker Lokal) ---" -ForegroundColor DarkCyan
Write-Host "  App (Nginx)         : http://localhost:$nginxPort"
Write-Host "  Vite HMR (dev only) : http://localhost:$vitePort"
Write-Host "  Reverb WebSocket    : http://localhost:$reverbPort"
Write-Host ""

if ($Push -and -not [string]::IsNullOrWhiteSpace($Registry) -and $Script:DockerReleaseTag) {
    $finalImage = "$Registry/${imageName}:$Script:DockerReleaseTag"
    Write-Host "  --- [DEPLOY] Deploy ke Production (Docker Swarm) ---" -ForegroundColor DarkCyan
    Write-Host ""
    Write-Host "  Image (dipush)      : $finalImage" -ForegroundColor Green
    Write-Host "  Image :latest alias : $Registry/${imageName}:latest" -ForegroundColor Green
    Write-Host ""
    Write-Host "  1) (opsional) Git tag version -- biar mudah rollback:" -ForegroundColor Cyan
    Write-Host "     git tag -a $Script:DockerReleaseTag -m `"Release $Script:DockerReleaseTag`""
    Write-Host "     git push origin $Script:DockerReleaseTag"
    Write-Host ""
    Write-Host "  2) Di server Swarm -- update env DOCKER_IMAGE lalu deploy stack:" -ForegroundColor Cyan
    Write-Host "     export DOCKER_IMAGE=$finalImage"
    Write-Host "     docker stack deploy -c docker-compose.swarm.yml eduzone"
    Write-Host ""
    Write-Host "  3) Verifikasi:" -ForegroundColor Cyan
    Write-Host "     docker stack services eduzone"
    Write-Host "     docker stack ps eduzone --no-trunc"
    Write-Host ""
    Write-Host "  [WARN]  Jangan lupa DB migrate di production (sekali per release):" -ForegroundColor Yellow
    Write-Host "     docker exec -it \$(docker ps -q -f name=eduzone_eduzone_app) php artisan migrate --force"
    Write-Host ""
}

Write-Host "  --- Perintah Selanjutnya (Rekomendasi) ---" -ForegroundColor DarkCyan
if ($Mode -eq 'Development') {
    Write-Host "  * Build assets with watch : npm run dev"
    Write-Host "  * Start via Docker Compose: docker compose up -d"
    Write-Host "  * Docker logs app         : docker logs -f eduzone_app"
    Write-Host "  * Tinker                   : docker exec -it eduzone_app php artisan tinker"
    Write-Host "  * Migrate in container    : docker exec -it eduzone_app php artisan migrate"
} else {
    if (-not $Push) {
        Write-Host "  * Build & push image      : .\build.ps1 -Release -DockerHub <username> -Tag vX.Y.Z"
    }
    Write-Host "  * Deploy stack (Swarm)    : docker stack deploy -c docker-compose.swarm.yml eduzone"
    Write-Host "  * Test optimize commands  : php artisan config:cache && php artisan route:cache"
}
Write-Host ""
Write-Host "  --- Jika Pakai Laragon (no Docker) ---" -ForegroundColor DarkCyan
Write-Host "  * Pastikan document root -> public/"
Write-Host "  * Start queue worker      : php artisan horizon"
Write-Host "  * Start reverb            : php artisan reverb:start"
Write-Host "  * Scheduler (loop)        : php artisan schedule:work"
Write-Host ""

Write-Success "Build EduZone ($Mode) -- SELESAI TANPA ERROR KRITIS"
exit 0
