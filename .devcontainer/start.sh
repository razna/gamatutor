#!/usr/bin/env bash
# Menyalakan server SiKredit di port 8000 (jika belum berjalan).
cd "$(dirname "$0")/../sikredit"

if ! (exec 3<>/dev/tcp/127.0.0.1/8000) 2>/dev/null; then
    nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/sikredit-server.log 2>&1 &
fi

echo ""
echo "=================================================================="
echo "  SiKredit berjalan di port 8000."
echo "  Buka tab PORTS di bawah, lalu klik ikon globe pada port 8000."
echo "  Login: admin@sikredit.test / password123"
echo "=================================================================="
