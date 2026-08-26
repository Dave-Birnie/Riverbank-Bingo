# Riverbank Bingo — temp review site

Two homepage design options for Riverbank Bingo, built as a plain static site
so they can be hosted side by side on Hostinger for client review.

| Design | Page | URL once deployed |
| --- | --- | --- |
| **Classic** (light) | `index.html` | `https://yourdomain.com/` |
| **Black & Gold** | `black-gold.html` | `https://yourdomain.com/black-gold` |

Both pages carry a **light-bulb button** in the bottom-right corner that swaps
between the two designs. Your scroll position carries across, so it reads like a
theme switch rather than a jump to a different page. The bulb is lit on the
Classic design (click to turn the lights down) and dimmed on Black & Gold
(click to turn them back up).

Each design also has its own URL, so you can send a client straight to one.

---

## Deploying to Hostinger

The site is generated **at the repository root** — `index.html` is the first
thing in the repo, not tucked inside a `public/` folder. Hostinger's Git
integration clones the whole repository into your web root, so this is what
makes the site serve from `/` rather than `/public/`.

### Option A — connect the GitHub repo (recommended)

1. hPanel → **Websites →** your domain → **Advanced → GIT**
2. **Repository:** `https://github.com/Dave-Birnie/Riverbank-Bingo.git`
3. **Branch:** `claude/riverbank-bingo-temp-site-jlhsdq`
4. **Directory:** leave blank to deploy into `public_html`
5. **Create**, then hit **Deploy** on the entry it adds

The repo is public, so no deploy key or SSH setup is needed. To push updates
later, commit and press **Deploy** again — or use **Auto-Deployment** to have
Hostinger pull on every push.

`public_html` must be empty first — delete Hostinger's placeholder
(`index.html` / `default.php`) or the clone will refuse to run.

### Option B — manual upload

1. hPanel → **Files → File Manager**, open `public_html`
2. Delete the placeholder files
3. Upload everything from this repo *except* `source/`, `tools/`, `README.md`
   and `.gitignore` — i.e. `index.html`, `black-gold.html`, `assets/`,
   `robots.txt` and `.htaccess`
4. File Manager → **Settings → Show hidden files** to confirm `.htaccess`
   arrived

### What gets served

```
index.html          Classic homepage
black-gold.html     Black & Gold homepage
robots.txt          Keeps the review site out of search engines
.htaccess           Compression, cache headers, /black-gold pretty URL
assets/js/          React + the Design Component runtime that drives the demo
assets/fonts/       Bevan, Source Sans 3, Playfair Display, Jost (self-hosted)
assets/img/         Logo badges, wordmark, favicon
```

About 1.2 MB. The repo also carries `source/`, `tools/` and this README, which
a Git deploy drops into the web root alongside the site — `.htaccess` returns
404 for those, and for `.git/`, which would otherwise let anyone download the
full repository history.

### A note on search engines

This is an unfinished review site, so both pages are marked `noindex, nofollow`
and `robots.txt` disallows crawling. **Remove those before this becomes the real
site** — see `robots.txt`, the `X-Robots-Tag` header in `.htaccess`,
and the `robots` meta tag in each page (the `HEAD_EXTRA` / `ROBOTS` / `HTACCESS`
blocks in `tools/build.py`).

---

## Rebuilding

The site files are generated from the two original design exports in `source/`.
They are committed to the repo, so you only need this if you get updated exports.

```bash
python3 tools/build.py
```

The files in `source/` are single-file Claude Design exports: a JSON manifest of
base64 assets plus the page HTML, unpacked in the browser by a loader script.
Serving those as-is works, but every visitor downloads ~1.1 MB and rebuilds each
asset in JavaScript before anything appears — and the two files ship duplicate
copies of React, the fonts and the shared logos.

`tools/build.py` unpacks them ahead of time instead. It:

- extracts every asset to a real file with a readable name, sharing assets that
  are byte-identical across the two designs (React, the fonts, the logo badge
  and the wordmark are stored once, not twice);
- rewrites the pages to load those assets by path, and to pull React from
  `assets/js/` rather than a CDN;
- drops the now-unused Google Fonts preconnects;
- adds the page title, favicon and meta tags;
- injects the light-bulb switcher.

The design markup itself is left byte-for-byte identical to the export — the
switcher block is the only thing added to either page.

### Editing the designs

These pages are Design Components: static markup plus a `<script type="text/x-dc">`
block holding the interactive logic for the live caller and the printable card.
The safe path is to change the design in Claude Design, re-export, drop the new
file into `source/` under the same name, and re-run the build.

If you edit `index.html` or `black-gold.html` by hand, note that the next build
overwrites them. The build only ever touches `index.html`, `black-gold.html`,
`assets/`, `robots.txt` and `.htaccess` — never `source/`, `tools/` or `.git/`.

---

## Placeholder content

The designs are comps, so the copy is deliberately provisional. Before this goes
live, replace:

- the support phone number `555-000-0000`;
- the distributor names, hours and the "drop a real map here" placeholder;
- the winner quotes and photo slots, all marked *Placeholder name*;
- the countdown target and session times;
- the `href="#"` links in the top bar and footer.

The bingo demo is explicitly labelled as free play — no money, no prizes.
