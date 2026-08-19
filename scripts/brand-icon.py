#!/usr/bin/env python3
"""Redraw assets/brand/apple-touch-icon.png from the geometry in mark.svg.

iOS and the web manifest both want a raster, and nothing in this project can
rasterise an SVG: PHP's GD has no SVG reader, and the container has no
ImageMagick. So the mark is drawn a second time here, in Pillow, on the same
140-unit grid and with the same numbers as assets/brand/mark.svg. Changing one
without the other makes the tab icon and the home-screen icon different bears.

    pip install pillow && python scripts/brand-icon.py

Not shipped: scripts/ is in .dockerignore. This is a tool for regenerating a
committed asset, not part of the running site.
"""
from PIL import Image, ImageDraw

OUT  = "assets/brand/apple-touch-icon.png"
SIZE = 180
SS   = 4                        # supersample: Pillow has no antialiasing of its
K    = SIZE * SS / 140.0        # own, and a 180px circle comes out as a staircase

GOLD  = (255, 201,  60)         # --slap-yellow
CORAL = (255, 107, 107)         # --slap-coral
INK   = ( 43,  36,  64)         # --slap-ink
CREAM = (255, 248, 238)         # --slap-cream


def s(v):
    return v * K


def box(cx, cy, rx, ry=None):
    ry = rx if ry is None else ry
    return [s(cx - rx), s(cy - ry), s(cx + rx), s(cy + ry)]


def quad(p0, p1, p2, n=400):
    """Points along a quadratic bezier — the q segments of the SVG paths."""
    out = []
    for i in range(n + 1):
        t = i / n
        u = 1 - t
        out.append((u * u * p0[0] + 2 * u * t * p1[0] + t * t * p2[0],
                    u * u * p0[1] + 2 * u * t * p1[1] + t * t * p2[1]))
    return out


def stroke(points, colour, width):
    """A path with stroke-linecap="round", which Pillow's line() does not do."""
    if len(points) > 1:
        draw.line([(s(x), s(y)) for x, y in points],
                  fill=colour, width=round(s(width)), joint="curve")
    for x, y in (points[0], points[-1]):
        draw.ellipse(box(x, y, width / 2), fill=colour)


def dashed(points, colour, width, on_len, off_len):
    """stroke-dasharray, walked by arc length so the dashes come out the length
       the SVG asks for and not the length an even step in t would give."""
    run, drawing, seg = 0.0, True, [points[0]]
    for a, b in zip(points, points[1:]):
        run += ((b[0] - a[0]) ** 2 + (b[1] - a[1]) ** 2) ** 0.5
        seg.append(b)
        if run >= (on_len if drawing else off_len):
            if drawing:
                stroke(seg, colour, width)
            run, drawing, seg = 0.0, not drawing, [b]
    if drawing and len(seg) > 1:
        stroke(seg, colour, width)


# Opaque and square. Apple masks the icon to its own shape, so baking the SVG's
# rx="38" corners in as well gives a double-rounded edge, and leaving the
# corners transparent — as the previous icon did — lets older iOS composite the
# whole thing onto black.
img  = Image.new("RGB", (SIZE * SS, SIZE * SS), GOLD)
draw = ImageDraw.Draw(img)

draw.ellipse(box(44, 42, 19), fill=CORAL)                  # ears, before the head
draw.ellipse(box(96, 42, 19), fill=CORAL)
draw.ellipse(box(70, 78, 46), fill=INK)                    # head

dashed(quad((32, 58), (70, 36), (108, 58)), GOLD, 5.5, 12, 11)   # the seam

draw.ellipse(box(52, 68, 7), fill=CREAM)                   # eyes
draw.ellipse(box(88, 68, 7), fill=CREAM)
draw.ellipse(box(70, 97, 23, 17), fill=CREAM)              # muzzle
draw.ellipse(box(70, 90, 8.5, 6.5), fill=INK)              # nose

stroke([(70, 96), (70, 101)], INK, 4)                      # mouth
stroke(quad((70, 101), (63.5, 106), (59, 101.5)), INK, 4)
stroke(quad((70, 101), (76.5, 106), (81, 101.5)), INK, 4)

img.resize((SIZE, SIZE), Image.LANCZOS).save(OUT, optimize=True)
print("wrote", OUT)
