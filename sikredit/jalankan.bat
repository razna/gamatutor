@echo off
title SiKredit - JANGAN TUTUP JENDELA INI selama web dipakai
cd /d "%~dp0"

if not exist vendor (
    echo Aplikasi belum di-setup. Jalankan "setup.bat" terlebih dahulu.
    pause
    exit /b 1
)

echo ==================================================
echo   SiKredit berjalan di http://127.0.0.1:8000
echo   Browser akan terbuka otomatis.
echo   Untuk mematikan: tutup jendela ini atau tekan Ctrl+C
echo ==================================================
start "" cmd /c "timeout /t 3 /nobreak >nul & start http://127.0.0.1:8000"
php artisan serve --port=8000
pause
