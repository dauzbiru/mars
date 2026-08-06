# MARS - Deploy Script untuk Windows VPS
# Jalankan: powershell -ExecutionPolicy Bypass -File deploy.ps1
# Pastikan Laragon sudah berjalan (PHP & MySQL aktif)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path

function Step([string]$msg) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  $msg" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
}

function CheckCmd([string]$name, [string]$probe) {
    try { & $probe 2>$null | Out-Null; return $true } catch { return $false }
}

Write-Host "MARS - Deployment to VPS" -ForegroundColor Green
Write-Host "Root : $root" -ForegroundColor Gray

# ---------------------------------------------------------------
Step "1/9  Cek Prasyarat"
# ---------------------------------------------------------------
$phpOk = CheckCmd "php" "php -v"
$composerOk = CheckCmd "composer" "composer --version"
$nodeOk = CheckCmd "node" "node -v"
$npmOk = CheckCmd "npm" "npm --version"
$pythonOk = CheckCmd "python" "python --version"

Write-Host "  PHP     : $(if($phpOk){'OK'}else{'MISSING'})"
Write-Host "  Composer: $(if($composerOk){'OK'}else{'MISSING'})"
Write-Host "  Node.js : $(if($nodeOk){'OK'}else{'MISSING'})"
Write-Host "  npm     : $(if($npmOk){'OK'}else{'MISSING'})"
Write-Host "  Python  : $(if($pythonOk){'OK'}else{'MISSING'})"

if (-not $phpOk)    { Write-Host "  [X] PHP tidak ditemukan. Aktifkan PHP di Laragon (Start > PHP version)." -ForegroundColor Red }
if (-not $composerOk){ Write-Host "  [X] Composer tidak ditemukan. Install dari https://getcomposer.org" -ForegroundColor Red }
if (-not $nodeOk)   { Write-Host "  [X] Node.js tidak ditemukan. Install dari https://nodejs.org" -ForegroundColor Red }
if (-not $pythonOk) { Write-Host "  [X] Python tidak ditemukan. Install dari https://python.org" -ForegroundColor Red }

if (-not ($phpOk -and $composerOk -and $nodeOk)) {
    Write-Host ""
    Write-Host "Prasyarat belum lengkap. Perbaiki dulu lalu jalankan ulang." -ForegroundColor Red
    exit 1
}

# ---------------------------------------------------------------
Step "2/9  Composer Install"
# ---------------------------------------------------------------
Push-Location $root
try {
    & composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer install gagal" }
} finally { Pop-Location }

# ---------------------------------------------------------------
Step "3/9  File .env"
# ---------------------------------------------------------------
if (-not (Test-Path "$root\.env")) {
    Copy-Item "$root\.env.example" "$root\.env"
    Write-Host ".env dibuat dari .env.example. Periksa & isi kredensial DB sebelum lanjut!" -ForegroundColor Yellow
    Write-Host "  - DB_DATABASE, DB_USERNAME, DB_PASSWORD" -ForegroundColor Yellow
    Write-Host "  - APP_URL, GEMINI_API_KEY" -ForegroundColor Yellow
    Read-Host "  Tekan Enter setelah mengisi .env..."
} else {
    Write-Host ".env sudah ada. Skip."
}

# ---------------------------------------------------------------
Step "4/9  Generate App Key + Migrate"
# ---------------------------------------------------------------
Push-Location $root
try {
    if (-not (Select-String -Path "$root\.env" -Pattern '^APP_KEY=.+')) {
        & php artisan key:generate --force
        if ($LASTEXITCODE -ne 0) { throw "key:generate gagal" }
    } else {
        Write-Host "APP_KEY sudah ada. Skip."
    }
    & php artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw "migrate gagal" }
} finally { Pop-Location }

# ---------------------------------------------------------------
Step "5/9  npm Install + Build CSS"
# ---------------------------------------------------------------
Push-Location $root
try {
    & npm install
    if ($LASTEXITCODE -ne 0) { throw "npm install gagal" }
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw "npm run build gagal" }
} finally { Pop-Location }

# ---------------------------------------------------------------
Step "6/9  Storage Link"
# ---------------------------------------------------------------
Push-Location $root
try {
    & php artisan storage:link
    if ($LASTEXITCODE -ne 0) { Write-Host "storage:link warning (mungkin sudah ada)" -ForegroundColor DarkYellow }
} finally { Pop-Location }

# ---------------------------------------------------------------
Step "7/9  Cache Laravel"
# ---------------------------------------------------------------
Push-Location $root
try {
    & php artisan optimize:clear
    & php artisan config:cache
    & php artisan route:cache
    & php artisan view:cache
    Write-Host "Cache lama dibersihkan, Config/Route/View cache dibuat." -ForegroundColor Green
} finally { Pop-Location }

# ---------------------------------------------------------------
Step "8/9  Python Library (scripts/)"
# ---------------------------------------------------------------
if ($pythonOk) {
    & python -m pip install --upgrade pywin32 xlwings pypdf
    if ($LASTEXITCODE -eq 0) {
        Write-Host "pywin32, xlwings, pypdf terinstall." -ForegroundColor Green
    } else {
        Write-Host "[WARN] Pip install gagal. Cek koneksi internet / Python." -ForegroundColor Yellow
    }
} else {
    Write-Host "Python tidak ada. Lewati install library." -ForegroundColor DarkYellow
}

# ---------------------------------------------------------------
Step "9/9  Daftarkan Scheduler (Task Scheduler)"
# ---------------------------------------------------------------
$taskName = "MARS-Scheduler"
$action = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -WindowStyle Hidden -Command `"cd '$root'; php artisan schedule:run`""
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

try {
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Force | Out-Null
    Write-Host "Task '$taskName' terdaftar (tiap 1 menit)." -ForegroundColor Green
} catch {
    Write-Host "[WARN] Gagal mendaftarkan Task Scheduler: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host "  Buat manual: Task Scheduler > Create Task > Program: php, Arg: artisan schedule:run, Run every 1 minute." -ForegroundColor Gray
}

# ---------------------------------------------------------------
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  DEPLOY SELESAI!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah terakhir manual:"
Write-Host "  1. Pastikan database 'mars' sudah dibuat di MySQL."
Write-Host "  2. Cek .env (DB kredensial, APP_URL)."
Write-Host "  3. Jalankan server:  start.bat"
Write-Host "     - atau langsung: php artisan serve --host=0.0.0.0 --port=80"
Write-Host ""
Read-Host "Tekan Enter untuk menutup..."
