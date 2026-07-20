"""
extract_exact_colors.py
Samples specific pixels from key Opzio branding images to get exact hex values.
"""
from PIL import Image
import os

IMAGES_DIR = r"c:\wamp64\www\opzio-1\opzio_erp\resources\images"

def sample(fname, coords):
    img = Image.open(os.path.join(IMAGES_DIR, fname)).convert("RGB")
    print(f"\n{fname} ({img.size[0]}x{img.size[1]})")
    for label, (x, y) in coords.items():
        r, g, b = img.getpixel((x, y))
        print(f"  [{label}] at ({x},{y}): #{r:02X}{g:02X}{b:02X}  rgb({r},{g},{b})")

# SC-01: dark purple bg, cream logo, orange dots
sample("Opzio - Branding_Logo-Opzio-SC-01.jpg", {
    "bg_dark_purple":  (100, 100),
    "bg_dark_purple2": (540, 900),
    "logo_cream":      (500, 490),
    "logo_cream2":     (700, 490),
    "orange_dot_left": (200, 476),
    "orange_dot_right":(727, 404),
})

# SC-02: cream bg, dark purple logo, orange dots
sample("Opzio - Branding_Logo-Opzio-SC-02.jpg", {
    "bg_cream":         (100, 100),
    "bg_cream2":        (900, 900),
    "logo_dark_purple": (500, 490),
    "logo_dark_purple2":(800, 500),
    "orange_dot_left":  (200, 480),
    "orange_dot_right": (727, 408),
})

# TC-01: white bg, dark purple logo, orange dots
sample("Opzio - Branding_Logo-Opzio-TC-01.jpg", {
    "bg_white":        (100, 100),
    "logo_dark":       (350, 480),
    "orange_left":     (200, 455),
    "orange_right":    (726, 388),
})

# Monogram C-02: medium purple circle
sample("Opzio - Branding_Monograma-C-02.jpg", {
    "medium_purple":    (540, 200),
    "medium_purple2":   (200, 540),
    "dark_purple_logo": (540, 490),
})

# Monogram C-03: orange circle
sample("Opzio - Branding_Monograma-C-03.jpg", {
    "orange_bg":        (540, 200),
    "orange_bg2":       (200, 540),
    "dark_purple_logo": (540, 490),
    "cream_dot":        (365, 385),
})

# Monogram C-04: cream circle
sample("Opzio - Branding_Monograma-C-04.jpg", {
    "cream_bg":          (540, 200),
    "dark_purple_logo":  (540, 490),
    "orange_dot":        (370, 385),
})

# Monogram C-05: cream circle, medium purple logo
sample("Opzio - Branding_Monograma-C-05.jpg", {
    "cream_bg":          (540, 200),
    "medium_purple_logo":(540, 400),
    "orange_dot":        (370, 385),
})
