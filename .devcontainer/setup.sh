#!/usr/bin/env bash
# Menyiapkan SiKredit sekali saat Codespace dibuat.
set -euo pipefail
cd "$(dirname "$0")/../sikredit"

composer install --no-interaction --no-progress
npm install --no-audit --no-fund
npm run build

[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
touch database/database.sqlite
php artisan migrate:fresh --seed --force

echo "SiKredit siap."
