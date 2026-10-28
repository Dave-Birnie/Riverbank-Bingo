# Riverbank Bingo — build plan

Turns the two-design review site into the real, owner-editable website described
in [`production-checklist.md`](production-checklist.md).

## Stack

**Laravel (PHP) with a Filament admin panel, on the Hostinger Business plan
already in place, using the MySQL database that comes with it.**

Why this and not something else:

- **No new monthly costs.** Hostinger Business runs PHP and includes MySQL
  databases, SSH, cron jobs, free SSL and daily backups. Nothing here needs
  a paid database, a separate app host or a paid map or CMS service.
- **Plain shared hosting is enough.** Laravel runs like any PHP site: no
  long-running Node process, no build step on the server. Uploaded photos
  sit on the same disk and are covered by Hostinger's backups.
- **A real admin panel out of the box.** Filament gives the owner proper
  editing screens (forms, tables, uploads, a rich text editor, dashboard
  widgets, login with optional two-factor) without us building a CMS.
- **A real web app, not a page builder.** The game schedule, live mode,
  distributors and winners are proper data with rules and tests, and the
  future AI assistant can drive the same code.

| Item | Choice | Monthly cost |
| --- | --- | --- |
| Hosting | Existing Hostinger Business plan | already paid |
| Database | MySQL included with the plan | $0 |
| Admin panel | Filament (open source) | $0 |
| Map | OpenStreetMap + Leaflet (no API key, no billing account) | $0 |
| Live stream | YouTube embed | $0 |
| Email | The plan's mailbox, over SMTP | $0 |
| Uptime alerts | UptimeRobot free tier | $0 |

Locally and in tests the app uses SQLite; production uses MySQL. Laravel
makes that a setting, not a code change.

## What's built so far

- **Content model:** games, game templates, distributors, winners,
  announcements, blog posts, a fixed set of pages, site settings, and contact
  messages.
- **Live mode:** one service decides whether a game is live (manual override
  first, otherwise the schedule), with self-expiring Go Live / End Live and a
  `/api/status` endpoint the pages poll every 30 seconds.
- **Public site** in the Classic design: home (animated flashboard, swapped
  for "Our game is currently in session" while live), schedule with
  cancellations, Watch Live, distributors with a map, winners, news, About,
  How to Play, Rules, Privacy, Become a Distributor and Contact. The Black &
  Gold design is one setting (`SITE_THEME=black-gold`) and still needs a
  visual pass.
- **Owner admin** at `/admin`: every content type, site settings, and a
  dashboard with Go Live / End Live.
- **Tests** for live-mode edge cases (window edges, cancellations, overrides,
  daylight saving), schedule generation, scheduled posts, consent on
  winners, and every public page.

## How live mode works

- One function decides: a manual override if one is in force, otherwise
  *now is inside a scheduled, non-cancelled game's start–end window*. Home,
  Schedule, Watch Live and the status endpoint all read it, so they can't
  disagree.
- **Go Live** lasts until the game's scheduled end plus 30 minutes, or 3 hours
  if there's no game nearby. **End Live** holds the site off until the game's
  window closes. Both expire on their own, so a forgotten button can't leave
  the site stuck, and the next game still starts automatically.
- Live state is worked out on each request rather than by a timed job, so it
  flips on the minute even if cron is late.
- Times are stored in Eastern time (`America/Toronto`), so the EST/EDT switch
  is handled.

## Remaining phases

**1. Staging on Hostinger.** Create the database and a staging subdomain,
deploy, create the owner login, load real content. *Done when:* the owner can
log in on staging and run a full cycle: schedule a month of games, cancel
one, post a winner, schedule a news post.

**2. Owner polish.** Change history with one-click restore, a media library
for reusable images, address lookup for map pins, field help text written for
the owner, and a review of the admin wording with them.

**3. Alerts and hardening.** Server cron for scheduled jobs; an email to the
developer when a game is live but the YouTube channel isn't broadcasting;
UptimeRobot; two-factor login for the owner; tested backup restore;
accessibility pass (WCAG 2.1 AA); production security headers.

**4. Launch.** Final copy approved on staging, production domain and SSL,
search engines allowed, verify forms, map, stream and cron in production,
then watch the first two game nights.

**Later (not in launch)**
- **AI assistant.** Owner actions already live in the models and services
  (generate games, cancel, go live), not in the admin screens, so a chat
  assistant can call the same code. It saves drafts only, and the owner
  confirms before anything publishes. Off until purchased; the switch is
  developer-only (`assistant_enabled`).
- **Online card sales.** Needs regulatory authorization first; out of scope.

## Things to flag to the client

These are questions for the client and their regulator, not legal advice:

- **Regulator.** The checklist refers to Ontario gaming rules, but Kahnawake is
  in Quebec and has its own Kahnawake Gaming Commission. Worth confirming which
  authority governs the broadcasts, and whether delivering cards into New York
  needs anything separate.
- **YouTube.** YouTube restricts gambling-related content; get written
  confirmation the bingo broadcasts are allowed.
- **Privacy.** The contact form and winner names and photos collect personal
  information; the Privacy page should reflect Quebec's Law 25. Winners can't
  be published without a recorded consent box being ticked.
- **Rules and Privacy copy** have to come from the client; the pages are
  placeholders.

## Open questions

1. **Design:** Classic (built) or Black & Gold (a theme switch plus a visual
   pass)?
2. **Schedule:** "every second Tuesday" means every two weeks? What time does a
   game end, and when do card sales close? (Sample data assumes 6:45–9:15 PM,
   sales close 6:00 PM.)
3. **French:** bilingual at launch, later, or not at all? Affects every page
   and the admin, so better decided early.
4. **Play-along demo / reminders sign-up:** the comps had a practice card and an
   email reminders form. Both are left out; add either?
5. **Domain:** what's the production domain, and is it already on Hostinger?
