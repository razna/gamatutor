@echo off
setlocal
title Setup SiKredit
cd /d "%~dp0"

echo ==================================================
echo   SETUP SIKREDIT - Sistem Informasi Pengajuan Kredit
echo ==================================================
echo.

where php >nul 2>nul || (echo [GAGAL] PHP belum terpasang. Pasang Laravel Herd terlebih dahulu. & goto gagal)
where composer >nul 2>nul || (echo [GAGAL] Composer belum terpasang. Pasang Laravel Herd terlebih dahulu. & goto gagal)
where npm >nul 2>nul || (echo [GAGAL] Node.js belum terpasang. Pasang Node.js LTS dari nodejs.org. & goto gagal)

echo [1/5] Mengunduh library PHP (beberapa menit)...
call composer install --no-interaction || goto gagal

echo.
echo [2/5] Mengunduh library tampilan...
call npm install || goto gagal

echo.
echo [3/5] Menyusun file tampilan...
call npm run build || goto gagal

echo.
echo [4/5] Menyiapkan konfigurasi...
if not exist .env (
    copy .env.example .env >nul
    php artisan key:generate --force || goto gagal
)
if not exist database\database.sqlite type nul > database\database.sqlite

echo.
echo [5/5] Membuat database dan data contoh...
php artisan migrate:fresh --seed --force || goto gagal

echo.
echo ==================================================
echo   SETUP BERHASIL!
echo   Klik dua kali file "jalankan.bat" untuk membuka web.
echo ==================================================
pause
exit /b 0

:gagal
echo.
echo Setup berhenti karena ada kesalahan. Baca pesan di atas,
echo atau salin pesannya untuk ditanyakan.
pause
exit /b 1
