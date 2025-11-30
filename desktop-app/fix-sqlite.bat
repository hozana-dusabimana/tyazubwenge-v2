@echo off
echo Fixing better-sqlite3 module...
echo.

echo Step 1: Removing old better-sqlite3...
if exist node_modules\better-sqlite3 (
    rmdir /s /q node_modules\better-sqlite3
    echo Removed old better-sqlite3
)

echo.
echo Step 2: Reinstalling better-sqlite3 from source...
call npm install better-sqlite3 --build-from-source

echo.
echo Step 3: Rebuilding better-sqlite3...
call npm rebuild better-sqlite3

echo.
echo Done! Try running 'npm start' again.
pause

