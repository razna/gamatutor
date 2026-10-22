@echo off
rem Buka ulang di jendela yang tetap terbuka agar pesan error selalu terlihat
if not "%~1"=="--run" (
    cmd /k ""%~f0" --run"
    exit /b
)
setlocal
title Setup SiKredit
cd /d "%~dp0"

echo ==================================================
echo   SETUP SIKREDIT - Sistem Informasi Pengajuan Kredit
echo ==================================================
echo Folder: %cd%
echo.

where php >nul 2>nul || (echo [GAGAL] PHP belum terpasang. Pasang Laravel Herd lalu restart komputer. & goto gagal)
where composer >nul 2>nul || (echo [GAGAL] Composer belum terpasang. Pasang Laravel Herd lalu restart komputer. & goto gagal)
where npm >nul 2>nul || (echo [GAGAL] Node.js belum terpasang. Pasang Node.js LTS lalu restart komputer. & goto gagal)
call php -v || goto gagal
echo.

echo [1/5] Mengunduh library PHP - beberapa menit...
call composer install --no-interaction || goto gagal

echo.
echo [2/5] Mengunduh library tampilan...
call npm install || goto gagal

echo.
echo [3/5] Menyusun file tampilan...
call npm run build || goto gagal

echo.
echo [4/5] Menyiapkan konfigurasi...
if not exist .env copy .env.example .env >nul
findstr /r /c:"^APP_KEY=base64" .env >nul || call php artisan key:generate --force || goto gagal
if not exist database\database.sqlite type nul > database\database.sqlite

echo.
echo [5/5] Membuat database dan data contoh...
call php artisan migrate:fresh --seed --force || goto gagal

echo.
echo ==================================================
echo   SETUP BERHASIL!
echo   Tutup jendela ini, lalu klik dua kali "jalankan.bat".
echo ==================================================
goto :eof

:gagal
echo.
echo ==================================================
echo   SETUP GAGAL. Screenshot jendela ini dan kirimkan
echo   untuk ditanyakan. Jendela tidak akan tertutup sendiri.
echo ==================================================
goto :eof
