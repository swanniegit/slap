#!/usr/bin/env python3
"""Render assets/brand/apple-touch-icon.png FROM assets/brand/mark.svg.

    pip install pillow && python scripts/brand-icon.py            # rewrite it
    pip install pillow && python scripts/brand-icon.py --check    # CI: is it stale?

iOS and the web manifest want a raster, and nothing in this project can make one
from an SVG: PHP's GD has no SVG reader and the container has no ImageMagick.

The first version of this script solved that by drawing the bear a second time,
in Pillow, from the same numbers. That was the bug it existed to prevent. The
numbers were a COPY, so editing mark.svg changed the favicon and left the
home-screen icon alone -- and a --check that re-ran this script compared the PNG
against the copy rather than against the mark. It passed a deliberately mangled
mark.svg without noticing. A guard that cannot fail is worse than no guard,
because it is believed.

So this reads the SVG. There is one set of geometry in this repo, it lives in
mark.svg, and nothing here can drift from it because nothing here restates it.

The parser is deliberately small -- it understands exactly the shapes mark.svg
uses -- and it raises on anything it does not recognise rather than skipping it.
Silently ignoring an unknown element is how you get an icon missing an eye and a
check that is perfectly happy about it.

Not shipped: scripts/ is in .dockerignore.
"""
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw

ROOT = Path(__file__).resolve().parent.parent
SVG = ROOT / "assets/brand/mark.svg"
OUT = ROOT / "assets/brand/apple-touch-icon.png"

SIZE = 180
SS = 4          # supersample: Pillow has no antialiasing of its own, so a 180px
                # circle drawn directly comes out with a staircase edge
TOLERANCE = 4   # max per-channel difference --check forgives; see the note below
FLATTEN = 512   # line segments per quadratic curve. Higher than smoothness alone
                # needs: the seam is dashed, and where each dash starts is walked
                # by arc length along these segments, so a coarse curve moves the
                # stitches. Measured -- going 64 -> 512 more than halves the
                # disagreement with a true renderer, and costs milliseconds.


def parse_svg(path):
    """(viewbox_size, [(tag, attrs), ...]) for the subset mark.svg uses."""
    root = ET.parse(path).getroot()
    vb = [float(n) for n in root.get("viewBox").split()]
    if vb[0] or vb[1] or vb[2] != vb[3]:
        raise SystemExit(f"ERROR: {path} viewBox must be square and start at 0,0: {vb}")

    shapes = []
    for el in root:
        tag = el.tag.split("}")[-1]
        if tag == "title":
            continue
        if tag not in ("rect", "circle", "ellipse", "path"):
            raise SystemExit(
                f"ERROR: mark.svg contains a <{tag}>, which this renderer does "
                f"not understand. Teach it, or the icon silently loses that shape."
            )
        shapes.append((tag, {k: el.get(k) for k in el.keys()}))
    return vb[2], shapes


def num(v, default=0.0):
    return default if v is None else float(v)


def rgb(v):
    v = v.lstrip("#")
    return tuple(int(v[i:i + 2], 16) for i in (0, 2, 4))


def parse_path(d):
    """[[point, ...], ...] -- one list per subpath.

    Understands M (absolute move), q (relative quadratic) and v (relative
    vertical line): every command mark.svg contains. Anything else raises.
    """
    tokens = re.findall(r"[A-Za-z]|-?\d*\.?\d+", d)
    subpaths, cur, x, y, i = [], [], 0.0, 0.0, 0

    def flush():
        if len(cur) > 1:
            subpaths.append(list(cur))

    while i < len(tokens):
        cmd = tokens[i]
        i += 1
        if cmd == "M":
            flush()
            x, y = float(tokens[i]), float(tokens[i + 1])
            i += 2
            cur = [(x, y)]
        elif cmd == "v":
            y += float(tokens[i])
            i += 1
            cur.append((x, y))
        elif cmd == "q":
            cx, cy = x + float(tokens[i]), y + float(tokens[i + 1])
            nx, ny = x + float(tokens[i + 2]), y + float(tokens[i + 3])
            i += 4
            for step in range(1, FLATTEN + 1):
                t = step / FLATTEN
                u = 1 - t
                cur.append((u * u * x + 2 * u * t * cx + t * t * nx,
                            u * u * y + 2 * u * t * cy + t * t * ny))
            x, y = nx, ny
        else:
            raise SystemExit(
                f"ERROR: path command '{cmd}' in mark.svg is not supported by "
                f"scripts/brand-icon.py. Add it there rather than leaving the "
                f"icon wrong."
            )
    flush()
    return subpaths


