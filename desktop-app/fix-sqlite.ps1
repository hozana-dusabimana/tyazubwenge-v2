Write-Host "Fixing better-sqlite3 module..." -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Removing old better-sqlite3..." -ForegroundColor Yellow
if (Test-Path "node_modules\better-sqlite3") {
    Remove-Item -Recurse -Force "node_modules\better-sqlite3"
    Write-Host "Removed old better-sqlite3" -ForegroundColor Green
}

Write-Host ""
Write-Host "Step 2: Reinstalling better-sqlite3 from source..." -ForegroundColor Yellow
npm install better-sqlite3 --build-from-source

Write-Host ""
Write-Host "Step 3: Rebuilding better-sqlite3..." -ForegroundColor Yellow
npm rebuild better-sqlite3

Write-Host ""
Write-Host "Done! Try running 'npm start' again." -ForegroundColor Green
Read-Host "Press Enter to exit"

