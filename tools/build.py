#!/usr/bin/env python3
"""
Build the served pages from the editable templates in design/.

    design/classic.dc.html     ->  index.html
    design/black-gold.dc.html  ->  black-gold.html

Each template carries two markers that this script fills in:

    <!--BUILD:HEAD-->     title, meta, favicon and the local script tags
    <!--BUILD:TOGGLE-->   the light-bulb design switcher

design/ is the source of truth — edit the design there, not in the generated
pages. tools/extract.py regenerates design/ from the original exports in
source/ and overwrites any edits, so it is not part of the normal loop.

The pages are written to the repository root rather than a subfolder, because
Hostinger's Git deployment clones the whole repo into the web root — one level
down they would serve from /public/ instead of /.

Usage:  python3 tools/build.py
"""

import json
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DESIGN = ROOT / "design"

# The site is generated straight into the repo root (see the module docstring).
# Only these paths are ever written or removed by the build — everything else in
# the repo (source/, tools/, README.md, .git/) is left alone.
OUT = ROOT
# assets/ is produced by tools/extract.py and is NOT listed here — the build
# must not delete files it cannot regenerate.
GENERATED = ["index.html", "black-gold.html", "robots.txt", ".htaccess"]

# --------------------------------------------------------------------------
# The two designs
# --------------------------------------------------------------------------

THEMES = [
    {
        "key": "classic",
        "template": "classic.dc.html",
        "page": "index.html",
        "other_page": "black-gold.html",
        "title": "Riverbank Bingo — Play at Home",
        "label": "Classic",
        "other_label": "Black & Gold",
        # bulb is lit on this design; clicking it turns the lights down
        "bulb_on": True,
        "btn": {
            "bg": "#101319",
            "border": "rgba(201,183,142,.45)",
            "bulb": "#F0C97F",
            "glow": "rgba(240,201,127,.55)",
            "text": "#F4EBD8",
            "ring": "rgba(15,107,96,.55)",
        },
    },
    {
        "key": "black-gold",
        "template": "black-gold.dc.html",
        "page": "black-gold.html",
        "other_page": "index.html",
        "title": "Riverbank Bingo — Play at Home",
        "label": "Black & Gold",
        "other_label": "Classic",
        # bulb is dimmed here; clicking it turns the lights back up
        "bulb_on": False,
        "btn": {
            "bg": "#141210",
            "border": "rgba(201,164,76,.5)",
            "bulb": "#8A8272",
            "glow": "rgba(201,164,76,.0)",
            "text": "#E8CE85",
            "ring": "rgba(201,164,76,.6)",
        },
    },
]


# --------------------------------------------------------------------------
# The light-bulb toggle
# --------------------------------------------------------------------------

