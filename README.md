# Riverbank Bingo

The website for Riverbank Bingo, Kahnawake's live-streamed bingo, in English and
French: the game schedule, live broadcast, distributors map, winners and news,
plus an admin panel where the owner runs all of it.

Built with **Laravel 13** and the **Filament** admin panel. Runs on ordinary PHP
hosting (Hostinger Business) with the MySQL database that comes with the plan.
No Node build step, no paid services. See [`docs/PLAN.md`](docs/PLAN.md) for the
plan and the reasoning behind the stack.

| | |
| --- | --- |
| Public site (English) | `/`, `/schedule`, `/watch-live`, … |
| Public site (French) | `/fr`, `/fr/horaire`, `/fr/en-direct`, … |
| Owner admin | `/admin` |
| Live status (JSON, polled by the pages) | `/api/status` |

---

## Deploying to the Hostinger temp site

Pushes are tested and deployed by GitHub Actions over SSH
([`.github/workflows/deploy.yml`](.github/workflows/deploy.yml)). Nothing deploys
until the settings below exist. One-time setup, about 15 minutes:

### 1. In hPanel (for the website `lightgrey-hornet-559707.hostingersite.com`)

1. **Remove the old Git deployment.** Advanced → **GIT** → delete the entry for
   the review site, so it can't overwrite the new one. (The review site's files
   are kept as a dated backup folder on the first deploy.)
2. **PHP:** Advanced → **PHP Configuration** → PHP **8.3** or newer. Under
   *PHP extensions* make sure `intl`, `pdo_mysql`, `mbstring` and `fileinfo` are on.
3. **Database:** Databases → **MySQL Databases** → create a database and user.
   Note the database name, user name and password (Hostinger prefixes them,
   e.g. `u123456789_riverbank`).
4. **SSH:** Advanced → **SSH Access** → enable it and note the **IP**, **port**
   (usually 65002) and **username**.
5. **SSH key:** on your computer run
   `ssh-keygen -t ed25519 -f riverbank_deploy -N ""`, then in SSH Access add the
   contents of `riverbank_deploy.pub` as a key. The other file,
   `riverbank_deploy`, goes into GitHub in the next step.

### 2. In GitHub (repo → Settings → Secrets and variables → Actions)

**Variables** tab:

| Name | Value |
| --- | --- |
| `SITE_DOMAIN` | `lightgrey-hornet-559707.hostingersite.com` |
| `DEPLOY_BRANCH` | the branch to deploy, e.g. `ccr-d308271b-s2ytoh` |
| `PHP_BIN` | *(optional)* only if the deploy says the server's PHP is too old, e.g. `/opt/alt/php83/usr/bin/php` |

**Secrets** tab:

| Name | Value |
| --- | --- |
| `SSH_HOST` | the IP from SSH Access |
| `SSH_PORT` | the port from SSH Access |
| `SSH_USER` | the username from SSH Access |
| `SSH_KEY` | the whole contents of `riverbank_deploy` (the private key) |
| `ADMIN_EMAIL` | your email: the first admin login |
| `ADMIN_PASSWORD` | a password for it, at least 12 characters |
| `ENV_FILE` | the site settings: copy the block below and fill in the database and mail values |
| `SSH_KNOWN_HOSTS` | *(optional)* pins the server's identity; output of `ssh-keyscan -p PORT IP` |

`ENV_FILE`:

```
APP_NAME="Riverbank Bingo"
APP_ENV=staging
APP_DEBUG=false
APP_KEY=
APP_URL=https://lightgrey-hornet-559707.hostingersite.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_riverbank
DB_USERNAME=u123456789_riverbank
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=daily
LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=website@yourdomain.com
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=website@yourdomain.com
MAIL_FROM_NAME="Riverbank Bingo"
```

Leave `APP_KEY` empty; the first deploy generates it. `APP_ENV=staging` keeps
the temp site out of search engines; switch to `production` on the real domain.
The `.env` is only written on the first deploy. After that, change it on the
server (hPanel File Manager → `domains/<site>/riverbank/.env`).

### 3. Deploy

Push to the deploy branch, or Actions → **Test and deploy** → Run workflow. The
first deploy:

- uploads the app to `~/domains/<site>/riverbank` (outside the web root);
- creates the database tables, the site pages and an *Every second Tuesday*
  game template;
- creates your admin login from `ADMIN_EMAIL` / `ADMIN_PASSWORD`;
- moves the old `public_html` to `public_html.before-app-<date>` and points
  `public_html` at the app.

Then sign in at `/admin` and:

1. **Logins** → add the owner's login (role *Owner*).
2. **Settings** → contact email, YouTube channel, jackpots, design.
3. **Game Templates** → *Add games*, picking the first game's date.
4. **Distributors**, **Announcements**, **Pages** (English and Français tabs).

Every later push redeploys in about a minute, with the site in maintenance mode
only for the few seconds the database update takes.

**If something fails**, the Actions log names the step. Common ones: *PHP is too
old* (set `PHP_BIN`), *extension is off* (step 1.2), *permission denied
(publickey)* (step 1.5, or `SSH_KEY` pasted incompletely), *rsync: command not
found* (ask Hostinger support to enable rsync for SSH).

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

Then open <http://localhost:8000> and <http://localhost:8000/fr>. Locally the
seeder also creates two admin logins, both with the password `password`
(`owner@example.com`, `developer@example.com`). Sample data and these logins
are only ever created when `APP_ENV=local`.

Run the tests with `php artisan test`.

## How it's organised

```
app/Models/             Games, templates, distributors, winners, announcements,
                        posts, pages, settings, contact messages. Text fields
                        have a French twin column (title / title_fr).
app/Support/LiveStatus  The one place that decides whether we're live
app/Support/Settings    Phone, email, stream, jackpots, design: edited once, used everywhere
app/Support/Locales     The two languages and their URLs
app/Support/Format      Dates, times and money per language
app/Filament/           The owner admin: resources, settings page, dashboard
app/Http/Controllers/   The public pages
resources/views/        Public templates (layout, pages, partials)
lang/fr.json            French for every fixed piece of text on the site
public/css/site.css     Public styles; both designs as tokens
public/js/site.js       Live-status polling, countdown, flashboard, map
deploy/                 What the deploy runs on the server
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

### Two languages

English lives at the root and French under `/fr`, with French URLs
(`/fr/horaire`, `/fr/en-direct`, `/fr/nouvelles`, …). Every page has a link to
the same page in the other language, and `hreflang` tags for search engines.

- **Fixed text** (menus, buttons, headings) is translated in `lang/fr.json`.
- **Owner content** has an *English* and a *Français* tab in the admin. Anything
  left blank in French shows the English, so translating can happen gradually.
- Dates, times and amounts follow each language: *Tuesday, November 10 · 6:45 PM
  ET* / *mardi 10 novembre · 18 h 45 HE*, *$1,000* / *1 000 $*.

### Page copy

The owner edits a fixed set of pages (About, How to Play, Rules, Privacy, …)
but can't create or delete pages. Writing `[phone]` or `[email]` in page copy
inserts the number and address from Settings, so they're only ever changed in
one place.

### The two designs

**Black & Gold** is the default. The owner can switch to **Classic** under
Settings → Design; it changes colours and type only, not content or layout.
