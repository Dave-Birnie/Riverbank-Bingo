#!/usr/bin/env bash
# Runs on the Hostinger server after GitHub Actions uploads a new build.
#
#   release.sh <app dir> <web root>      (both relative to the home folder)
#
# Safe to run on every deploy. On the first run it also points the website's
# public_html at the app (keeping whatever was there as a dated backup).
set -euo pipefail

cd "$HOME"
APP_DIR="$HOME/$1"
WEB_ROOT="$HOME/$2"
PHP="${PHP_BIN:-php}"

cd "$APP_DIR"

# Hostinger can run a different PHP on the command line than on the website.
if ! "$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    echo "The server's command-line PHP is $("$PHP" -r 'echo PHP_VERSION;'); this app needs 8.3 or newer."
    echo "Set the PHP_BIN repository variable to a newer one, e.g. /opt/alt/php83/usr/bin/php"
    exit 1
fi
for ext in intl pdo_mysql mbstring fileinfo; do
    if ! "$PHP" -m | grep -qix "$ext"; then
        echo "PHP extension '$ext' is off. Turn it on in hPanel: Advanced → PHP Configuration → PHP extensions."
        exit 1
    fi
done

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod 600 .env

# Caches from the previous build may name classes that no longer exist.
rm -f bootstrap/cache/*.php
grep -q '^APP_KEY=' .env || echo 'APP_KEY=' >> .env
grep -q '^APP_KEY=base64:' .env || "$PHP" artisan key:generate --force

"$PHP" artisan package:discover
"$PHP" artisan down --retry=15 || true
trap '"$PHP" artisan up' EXIT

"$PHP" artisan migrate --force
"$PHP" artisan db:seed --class=SiteSeeder --force   # adds any new pages; never overwrites edits
"$PHP" artisan filament:assets
[ -L public/storage ] || "$PHP" artisan storage:link
"$PHP" artisan riverbank:admin
"$PHP" artisan optimize
"$PHP" artisan filament:optimize

if [ ! -L "$WEB_ROOT" ]; then
    if [ -e "$WEB_ROOT" ]; then
        BACKUP="$WEB_ROOT.before-app-$(date +%Y%m%d-%H%M%S)"
        mv "$WEB_ROOT" "$BACKUP"
        echo "Moved the old site to $BACKUP"
    fi
    ln -s "$APP_DIR/public" "$WEB_ROOT"
    echo "The website now serves the app."
fi

echo "Deployed $(cat REVISION 2>/dev/null || echo 'build')."
