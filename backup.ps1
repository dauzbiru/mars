# MARS - Backup Database + Storage
# Jalankan: powershell -ExecutionPolicy Bypass -File backup.ps1
# Hasil: folder MARS-backup-<tanggal>/ yang siap dicopy ke VPS.

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path

# ---------- Konfigurasi ----------
$dbName     = "mars"
$dbUser     = "root"
$dbPass     = ""                          # isi password MySQL kalo ada
$mysqldump  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$backupDir  = Join-Path $root "MARS-backup-$(Get-Date -Format 'yyyyMMdd-HHmm')"

if (-not (Test-Path $mysqldump)) {
    Write-Host "mysqldump tidak ditemukan di: $mysqldump" -ForegroundColor Red
    Write-Host "Ganti variable `$mysqldump di baris atas script, atau jalankan dari Laragon > MySQL > MySQL CLI."
    exit 1
}

New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Write-Host "Backup ke: $backupDir" -ForegroundColor Cyan

# ---------- 1. Export Database ----------
Write-Host "[1/3] Export database '$dbName'..." -ForegroundColor Yellow
$sqlFile = Join-Path $backupDir "mars.sql"
$args = @("--single-transaction", "--routines", "--triggers")
if ($dbPass -ne "") {
    & $mysqldump -u $dbUser "-p$dbPass" $args $dbName | Out-File -FilePath $sqlFile -Encoding utf8
} else {
    & $mysqldump -u $dbUser $args $dbName | Out-File -FilePath $sqlFile -Encoding utf8
}
if (-not (Test-Path $sqlFile) -or (Get-Item $sqlFile).Length -eq 0) {
    Write-Host "[FAIL] Export DB gagal. Cek password/user MySQL." -ForegroundColor Red
    Read-Host "Tekan Enter untuk keluar..."
    exit 1
}
Write-Host "      -> mars.sql ($([math]::Round((Get-Item $sqlFile).Length/1KB)) KB)" -ForegroundColor Green

# ---------- 2. Copy Storage (foto ttd + file private) ----------
Write-Host "[2/3] Copy storage files..." -ForegroundColor Yellow
$srcPublic = Join-Path $root "storage\app\public"
$srcPrivate = Join-Path $root "storage\app\private"

if (Test-Path $srcPublic) { Copy-Item $srcPublic "$backupDir\storage-app-public" -Recurse -Force; Write-Host "      -> storage/app/public (ttd)" -ForegroundColor Green }
if (Test-Path $srcPrivate) { Copy-Item $srcPrivate "$backupDir\storage-app-private" -Recurse -Force; Write-Host "      -> storage/app/private (tampilan-gerai)" -ForegroundColor Green }

# ---------- 3. .env (sebagai referensi, JANGAN di-deploy mentah2) ----------
Write-Host "[3/3] Copy .env (referensi saja)..." -ForegroundColor Yellow
if (Test-Path "$root\.env") {
    Copy-Item "$root\.env" "$backupDir\.env.reference"
    Write-Host "      -> .env.reference (untuk menyalin kredensial, bukan untuk VPS)" -ForegroundColor DarkYellow
}

# ---------- Ringkasan ----------
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  BACKUP SELESAI" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Isi folder:"
Get-ChildItem $backupDir | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor Gray }

Write-Host ""
Write-Host "Langkah selanjutnya di VPS:"
Write-Host "  1. Copy folder '$([System.IO.Path]::GetFileName($backupDir))' ke VPS."
Write-Host "  2. Buat DB: mysql -u root -e \"CREATE DATABASE mars CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\""
Write-Host "  3. Import:  mysql -u root mars < mars.sql"
Write-Host "  4. Copy storage-app-public -> storage/app/public  (dan private)"
Write-Host "  5. Jangan jalankan `php artisan migrate` (DB sudah penuh)."
Write-Host ""
Read-Host "Tekan Enter untuk menutup..."
