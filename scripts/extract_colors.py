"""
extract_colors.py
Extracts the dominant brand colors from Opzio branding images using PIL.
Outputs unique non-background hex values found in each image.
"""

import os
from PIL import Image
from collections import Counter

IMAGES_DIR = r"c:\wamp64\www\opzio-1\opzio_erp\resources\images"

# Pixel sampling step (every N pixels)
STEP = 4

def rgb_to_hex(r, g, b):
    return "#{:02X}{:02X}{:02X}".format(r, g, b)

def is_near_white(r, g, b, threshold=245):
    return r >= threshold and g >= threshold and b >= threshold

def quantize_channel(v, levels=16):
    """Reduce precision to group similar colors."""
    bucket = round(v / (256 / levels)) * (256 // levels)
    return min(bucket, 255)

def quantize_color(r, g, b):
    return (quantize_channel(r), quantize_channel(g), quantize_channel(b))

def extract_dominant_colors(path, top_n=8):
    img = Image.open(path).convert("RGB")
    w, h = img.size
    pixels = []
    for y in range(0, h, STEP):
        for x in range(0, w, STEP):
            r, g, b = img.getpixel((x, y))
            if not is_near_white(r, g, b):
                pixels.append(quantize_color(r, g, b))
    counter = Counter(pixels)
    top = counter.most_common(top_n)
    return [(rgb_to_hex(r, g, b), count) for (r, g, b), count in top]

def main():
    files = sorted(os.listdir(IMAGES_DIR))
    all_colors = Counter()
    print("=" * 60)
    print("OPZIO BRAND COLOR EXTRACTION")
    print("=" * 60)
    for fname in files:
        if not fname.lower().endswith((".jpg", ".jpeg", ".png")):
            continue
        fpath = os.path.join(IMAGES_DIR, fname)
        colors = extract_dominant_colors(fpath)
        print(f"\n{fname}")
        for hex_color, count in colors:
            print(f"  {hex_color}  ({count} samples)")
            all_colors[hex_color] += count

    print("\n" + "=" * 60)
    print("GLOBAL TOP COLORS (across all images, excl. near-white)")
    print("=" * 60)
    for hex_color, count in all_colors.most_common(15):
        print(f"  {hex_color}  (total samples: {count})")

if __name__ == "__main__":
    main()