def render(viewbox, shapes):
    k = SIZE * SS / viewbox
    img = Image.new("RGB", (SIZE * SS, SIZE * SS), (255, 255, 255))
    draw = ImageDraw.Draw(img)

    def s(v):
        return v * k

    def box(cx, cy, rx, ry):
        return [s(cx - rx), s(cy - ry), s(cx + rx), s(cy + ry)]

    def stroke(points, colour, width):
        """A path with stroke-linecap=round, which Pillow's line() cannot do."""
        if len(points) > 1:
            draw.line([(s(px), s(py)) for px, py in points],
                      fill=colour, width=round(s(width)), joint="curve")
        for px, py in (points[0], points[-1]):
            draw.ellipse(box(px, py, width / 2, width / 2), fill=colour)

    for tag, a in shapes:
        if tag == "rect":
            # rx is honoured in the SVG and deliberately dropped here. Apple
            # masks the touch icon to its own shape, so baking our corners in as
            # well gives a double-rounded edge; and transparent corners let
            # older iOS composite the whole icon onto black. Full bleed, opaque.
            draw.rectangle([0, 0, SIZE * SS, SIZE * SS], fill=rgb(a["fill"]))
        elif tag == "circle":
            r = num(a.get("r"))
            draw.ellipse(box(num(a.get("cx")), num(a.get("cy")), r, r), fill=rgb(a["fill"]))
        elif tag == "ellipse":
            draw.ellipse(box(num(a.get("cx")), num(a.get("cy")),
                             num(a.get("rx")), num(a.get("ry"))), fill=rgb(a["fill"]))
        elif tag == "path":
            colour = rgb(a["stroke"])
            width = num(a.get("stroke-width"), 1.0)
            dash = a.get("stroke-dasharray")
            for pts in parse_path(a["d"]):
                if not dash:
                    stroke(pts, colour, width)
                    continue
                on_len, off_len = (float(n) for n in re.split(r"[ ,]+", dash.strip()))
                # Walked by arc length, so each dash comes out the length the SVG
                # asks for rather than the length an even step in t would give.
                run, drawing, seg = 0.0, True, [pts[0]]
                for p, q in zip(pts, pts[1:]):
                    run += ((q[0] - p[0]) ** 2 + (q[1] - p[1]) ** 2) ** 0.5
                    seg.append(q)
                    if run >= (on_len if drawing else off_len):
                        if drawing:
                            stroke(seg, colour, width)
                        run, drawing, seg = 0.0, not drawing, [q]
                if drawing and len(seg) > 1:
                    stroke(seg, colour, width)

    return img.resize((SIZE, SIZE), Image.LANCZOS)


viewbox, shapes = parse_svg(SVG)
rendered = render(viewbox, shapes)

if "--check" not in sys.argv:
    rendered.save(OUT, optimize=True)
    print(f"wrote assets/brand/apple-touch-icon.png from mark.svg ({len(shapes)} shapes)")
    raise SystemExit(0)

try:
    committed = Image.open(OUT).convert("RGB")
except OSError as exc:
    raise SystemExit(f"ERROR: cannot read {OUT}: {exc}")

if committed.size != rendered.size:
    raise SystemExit(f"ERROR: {OUT} is {committed.size}, mark.svg renders {rendered.size}")

# Compared as PIXELS, not file bytes. PNG encoding depends on the zlib build, so
# a byte comparison would fail on a machine whose only sin was a different Pillow
# wheel. The tolerance covers resampling differences between Pillow versions; a
# bear that has actually been redrawn differs by very much more.
worst = max(ImageChops.difference(committed, rendered).getextrema(), key=lambda mm: mm[1])[1]

if worst > TOLERANCE:
    raise SystemExit(
        f"ERROR: assets/brand/apple-touch-icon.png no longer matches mark.svg\n"
        f"       (worst channel difference {worst}, tolerance {TOLERANCE})\n"
        f"       The mark was redrawn without regenerating the icon. Run:\n"
        f"         python scripts/brand-icon.py"
    )

print(f"apple-touch-icon.png matches mark.svg "
      f"(worst channel difference {worst}/{TOLERANCE}, {len(shapes)} shapes)")
