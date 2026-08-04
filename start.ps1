Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   MARS - Laravel Dev Server" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

# Start Laravel dev server
Write-Host "[1/2] Starting php artisan serve..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$projectRoot'; php artisan serve" -WindowStyle Normal

# Start Cloudflare Tunnel (if cloudflared.exe exists)
$cloudflared = Join-Path $projectRoot "cloudflared.exe"
if (Test-Path $cloudflared) {
    Write-Host "[2/2] Starting Cloudflare Tunnel..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$projectRoot'; & '$cloudflared' tunnel --url http://localhost:8000" -WindowStyle Normal
} else {
    Write-Host "[2/2] cloudflared.exe not found, skipping tunnel." -ForegroundColor DarkYellow
}

Write-Host ""
Write-Host "Laravel server running at: http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
