# Riverbank Bingo

The website for Riverbank Bingo, Kahnawake's live-streamed bingo: a public site
with the game schedule, live broadcast, distributors map, winners and news, and
an admin panel where the owner runs all of it.

Built with **Laravel 13** and the **Filament** admin panel. Runs on ordinary PHP
hosting (Hostinger Business) with the MySQL database that comes with the plan.
No Node build step, no paid services. See [`docs/PLAN.md`](docs/PLAN.md) for the
plan and the reasoning behind the stack.

| | |
| --- | --- |
| Public site | `/` |
| Owner admin | `/admin` |
| Live status (JSON, polled by the pages) | `/api/status` |

---

## Running it locally

Needs PHP 8.3+ with the `intl`, `pdo_sqlite` and `zip` extensions, and Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # sample games, distributors, winners, news
php artisan storage:link
php artisan serve
```

Then open <http://localhost:8000>. The seeder creates two logins for
`/admin`, both with the password `password`:

- `owner@example.com` — what the client will use
- `developer@example.com` — the same, plus developer-only switches

Run the tests with `php artisan test`.

## How it's organised

```
app/Models/             Games, templates, distributors, winners, announcements,
                        posts, pages, settings, contact messages
app/Support/LiveStatus  The one place that decides whether we're live
app/Support/Settings    Phone, email, stream, jackpots: edited once, used everywhere
app/Filament/           The owner admin: resources, settings page, dashboard
app/Http/Controllers/   The public pages
resources/views/        Public templates (layout, pages, partials)
public/css/site.css     Public styles; both designs as colour/type tokens
public/js/site.js       Live-status polling, countdown, flashboard, map
design/                 The two original homepage comps, kept for reference
docs/                   Plan, checklist, client copy
```

### Live mode

A game is live when an owner override says so, or otherwise when now is inside
a scheduled, non-cancelled game's start–end window. **Go Live** and
**End Live** on the dashboard set an override that expires on its own (the
game's end plus 30 minutes, or 3 hours with no game nearby), so the site can't
get stuck live. Every page polls `/api/status` every 30 seconds and flips the
homepage hero without a reload.

### Page copy

The owner edits a fixed set of pages (About, How to Play, Rules, Privacy, …)
but can't create or delete pages. Writing `[phone]` or `[email]` in page copy
inserts the number and address from Settings, so they're only ever changed in
one place.

### The two designs

The public site is built from the **Classic** comp. Set `SITE_THEME=black-gold`
in `.env` to switch to the **Black & Gold** palette and type (same layout).

---

## Deploying to Hostinger (Business plan)

One-time setup, all in hPanel:

1. **PHP:** Advanced → PHP Configuration → PHP 8.3 or newer.
2. **Database:** Databases → MySQL Databases → create a database and user.
   Note the names and password.
3. **SSH:** Advanced → SSH Access → enable, add your public key.
4. **Email:** create a mailbox (e.g. `website@yourdomain`) for the contact
   form to send from.

Then over SSH (replace `yourdomain.com`):

```bash
cd ~/domains/yourdomain.com
git clone https://github.com/Dave-Birnie/Riverbank-Bingo.git app
cd app
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env       # see "Production .env" below
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
php artisan make:filament-user   # the owner's login

# Serve the app's public/ folder as the website
mv ../public_html ../public_html.old
ln -s ~/domains/yourdomain.com/app/public ../public_html
```

**Scheduled jobs:** Advanced → Cron Jobs → every 5 minutes:

```
cd ~/domains/yourdomain.com/app && php artisan schedule:run >> /dev/null 2>&1
```

**Updating** after a push: `./deploy.sh` from the app folder over SSH.

### Production `.env`

```
APP_NAME="Riverbank Bingo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u123456789_riverbank
DB_USERNAME=u123456789_riverbank
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=website@yourdomain.com
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=website@yourdomain.com

SITE_THEME=classic
ALERTS_EMAIL=you@example.com
```

`APP_ENV` other than `production` keeps the site out of search engines, which
is what you want on a staging copy.
