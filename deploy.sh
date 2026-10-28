#!/usr/bin/env bash
# Update the site in place on Hostinger. Run from the app folder over SSH.
set -euo pipefail

php artisan down --retry=30 || true
trap 'php artisan up' EXIT

git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --class=SiteSeeder --force   # adds any new pages; never overwrites edits
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
