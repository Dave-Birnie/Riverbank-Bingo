# Riverbank Bingo — build plan

Turns the two-design review site into the real, owner-editable website described
in [`production-checklist.md`](production-checklist.md). Working draft; the
decisions marked **Open** are still waiting on an answer.

## Where we are

- The repo holds a static review site: two homepage comps (**Classic** and
  **Black & Gold**) built from Claude Design exports, React + a design-tool
  runtime, deployed to Hostinger by Git.
- Everything in the comps is placeholder: phone `555-000-0000`, distributors,
  winners, session times, the reminders form.
- One comp line now contradicts the business: *"We call every number live on
  this page"*. Games are broadcast on YouTube, so the site embeds that stream
  rather than calling numbers itself.
- Real copy so far: the About page ([`content/about.md`](content/about.md)).

## Recommended stack: WordPress on the existing Hostinger Business plan

The checklist is, item for item, a description of what WordPress already does:
one owner login, editing fixed pages, a media library, blog with scheduled
publishing, preview, revisions with rollback. Building those ourselves would be
most of the project. What's left to build is the Riverbank-specific part: the
game schedule, live mode, distributors, winners and the homepage.

| | WordPress (recommended) | Next.js + Payload CMS |
| --- | --- | --- |
| Runs on Hostinger Business | Natively (PHP + MySQL) | As a managed Node.js app, plus an external Postgres (e.g. Supabase) and media storage |
| Owner editing, media, revisions, scheduling, preview | Built in | Built in (Payload) |
| Backups | Hostinger daily backups cover files + database | Split across Hostinger and the DB provider |
| Staging | Hostinger's one-click WordPress staging | Second app + second database |
| Future AI assistant | WordPress Abilities API + official MCP adapter | Custom API layer |
| Maintenance | Core/plugin updates (auto-update on, few plugins) | npm dependency upgrades, two providers |

Next.js + Payload is the better choice only if keeping the front end in React
matters more than hosting simplicity. **Open: confirm WordPress.**

### What we build

All Riverbank code lives in this repo; WordPress core and content do not.

```
wp-content/themes/riverbank/         chosen design as a theme: templates, CSS,
                                     flashboard animation, map, live-status polling
wp-content/plugins/riverbank-core/   content model, settings, live mode, owner
                                     role, admin dashboard, REST status endpoint,
                                     abilities (for the future assistant)
design/                              the comps, kept as visual reference
docs/                                this plan, checklist, client copy
.github/workflows/deploy.yml         push → staging / production over SSH
```

Third-party plugins kept to a minimum: **Advanced Custom Fields** (field
editing UI), **Simple History** (activity log), **Two Factor** (owner login),
and Hostinger's **LiteSpeed Cache**. The contact form is built into
`riverbank-core`, sent through Hostinger SMTP, with a honeypot and Cloudflare
Turnstile against spam.

The React and design-tool runtime from the comps is dropped. The flashboard and
other interactive bits get rewritten as small vanilla JS, which is lighter and
has no build step.

## Content model

| Content | Type | Key fields |
| --- | --- | --- |
| **Games** | custom post type | date, start/end time, status (scheduled / cancelled / completed), cancellation note, prize lineup (game, pattern, amount), jackpots, card-sales cutoff |
| **Game templates** | custom post type | default times + prize lineup; "Generate the next N games" button (e.g. every second Tuesday) |
| **Distributors** | custom post type | name, address, auto-geocoded lat/lng, phone, hours, pickup or delivery, active toggle |
| **Winners** | custom post type | display name, date, game, amount, photo, story, *consent on file* checkbox (required to publish) |
| **Announcements** | custom post type | banner text, link, show-from / show-until, type (banner or special event) |
| **Blog** | WordPress posts | native editor, scheduling, preview |
| **Pages** | WordPress pages, fixed set | About, How to Play, Rules, Privacy, Become a Distributor, Contact. Owner can edit, not create or delete |
| **Site settings** | options screen | phone, email, socials, YouTube channel / stream URL, offline message, delivery areas, progressive jackpot amounts, live-mode control, assistant on/off (developer-only) |

Phone and email are stored once in Settings and inserted everywhere, including
inside page copy, so a number change can't leave a stale copy on some page.

## Live mode

The trickiest piece, so it gets one source of truth:

- One function decides whether we're live: a manual override if one is set,
  otherwise *now is inside a scheduled, non-cancelled game's start–end window*.
  Homepage, schedule and Watch Live all read from it, so they can't disagree.
- **Go Live** / **End Live** buttons on the dashboard set the override. Overrides
  expire on their own — Go Live at the game's end plus a grace period, End Live
  when the current game window closes — so a forgotten button never leaves the
  site stuck live for two weeks, and the next game still starts automatically.
