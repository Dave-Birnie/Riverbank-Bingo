#!/usr/bin/env python3
"""
One-time unpack of the original Claude Design exports in source/.

The exports are single-file "self-unpacking" bundles: a JSON manifest of base64
assets plus the page HTML stored as a JSON string. This script pulls them apart
into files you can actually edit:

    design/classic.dc.html      editable page template
    design/black-gold.dc.html   editable page template
    assets/js|fonts|img         shared, deduplicated assets

`tools/build.py` then turns the templates in design/ into the served pages.
Once design/ exists it is the source of truth — re-running this script
OVERWRITES those templates and throws away any edits made to them.

Usage:  python3 tools/extract.py [--force]
"""

import base64
import gzip
import hashlib
import json
import re
import sys
import zlib
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "source"
DESIGN = ROOT / "design"
ASSETS = ROOT / "assets"

BUNDLES = [
    ("homepage-classic.bundle.html", "classic.dc.html"),
    ("homepage-black-gold.bundle.html", "black-gold.dc.html"),
]

# Assets are matched by content hash so both designs share a single copy.
IMAGE_NAMES = {
    "bb6069f59e3edb1412f7f6a3ce3c71f0fb96b609": "logo-badge-gold.png",
    "60020dd81063e231abc4cc693968236286438a1e": "logo-badge-colour.png",
    "0a1dd9272d1399f889367bf2301d92a72dd0fa2d": "wordmark.png",
}
JS_NAMES = {
    "aa77ae4c49f525bc21de1d04f08a5d73962c7cce": "react.js",
    "feb8ddc9d0566a4fa0971a6e1138658618cdacfe": "react-dom.js",
    "2e38395c4a4ac9dd45b360554b9b99ba5a509250": "dc-runtime.js",
}


def read_bundle(path):
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
    """Name each font file from its @font-face rule and preceding subset comment."""
    names = {}
    pattern = re.compile(r"/\*\s*([a-z0-9-]+)\s*\*/\s*@font-face\s*\{(.*?)\}", re.S | re.I)
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


def main():
    force = "--force" in sys.argv
    existing = sorted(p.name for _, p in BUNDLES for p in [DESIGN / p] if p.exists())
    if existing and not force:
        sys.exit(
            "design/ already exists (%s).\n"
            "It is the source of truth for the site and may contain edits.\n"
            "Re-run with --force to overwrite it from source/." % ", ".join(existing)
        )

    DESIGN.mkdir(exist_ok=True)
    written = {}

    for bundle_name, design_name in BUNDLES:
        manifest, template = read_bundle(SOURCE / bundle_name)
        fonts = font_names(template)
        paths, runtime_id = {}, None

        for asset_id, entry in manifest.items():
            data = asset_bytes(entry)
            digest = hashlib.sha1(data).hexdigest()
            mime = entry["mime"]

            if mime in ("text/javascript", "application/javascript"):
                name = JS_NAMES.get(digest)
                if name is None:
                    sys.exit(f"{bundle_name}: unrecognised script {asset_id} ({digest})")
                if name == "dc-runtime.js":
                    runtime_id = asset_id
                out = ASSETS / "js" / name
            elif mime == "font/woff2":
                out = ASSETS / "fonts" / (fonts.get(asset_id) or f"font-{digest[:10]}.woff2")
            elif mime.startswith("image/"):
                out = ASSETS / "img" / (IMAGE_NAMES.get(digest) or f"image-{digest[:10]}.png")
            else:
                sys.exit(f"{bundle_name}: unexpected asset type {mime}")

            paths[asset_id] = out.relative_to(ROOT).as_posix()
            if str(out) in written:
                if written[str(out)] != digest:
                    sys.exit(f"asset name collision with different content: {out.name}")
            else:
                out.parent.mkdir(parents=True, exist_ok=True)
                out.write_bytes(data)
                written[str(out)] = digest

        if runtime_id is None:
            sys.exit(f"{bundle_name}: dc-runtime script not found")

        # Point asset references at real files.
        for asset_id, path in paths.items():
            template = template.replace(asset_id, path)

        # The bundler's loader tag becomes the marker build.py fills with the
        # real <head>; fonts are local now, so the preconnects go too.
        template = template.replace(
            f'<script src="{paths[runtime_id]}"></script>', "<!--BUILD:HEAD-->", 1
        )
        template = re.sub(
            r'\s*<link rel="preconnect" href="https://fonts\.(?:googleapis|gstatic)\.com"[^>]*>',
            "", template,
        )

        # Marker for the light-bulb switcher. It must go before the document's
        # real closing tag: the card's print handler writes a popup document, so
        # an earlier `</body>` sits inside a JavaScript string.
        head, tail = template.rsplit("</body>", 1)
        template = head + "<!--BUILD:TOGGLE-->\n</body>" + tail

        (DESIGN / design_name).write_text(template, encoding="utf-8")
        print(f"  design/{design_name:<22} {len(template)/1024:6.1f} KB")

    print(f"\n  {len(written)} assets -> assets/")
    print("  design/ is now the source of truth. Run: python3 tools/build.py")


if __name__ == "__main__":
    main()
