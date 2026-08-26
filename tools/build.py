#!/usr/bin/env python3
"""
Build the Riverbank Bingo temp site from the two Claude Design bundles.

The files in source/ are single-file "self-unpacking" exports: a JSON manifest
of base64 assets plus the real page HTML stored as a JSON string. Serving those
directly works but forces every visitor to download ~1.1 MB and rebuild every
asset in JavaScript before anything renders.

This script unpacks them into an ordinary static site instead:

    public/
      index.html          Classic (light) homepage
      black-gold.html     Black & Gold homepage
      assets/js|fonts|img Shared, deduplicated, cacheable assets

Both pages get a light-bulb button that switches between the two designs,
keeping your scroll position so it reads as a theme toggle.

Usage:  python3 tools/build.py
"""

import base64
import gzip
import hashlib
import json
import re
import shutil
import sys
import zlib
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "source"
PUBLIC = ROOT / "public"

# --------------------------------------------------------------------------
# The two designs
# --------------------------------------------------------------------------

THEMES = [
    {
        "key": "classic",
        "bundle": "homepage-classic.bundle.html",
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
        "bundle": "homepage-black-gold.bundle.html",
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

# Images are matched by content hash so both bundles share one copy.
IMAGE_NAMES = {
    "bb6069f59e3edb1412f7f6a3ce3c71f0fb96b609": "logo-badge-gold.png",
    "60020dd81063e231abc4cc693968236286438a1e": "logo-badge-colour.png",
    "0a1dd9272d1399f889367bf2301d92a72dd0fa2d": "wordmark.png",
}

# The three scripts the page needs, identified by content hash.
JS_NAMES = {
    "aa77ae4c49f525bc21de1d04f08a5d73962c7cce": "react.js",
    "feb8ddc9d0566a4fa0971a6e1138658618cdacfe": "react-dom.js",
    "2e38395c4a4ac9dd45b360554b9b99ba5a509250": "dc-runtime.js",
}

EXT = {
    "image/png": "png",
    "image/jpeg": "jpg",
    "image/webp": "webp",
    "image/svg+xml": "svg",
    "font/woff2": "woff2",
    "text/javascript": "js",
    "application/javascript": "js",
    "text/css": "css",
}


# --------------------------------------------------------------------------
# Unpacking
# --------------------------------------------------------------------------

def read_bundle(path):
    """Pull the asset manifest and the page template out of a bundle file."""
    html = path.read_text(encoding="utf-8")

    def grab(kind):
        m = re.search(r'<script type="__bundler/%s">(.*?)</script>' % kind, html, re.S)
        if not m:
            sys.exit(f"{path.name}: no __bundler/{kind} block found")
        return json.loads(m.group(1).strip())

    return grab("manifest"), grab("template")


def asset_bytes(entry):
    data = base64.b64decode(entry["data"])
    if entry.get("compressed"):
        try:
            data = gzip.decompress(data)
        except Exception:
            data = zlib.decompress(data)
    return data


def slug(text):
    return re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")


def font_names(template):
    """Map each font asset id to a readable filename from its @font-face rule.

    Each rule is preceded by a `/* subset */` comment, so we can build names
    like `source-sans-3-400-normal-latin.woff2` instead of leaving raw UUIDs.
    """
    names = {}
    pattern = re.compile(
        r"/\*\s*([a-z0-9-]+)\s*\*/\s*@font-face\s*\{(.*?)\}", re.S | re.I
    )
    for subset, block in pattern.findall(template):
        def field(name, default=""):
            m = re.search(r"%s:\s*([^;]+);" % name, block)
            return m.group(1).strip().strip("'\"") if m else default

        src = re.search(r'url\("([^"]+)"\)', block)
        if not src:
            continue
        names[src.group(1)] = "%s-%s-%s-%s.woff2" % (
            slug(field("font-family", "font")),
            field("font-weight", "400"),
            field("font-style", "normal"),
            slug(subset),
        )
    return names


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
<meta name="description" content="Riverbank Bingo — free play-at-home bingo. Live caller every session, cards from verified local distributors. 18+ approved adult play.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="{theme_color}">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/img/logo-badge-gold.png">
<script src="assets/js/react.js"></script>
<script src="assets/js/react-dom.js"></script>
<script src="assets/js/dc-runtime.js"></script>"""

THEME_COLOR = {"classic": "#FBF6EC", "black-gold": "#0A0A0C"}


def build_page(theme, shared):
    bundle = SOURCE / theme["bundle"]
    manifest, template = read_bundle(bundle)

    fonts = font_names(template)
    replacements = {}
    runtime_id = None

    for asset_id, entry in manifest.items():
        data = asset_bytes(entry)
        digest = hashlib.sha1(data).hexdigest()
        mime = entry["mime"]

        if mime in ("text/javascript", "application/javascript"):
            name = JS_NAMES.get(digest)
            if name is None:
                sys.exit(f"{bundle.name}: unrecognised script asset {asset_id} ({digest})")
            if name == "dc-runtime.js":
                runtime_id = asset_id
            out = PUBLIC / "assets" / "js" / name
            # The runtime is injected by hand below; the rest resolve by path.
            replacements[asset_id] = f"assets/js/{name}"

        elif mime == "font/woff2":
            name = fonts.get(asset_id) or f"font-{digest[:10]}.woff2"
            out = PUBLIC / "assets" / "fonts" / name
            replacements[asset_id] = f"assets/fonts/{name}"

        elif mime.startswith("image/"):
            name = IMAGE_NAMES.get(digest) or f"image-{digest[:10]}.{EXT.get(mime, 'bin')}"
            out = PUBLIC / "assets" / "img" / name
            replacements[asset_id] = f"assets/img/{name}"

        else:
            sys.exit(f"{bundle.name}: unexpected asset type {mime}")

        # Assets shared between the two designs are written once.
        key = str(out)
        if key in shared:
            if shared[key] != digest:
                sys.exit(f"asset name collision with different content: {out.name}")
        else:
            out.parent.mkdir(parents=True, exist_ok=True)
            out.write_bytes(data)
            shared[key] = digest

    if runtime_id is None:
        sys.exit(f"{bundle.name}: dc-runtime script not found")

    # 1. Swap the bundler's runtime <script> for real, ordered script tags.
    #    React must be on `window` before dc-runtime boots, otherwise the
    #    runtime falls back to fetching React from a CDN.
    head = HEAD_EXTRA.format(
        title=theme["title"],
        label=theme["label"],
        theme_color=THEME_COLOR[theme["key"]],
    )
    runtime_tag = f'<script src="{runtime_id}"></script>'
    if runtime_tag not in template:
        sys.exit(f"{bundle.name}: could not locate the runtime script tag")
    page = template.replace(runtime_tag, head, 1)

    # 2. Point every remaining asset reference at its file on disk.
    for asset_id, path in replacements.items():
        page = page.replace(asset_id, path)

    # 3. Drop the Google Fonts preconnects — every font is served locally now.
    page = re.sub(
        r'\s*<link rel="preconnect" href="https://fonts\.(googleapis|gstatic)\.com"[^>]*>',
        "",
        page,
    )

    # 4. Add the light-bulb switcher, immediately before the document's real
    #    closing tag. It has to be the *last* `</body>`: the bingo card's
    #    print handler writes a popup document, so an earlier `</body>` sits
    #    inside a JavaScript string and splicing there breaks the logic script.
    if "</body>" in page:
        head_part, tail_part = page.rsplit("</body>", 1)
        page = head_part + toggle_markup(theme) + "\n</body>" + tail_part
    else:
        page += toggle_markup(theme)

    out_page = PUBLIC / theme["page"]
    out_page.write_text(page, encoding="utf-8")

    leftover = re.findall(r"[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}", page)
    if leftover:
        sys.exit(f"{out_page.name}: {len(leftover)} unresolved asset id(s), e.g. {leftover[0]}")

    print(f"  {theme['page']:<16} {out_page.stat().st_size / 1024:7.1f} KB  ({len(manifest)} assets)")


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
    if PUBLIC.exists():
        shutil.rmtree(PUBLIC)
    PUBLIC.mkdir(parents=True)

    print("Building Riverbank Bingo temp site\n")
    shared = {}
    for theme in THEMES:
        build_page(theme, shared)

    (PUBLIC / "assets" / "img" / "favicon.svg").write_text(FAVICON, encoding="utf-8")
    (PUBLIC / ".htaccess").write_text(HTACCESS, encoding="utf-8")
    (PUBLIC / "robots.txt").write_text(ROBOTS, encoding="utf-8")

    total = sum(f.stat().st_size for f in PUBLIC.rglob("*") if f.is_file())
    files = sum(1 for f in PUBLIC.rglob("*") if f.is_file())
    print(f"\n  {files} files, {total / 1024 / 1024:.2f} MB total -> {PUBLIC}")


if __name__ == "__main__":
    main()