def toggle_markup(theme):
    """Floating light-bulb button that swaps to the other design."""
    c = theme["btn"]
    on = theme["bulb_on"]
    tip = "Switch to %s" % theme["other_label"]

    # A lit bulb gets filaments and rays; a dim one is drawn flat.
    rays = (
        """
      <g class="rb-rays" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
        <path d="M12 1.6v1.8M20.4 5.1l-1.3 1.3M22.4 13h-1.8M3.4 13H1.6M4.9 5.1l1.3 1.3"/>
      </g>"""
        if on
        else ""
    )

    return f"""
<!-- Design switcher — swaps between the Classic and Black & Gold homepages. -->
<style>
  #rb-theme-toggle {{
    position: fixed; right: 20px; bottom: 20px; z-index: 2147483000;
    display: inline-flex; align-items: center; gap: 10px;
    padding: 12px 16px 12px 14px; border-radius: 999px;
    background: {c['bg']}; border: 1px solid {c['border']};
    color: {c['text']}; cursor: pointer;
    font: 600 13px/1 'Source Sans 3', system-ui, -apple-system, sans-serif;
    letter-spacing: .06em; text-transform: uppercase; text-decoration: none;
    box-shadow: 0 10px 30px rgba(0,0,0,.35);
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    -webkit-tap-highlight-color: transparent;
  }}
  #rb-theme-toggle:hover {{ transform: translateY(-2px); box-shadow: 0 16px 38px rgba(0,0,0,.45); }}
  #rb-theme-toggle:active {{ transform: translateY(0); }}
  #rb-theme-toggle:focus-visible {{ outline: 3px solid {c['ring']}; outline-offset: 3px; }}
  #rb-theme-toggle svg {{ width: 24px; height: 24px; flex: none; color: {c['bulb']}; display: block; }}
  #rb-theme-toggle .rb-bulb-glass {{ filter: drop-shadow(0 0 6px {c['glow']}); }}
  #rb-theme-toggle .rb-toggle-label {{ white-space: nowrap; }}

  /* Pulse the rays gently so the lit bulb reads as "on". */
  @keyframes rb-bulb-pulse {{ 0%,100% {{ opacity: .55; }} 50% {{ opacity: 1; }} }}
  #rb-theme-toggle .rb-rays {{ animation: rb-bulb-pulse 2.6s ease-in-out infinite; }}

  /* On small screens drop to a round icon-only button. */
  @media (max-width: 640px) {{
    #rb-theme-toggle {{ right: 14px; bottom: 14px; padding: 13px; border-radius: 50%; }}
    #rb-theme-toggle .rb-toggle-label {{ display: none; }}
  }}
  @media (prefers-reduced-motion: reduce) {{
    #rb-theme-toggle, #rb-theme-toggle .rb-rays {{ transition: none; animation: none; }}
  }}
  @media print {{ #rb-theme-toggle {{ display: none !important; }} }}
</style>

<a id="rb-theme-toggle" href="{theme['other_page']}" title="{tip}" aria-label="{tip}">
  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">{rays}
    <path class="rb-bulb-glass"
          d="M12 3.6a6 6 0 0 0-3.6 10.8c.6.45.95 1.05 1.02 1.7l.1.9h5l.1-.9c.07-.65.42-1.25 1.02-1.7A6 6 0 0 0 12 3.6Z"
          fill="{'currentColor' if on else 'none'}" fill-opacity="{'.28' if on else '0'}"
          stroke="currentColor" stroke-width="1.6"/>
    <path d="M9.7 19.2h4.6M10.4 21.4h3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    {'<path d="M10.6 16.4c0-2 .6-3 1.4-4 .8 1 1.4 2 1.4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".85"/>' if on else ''}
  </svg>
  <span class="rb-toggle-label">{theme['other_label']}</span>
</a>

<script>
/* Carry the scroll position across the swap so the button behaves like a
   theme toggle rather than a link to a different page. */
(function () {{
  var KEY = 'rb-theme-scroll';
  var btn = document.getElementById('rb-theme-toggle');

  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

  btn.addEventListener('click', function () {{
    try {{
      sessionStorage.setItem(KEY, String(window.scrollY || window.pageYOffset || 0));
      localStorage.setItem('rb-theme-preference', {json.dumps(theme['key'])});
    }} catch (e) {{}}
  }});

  var saved = null;
  try {{
    saved = sessionStorage.getItem(KEY);
    sessionStorage.removeItem(KEY);
  }} catch (e) {{}}
  if (saved === null) return;

  var y = parseInt(saved, 10);
  if (!(y > 0)) return;

  /* The page hydrates and its webfonts settle after load, both of which move
     content. Keep re-applying the offset until it holds still, rather than
     for a fixed number of frames. */
  function apply() {{
    var max = document.documentElement.scrollHeight - window.innerHeight;
    window.scrollTo(0, Math.max(0, Math.min(y, max)));
  }}

  var deadline = Date.now() + 3000;
  (function settle() {{
    apply();
    if (Date.now() < deadline) requestAnimationFrame(settle);
  }})();

  window.addEventListener('load', apply);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(apply);
}})();
</script>
"""


# --------------------------------------------------------------------------
# Page assembly
# --------------------------------------------------------------------------

HEAD_EXTRA = """<title>{title} · {label}</title>
<meta name="description" content="Riverbank Bingo is a play-at-home game. Pick up a card from a local distributor, follow the live caller from your own table, and phone in the moment you have BINGO. 18+ approved adult play.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="{theme_color}">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/img/logo-badge-gold.png">
<script src="assets/js/react.js"></script>
<script src="assets/js/react-dom.js"></script>
<script src="assets/js/dc-runtime.js"></script>"""

