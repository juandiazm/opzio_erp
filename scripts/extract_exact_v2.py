"""
extract_exact_v2.py
Finds exact brand colors by scanning center regions of hi-res branding images.
"""
from PIL import Image
import os
from collections import Counter

IMAGES_DIR = r"c:\wamp64\www\opzio-1\opzio_erp\resources\images"

def find_colors_in_region(fname, bg_tolerance=20):
    path = os.path.join(IMAGES_DIR, fname)
    img = Image.open(path).convert("RGB")
    w, h = img.size
    cx, cy = w // 2, h // 2
    
    # Sample center 60% of image
    x0 = int(w * 0.2)
    x1 = int(w * 0.8)
    y0 = int(h * 0.2)
    y1 = int(h * 0.8)
    
    counter = Counter()
    step = max(1, w // 200)  # ~200 samples per row
    
    for y in range(y0, y1, step):
        for x in range(x0, x1, step):
            r, g, b = img.getpixel((x, y))
            counter[(r, g, b)] += 1
    
    return counter, (w, h)

def rgb_to_hex(r, g, b):
    return "#{:02X}{:02X}{:02X}".format(r, g, b)

# Key images to analyze
images = {
    "SC-01 (dark purple bg)":    "Opzio - Branding_Logo-Opzio-SC-01.jpg",
    "SC-02 (cream bg)":          "Opzio - Branding_Logo-Opzio-SC-02.jpg",
    "TC-01 (white bg)":          "Opzio - Branding_Logo-Opzio-TC-01.jpg",
    "Monogram C-01 (circle dk)": "Opzio - Branding_Monograma-C-01.jpg",
    "Monogram C-02 (med purple)":"Opzio - Branding_Monograma-C-02.jpg",
    "Monogram C-03 (orange)":    "Opzio - Branding_Monograma-C-03.jpg",
    "Monogram C-04 (cream circ)":"Opzio - Branding_Monograma-C-04.jpg",
    "Monogram C-05 (cream+vio)": "Opzio - Branding_Monograma-C-05.jpg",
}

all_global = Counter()
print("=" * 60)
for label, fname in images.items():
    counter, size = find_colors_in_region(fname)
    top = counter.most_common(6)
    print(f"\n{label} [{size[0]}x{size[1]}]")
    for (r, g, b), cnt in top:
        print(f"  {rgb_to_hex(r, g, b)}  rgb({r},{g},{b})  [{cnt}]")
    for key, val in counter.items():
        all_global[key] += val

print("\n" + "=" * 60)
print("TOP 12 GLOBAL COLORS")
print("=" * 60)
for (r, g, b), cnt in all_global.most_common(12):
    print(f"  {rgb_to_hex(r, g, b)}  rgb({r},{g},{b})  [{cnt}]")
