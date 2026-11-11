# ================================================================
# build-push.ps1 - Build & push image production ke Docker Hub
# Usage: .\build-push.ps1 -Tag v2.10.1
# ================================================================
param(
    [Parameter(Mandatory=$true)]
    [string]$Tag
)

$IMAGE     = "iswant/lab-management"
$FULL_TAG  = "${IMAGE}:${Tag}"
$LATEST    = "${IMAGE}:latest"

# - VITE production values (di-bake ke JS bundle) -
# Domain sudah HTTPS — gunakan wss:// port 443
$REVERB_HOST   = "maikel.wazzgroup.com"
$REVERB_PORT   = "443"
$REVERB_SCHEME = "https"
$REVERB_PATH   = "/ws"

# - VITE_REVERB_APP_KEY - baca dari .env lokal -
$APP_KEY = (Get-Content .env | Select-String "^VITE_REVERB_APP_KEY=(.+)").Matches.Groups[1].Value.Trim()
if (-not $APP_KEY) {
    Write-Error "VITE_REVERB_APP_KEY tidak ditemukan di .env"
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Build image: $FULL_TAG" -ForegroundColor Cyan
Write-Host "  VITE_REVERB_HOST   = $REVERB_HOST" -ForegroundColor Gray
Write-Host "  VITE_REVERB_PORT   = $REVERB_PORT" -ForegroundColor Gray
Write-Host "  VITE_REVERB_SCHEME = $REVERB_SCHEME" -ForegroundColor Gray
Write-Host "  VITE_REVERB_PATH   = $REVERB_PATH" -ForegroundColor Gray
Write-Host "  VITE_REVERB_APP_KEY = $($APP_KEY.Substring(0,8))..." -ForegroundColor Gray
Write-Host "========================================" -ForegroundColor Cyan

# - Build dengan BuildKit + secret + build-args -
$env:DOCKER_BUILDKIT = "1"

# Simpan APP_KEY ke temp file sebagai secret
$SECRET_FILE = [System.IO.Path]::GetTempFileName()
Set-Content -Path $SECRET_FILE -Value $APP_KEY -NoNewline

try {
    docker build `
        --target production `
        --secret id=vite_reverb_app_key,src="$SECRET_FILE" `
        --build-arg VITE_REVERB_HOST="$REVERB_HOST" `
        --build-arg VITE_REVERB_PORT="$REVERB_PORT" `
        --build-arg VITE_REVERB_SCHEME="$REVERB_SCHEME" `
        --build-arg VITE_REVERB_PATH="$REVERB_PATH" `
        -t "${FULL_TAG}" `
        -t "${LATEST}" `
        .

    if ($LASTEXITCODE -ne 0) {
        Write-Error "Build gagal!"
        exit 1
    }

    Write-Host ""
    Write-Host "Build selesai. Push ke Docker Hub..." -ForegroundColor Green
    docker push "${FULL_TAG}"
    docker push "${LATEST}"

    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "Push selesai: $FULL_TAG" -ForegroundColor Green
        Write-Host "Push selesai: $LATEST" -ForegroundColor Green
        Write-Host ""
        Write-Host "Deploy ke production (Swarm stack 'lab'):" -ForegroundColor Yellow
        Write-Host "  docker service update --image $FULL_TAG --update-order start-first lab_app"
        Write-Host "  docker service update --image $FULL_TAG --update-order start-first lab_reverb"
        Write-Host "  docker service update --image $FULL_TAG lab_queue"
        Write-Host "  (lab_nginx tidak perlu update - pakai image nginx:alpine)"
        Write-Host ""
        Write-Host "Atau deploy ulang seluruh stack (lab):" -ForegroundColor Yellow
        Write-Host "  docker stack deploy -c docker-compose.swarm.yml lab"
    }
} finally {
    Remove-Item -Path $SECRET_FILE -Force -ErrorAction SilentlyContinue
}
