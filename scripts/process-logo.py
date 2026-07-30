from PIL import Image, ImageDraw
import os

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
src = os.path.join(root, "resources", "images", "logo.png")
# Prefer original Desktop copy if present (unprocessed)
desktop = os.path.join(os.path.expanduser("~"), "Desktop", "logo.png")
if os.path.isfile(desktop):
    src = desktop

img = Image.open(src).convert("RGBA")
w, h = img.size
print("source", src, w, h)

pixels = img.load()
for y in range(h):
    for x in range(w):
        r, g, b, a = pixels[x, y]
        if r < 28 and g < 28 and b < 28:
            pixels[x, y] = (r, g, b, 0)

bbox = img.getbbox()
print("bbox", bbox)
cropped = img.crop(bbox)
cw, ch = cropped.size
size = max(cw, ch)
square = Image.new("RGBA", (size, size), (0, 0, 0, 0))
square.paste(cropped, ((size - cw) // 2, (size - ch) // 2), cropped)

mask = Image.new("L", (size, size), 0)
draw = ImageDraw.Draw(mask)
inset = max(1, int(size * 0.008))
draw.ellipse([inset, inset, size - 1 - inset, size - 1 - inset], fill=255)
out = Image.new("RGBA", (size, size), (0, 0, 0, 0))
out.paste(square, (0, 0), mask)

hi = out.resize((1024, 1024), Image.Resampling.LANCZOS)

ui_path = os.path.join(root, "resources", "images", "logo.png")
hi.save(ui_path, "PNG", optimize=True)
print("ui", ui_path)

brand = os.path.join(root, "resources", "brand")
os.makedirs(brand, exist_ok=True)
brand_path = os.path.join(brand, "logo.png")
hi.save(brand_path, "PNG", optimize=True)
print("brand", brand_path)