THEME_COLOR = {"classic": "#FBF6EC", "black-gold": "#0A0A0C"}


def build_page(theme):
    src = DESIGN / theme["template"]
    if not src.exists():
        sys.exit(f"missing {src.relative_to(ROOT)} — run: python3 tools/extract.py")
    page = src.read_text(encoding="utf-8")

    # React must be on `window` before dc-runtime boots, otherwise the runtime
    # falls back to fetching React from a CDN.
    head = HEAD_EXTRA.format(
        title=theme["title"],
        label=theme["label"],
        theme_color=THEME_COLOR[theme["key"]],
    )

    for marker, content in (("<!--BUILD:HEAD-->", head),
                            ("<!--BUILD:TOGGLE-->", toggle_markup(theme))):
        if marker not in page:
            sys.exit(f"{src.name}: {marker} marker is missing")
        page = page.replace(marker, content, 1)

    out_page = OUT / theme["page"]
    out_page.write_text(page, encoding="utf-8")

    missing = [m.group(0) for m in re.finditer(r'(?:src|href)="(assets/[^"]+)"', page)
               if not (ROOT / m.group(1)).exists()]
    if missing:
        sys.exit(f"{out_page.name}: references missing asset(s), e.g. {missing[0]}")

    print(f"  {theme['page']:<16} {out_page.stat().st_size / 1024:7.1f} KB")


FAVICON = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <circle cx="32" cy="32" r="30" fill="#0E3648"/>
  <circle cx="32" cy="32" r="21" fill="#F5C842"/>
  <circle cx="32" cy="32" r="13" fill="#FDF8EE"/>
  <text x="32" y="39" font-family="Georgia, serif" font-size="19" font-weight="700"
        text-anchor="middle" fill="#0E3648">1</text>
</svg>
"""

HTACCESS = """# Riverbank Bingo — temp review site

DirectoryIndex index.html

# Deploying this repo with Hostinger's Git integration clones the whole
# repository into the web root, so the non-site files land there too. Hide
# them: .git would otherwise let anyone download the full repo history.
RedirectMatch 404 ^/\\.git(/|$)
RedirectMatch 404 ^/(source|tools)(/|$)
RedirectMatch 404 ^/(README\\.md|\\.gitignore)$

# Friendly URL for the alternate design: /black-gold
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^black-gold/?$ black-gold.html [L]
</IfModule>

<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript image/svg+xml
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  # Hashed-in-name assets never change; the pages themselves should not stick.
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType font/woff2             "access plus 1 year"
  ExpiresByType image/png              "access plus 1 year"
  ExpiresByType image/svg+xml          "access plus 1 year"
  ExpiresByType text/html              "access plus 0 seconds"
</IfModule>

<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  # This is an unfinished client review site — keep it out of search results.
  Header set X-Robots-Tag "noindex, nofollow"
</IfModule>

AddType font/woff2 .woff2
"""

ROBOTS = "User-agent: *\nDisallow: /\n"


def main():
    # Remove only what this script generates. Never rmtree OUT itself — it is
    # the repository root, and that would take source/, tools/ and .git with it.
    for name in GENERATED:
        target = OUT / name
        if target.is_dir():
            shutil.rmtree(target)
        elif target.exists():
            target.unlink()

    print("Building Riverbank Bingo temp site\n")
    for theme in THEMES:
        build_page(theme)

    favicon = OUT / "assets" / "img" / "favicon.svg"
    favicon.parent.mkdir(parents=True, exist_ok=True)
    favicon.write_text(FAVICON, encoding="utf-8")
    (OUT / ".htaccess").write_text(HTACCESS, encoding="utf-8")
    (OUT / "robots.txt").write_text(ROBOTS, encoding="utf-8")

    built = [OUT / n for n in GENERATED] + [OUT / "assets"]
    every = [f for p in built for f in ([p] if p.is_file() else p.rglob("*")) if f.is_file()]
    total = sum(f.stat().st_size for f in every)
    print(f"\n  {len(every)} files, {total / 1024 / 1024:.2f} MB total -> {OUT}")


if __name__ == "__main__":
    main()