- Live state is computed on request and exposed at
  `/wp-json/riverbank/v1/status`. Pages poll it every ~30 s and swap the hero
  in place ("Our game is currently in session" + Live Now + Watch Live), so
  page caching can't serve a stale hero and nothing depends on WP-Cron firing
  on time.
- Times are stored in `America/Toronto`, so the EST/EDT switch is handled.
  Unit tests cover the window edges, DST changes, cancellations and overrides.

## Livestream

- Watch Live embeds the YouTube stream. The owner sets the channel ID (the embed
  then picks up whatever is currently live) or pastes a specific stream URL.
- Outside a live window it shows the offline message and a countdown to the next
  game.
- A server cron job checks every 5 minutes during a live window that the channel
  is actually broadcasting (YouTube Data API) and emails the developer if not.
- Uptime monitoring through a free external monitor (UptimeRobot or Better
  Stack), alerts to the developer only.

## Phases

Each phase ends on staging with something the owner can click through.

**0. Decisions and setup**
Answer the open questions below. Create the Hostinger staging site (temporary
subdomain), SSH key, deploy workflow. Local WordPress for development
(WordPress Playground CLI or `wp-env`).

**1. Foundation**
Theme skeleton with the chosen design's header, footer, type and colour tokens.
`riverbank-core` with every content type, the settings screen and the Owner
role. *Done when:* the owner can log in on staging and enter a game, a
distributor and a winner.

**2. Public pages**
Homepage (flashboard hero, next game + countdown, jackpots, announcement banner,
how it works, distributors teaser, winners), Game Schedule with cancellation
overlays, Watch Live, Card Distributors with map pins, Winners, Blog + post, How
to Play, About, Rules, Privacy, Become a Distributor, Contact. Mobile from 320 px
up. *Done when:* every page in the checklist renders from CMS content, nothing
hard-coded.

**3. Live mode and stream**
Status function and endpoint, Go Live / End Live, hero swap, Watch Live states,
stream-down alerts. *Done when:* a test game scheduled five minutes out goes
live and ends on its own, and manual overrides behave as above.

**4. Owner experience**
Custom dashboard (next game, jackpot, active announcements, stream status, live
controls), templates → generated games, trimmed admin menu, field help text,
preview for every content type, revisions on custom fields, activity log.
*Done when:* the owner can run a full cycle — schedule a month, cancel one game,
post a winner, schedule a blog post — without help.

**5. Hardening**
Two-factor login, auto-updates, security headers, restricted API keys (Maps by
referrer, YouTube server-side only), daily backups plus one tested restore,
accessibility pass (WCAG 2.1 AA), performance pass, Playwright smoke tests.

**6. Review and launch**
Owner review and content load on staging, final copy approved, production
domain + SSL, remove all `noindex` / `robots.txt` blocks, verify forms, map,
stream, cron and alerts in production, then watch the first two game nights.

**Later (not in launch)**
- **AI assistant.** Every owner action is registered as a WordPress *ability*
  from Phase 1 (create game, edit distributor, draft post, …) with the same
  permission checks as the admin screens. The assistant later calls those
  abilities, saves drafts only, and the owner confirms before anything
  publishes. Off until purchased; the switch is developer-only.
- **Online card sales.** Needs regulatory authorization first; out of scope.

## Things to flag to the client

These are questions for the client and their regulator, not legal advice:

- **Regulator.** The checklist refers to Ontario gaming rules, but Kahnawake is
  in Quebec and has its own Kahnawake Gaming Commission. Worth confirming which
  authority governs the broadcasts, and whether delivering cards into New York
  needs anything separate.
- **YouTube.** YouTube restricts gambling-related content; get written
  confirmation the bingo broadcasts are allowed (the checklist already asks for
  this).
- **Privacy.** Contact form and winner photos/names collect personal
  information; the Privacy page should reflect Quebec's Law 25, and winners
  need recorded consent (built into the Winners form).
- **Email reminders.** The comp has a reminders sign-up, which isn't in the
  checklist. If kept, it needs CASL-compliant consent and an email provider.

## Open questions

1. **Stack:** WordPress (recommended) or Next.js + Payload?
2. **Design:** Classic or Black & Gold?
3. **Schedule:** "every second Tuesday" means every two weeks? What time does a
   game end, and when do card sales close?
4. **French:** the comps show a *Français* link. Bilingual site at launch, later,
   or not at all? (Doubles content entry; affects every phase.)
5. **Play-along demo:** keep the dabbable practice card on the homepage, or
   just the animated flashboard?
6. **Reminders sign-up:** launch, later, or drop?
7. **Map:** Google Maps (needs a billing account; this site should stay inside
   the free monthly allowance, with a budget cap set) or free OpenStreetMap?
8. **Domain:** what's the production domain, and is it already on Hostinger?
