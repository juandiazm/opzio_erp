"""
rename_images.py
Renames Opzio branding images with descriptive, lowercase, Linux-safe filenames
based on the color palette and variant identified in each file.

Run this script from any location:
    py -3 rename_images.py
"""
import os

IMAGES_DIR = r"c:\wamp64\www\opzio-1\opzio_erp\resources\images"

RENAME_MAP = {
    # ── Logos con fondo (Square/Landscape + background) ──────────────────────
    "Opzio - Branding_Logo-Opzio-SC-01.jpg": "opzio-logo-compact-purple-bg.jpg",
    "Opzio - Branding_Logo-Opzio-SC-02.jpg": "opzio-logo-compact-cream-bg.jpg",
    "Opzio - Branding_Logo-Opzio-SL-01.jpg": "opzio-logo-wide-purple-bg.jpg",
    "Opzio - Branding_Logo-Opzio-SL-02.jpg": "opzio-logo-wide-cream-bg.jpg",

    # ── Logos sin fondo (Transparent Compact / Landscape) ────────────────────
    "Opzio - Branding_Logo-Opzio-TC-01.jpg": "opzio-logo-compact-purple.jpg",
    "Opzio - Branding_Logo-Opzio-TC-02.jpg": "opzio-logo-compact-cream.jpg",
    "Opzio - Branding_Logo-Opzio-TL-01.jpg": "opzio-logo-wide-purple.jpg",
    "Opzio - Branding_Logo-Opzio-TL-02.jpg": "opzio-logo-wide-cream.jpg",

    # ── Monogramas – Círculo ──────────────────────────────────────────────────
    "Opzio - Branding_Monograma-C-01.jpg": "opzio-monogram-circle-purple-bg.jpg",
    "Opzio - Branding_Monograma-C-02.jpg": "opzio-monogram-circle-violet-bg.jpg",
    "Opzio - Branding_Monograma-C-03.jpg": "opzio-monogram-circle-orange-bg.jpg",
    "Opzio - Branding_Monograma-C-04.jpg": "opzio-monogram-circle-cream-bg.jpg",
    "Opzio - Branding_Monograma-C-05.jpg": "opzio-monogram-circle-cream-violet-bg.jpg",

    # ── Monogramas – Cuadrado ─────────────────────────────────────────────────
    "Opzio - Branding_Monograma-S-01.jpg": "opzio-monogram-square-purple-bg.jpg",
    "Opzio - Branding_Monograma-S-02.jpg": "opzio-monogram-square-violet-bg.jpg",
    "Opzio - Branding_Monograma-S-03.jpg": "opzio-monogram-square-orange-bg.jpg",
    "Opzio - Branding_Monograma-S-04.jpg": "opzio-monogram-square-cream-bg.jpg",
    "Opzio - Branding_Monograma-S-05.jpg": "opzio-monogram-square-cream-violet-bg.jpg",

    # ── Monogramas – Transparente (sin fondo) ─────────────────────────────────
    "Opzio - Branding_Monograma-T-01.jpg": "opzio-monogram-cream.jpg",
    "Opzio - Branding_Monograma-T-02.jpg": "opzio-monogram-purple.jpg",
    "Opzio - Branding_Monograma-T-03.jpg": "opzio-monogram-purple-alt.jpg",
    "Opzio - Branding_Monograma-T-04.jpg": "opzio-monogram-purple-violet.jpg",
    "Opzio - Branding_Monograma-T-05.jpg": "opzio-monogram-violet.jpg",
}

def main():
    renamed, skipped, errors = 0, 0, 0
    print(f"Images directory: {IMAGES_DIR}\n")

    for old_name, new_name in RENAME_MAP.items():
        old_path = os.path.join(IMAGES_DIR, old_name)
        new_path = os.path.join(IMAGES_DIR, new_name)

        if not os.path.exists(old_path):
            print(f"  [SKIP]  {old_name!r} not found")
            skipped += 1
            continue
        if os.path.exists(new_path):
            print(f"  [EXISTS] {new_name!r} already exists — skipping")
            skipped += 1
            continue
        try:
            os.rename(old_path, new_path)
            print(f"  [OK]  {old_name}  →  {new_name}")
            renamed += 1
        except OSError as e:
            print(f"  [ERR] {old_name}: {e}")
            errors += 1

    print(f"\nDone — renamed: {renamed}, skipped: {skipped}, errors: {errors}")

if __name__ == "__main__":
    main()
