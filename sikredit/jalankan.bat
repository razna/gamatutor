@echo off
rem Buka ulang di jendela yang tetap terbuka agar pesan error selalu terlihat
if not "%~1"=="--run" (
    cmd /k ""%~f0" --run"
    exit /b
)
title SiKredit - JANGAN TUTUP JENDELA INI selama web dipakai
cd /d "%~dp0"

if not exist vendor\autoload.php goto belum
if not exist .env goto belum
if not exist database\database.sqlite goto belum

echo ==================================================
echo   SiKredit sedang dinyalakan...
echo   Buka alamat yang tertulis "Server running on" di bawah.
echo   Browser akan terbuka otomatis ke http://127.0.0.1:8000
echo   Untuk mematikan: tutup jendela ini.
echo ==================================================
echo.
start "" cmd /c "timeout /t 5 /nobreak >nul & start http://127.0.0.1:8000"
call php artisan serve --host=127.0.0.1 --port=8000
echo.
echo Server berhenti. Jika ada pesan error di atas, screenshot dan kirimkan.
goto :eof

:belum
echo Aplikasi belum di-setup dengan lengkap.
echo Klik dua kali "setup.bat" dan tunggu sampai muncul SETUP BERHASIL!
goto :eof
