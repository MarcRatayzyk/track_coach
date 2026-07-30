"""Génère favicon + icônes PWA à partir de resources/brand/logo.png."""
from __future__ import annotations

import os
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SOURCE = os.path.join(ROOT, "resources", "brand", "logo.png")
ICONS_DIR = os.path.join(ROOT, "public", "icons")
PUBLIC_DIR = os.path.join(ROOT, "public")


def fit_on_canvas(logo: Image.Image, size: int, scale: float = 0.82, bg=(2, 6, 23, 255)) -> Image.Image:
    """Place le logo circulaire sur un fond sombre (icônes app / PWA)."""
    canvas = Image.new("RGBA", (size, size), bg)
    mark = logo.resize((max(1, int(size * scale)), max(1, int(size * scale))), Image.Resampling.LANCZOS)
    x = (size - mark.width) // 2
    y = (size - mark.height) // 2
    canvas.paste(mark, (x, y), mark)
    return canvas


def main() -> None:
    if not os.path.isfile(SOURCE):
        raise SystemExit(f"Missing source logo: {SOURCE}")

    logo = Image.open(SOURCE).convert("RGBA")
    os.makedirs(ICONS_DIR, exist_ok=True)

    sizes = {
        "icon-144.png": 144,
        "icon-192.png": 192,
        "icon-512.png": 512,
        "apple-touch-icon.png": 180,
    }

    for name, size in sizes.items():
        path = os.path.join(ICONS_DIR, name)
        fit_on_canvas(logo, size).convert("RGBA").save(path, "PNG", optimize=True)
        print("wrote", path)

    # Favicon PNG + copie transparente pour usage web
    favicon = fit_on_canvas(logo, 64, scale=0.9)
    favicon_path = os.path.join(PUBLIC_DIR, "favicon.png")
    favicon.save(favicon_path, "PNG", optimize=True)
    print("wrote", favicon_path)

    # Favicon SVG simple qui pointe vers le rendu PNG via foreignObject n'est pas fiable —
    # on remplace le SVG par une version minimaliste rouge/plate pour les navigateurs SVG.
    # Le lien principal dans blade utilisera favicon.png.

    print("PWA icons generated from brand logo")


if __name__ == "__main__":
    main()
