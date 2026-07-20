"""
convert_to_webp.py
Converts all images (jpg, jpeg, png, gif, bmp, tiff) in a given directory
to WebP format. The original file is preserved; the WebP is saved alongside it
with the same name but a .webp extension.

Usage:
    py -3 convert_to_webp.py <directory> [--quality 85] [--recursive]

Examples:
    py -3 convert_to_webp.py c:\\wamp64\\www\\opzio-1\\opzio_erp\\resources\\images
    py -3 convert_to_webp.py c:\\wamp64\\www\\opzio-1\\opzio_web\\public\\images --quality 90
    py -3 convert_to_webp.py c:\\wamp64\\www\\opzio-1\\opzio_erp\\public --recursive
"""

import argparse
import os
import sys
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    sys.exit("Pillow is required. Install it with:  py -3 -m pip install Pillow")

SUPPORTED = {".jpg", ".jpeg", ".png", ".gif", ".bmp", ".tiff", ".tif"}


def convert_image(src: Path, quality: int) -> tuple[bool, str]:
    """Convert a single image to WebP. Returns (success, message)."""
    dest = src.with_suffix(".webp")
    if dest.exists():
        return False, f"[SKIP]  {src.name} → already exists as {dest.name}"
    try:
        with Image.open(src) as img:
            # Preserve transparency when available (RGBA / P with transparency)
            if img.mode in ("RGBA", "LA") or (img.mode == "P" and "transparency" in img.info):
                img = img.convert("RGBA")
                img.save(dest, "WEBP", quality=quality, lossless=False)
            else:
                img = img.convert("RGB")
                img.save(dest, "WEBP", quality=quality)
        size_orig = src.stat().st_size / 1024
        size_webp = dest.stat().st_size / 1024
        saving = (1 - size_webp / size_orig) * 100 if size_orig else 0
        return True, (
            f"[OK]    {src.name}  →  {dest.name}  "
            f"({size_orig:.1f} KB → {size_webp:.1f} KB, -{saving:.0f}%)"
        )
    except Exception as exc:
        return False, f"[ERR]   {src.name}: {exc}"


def run(directory: str, quality: int, recursive: bool) -> None:
    root = Path(directory).resolve()
    if not root.is_dir():
        sys.exit(f"Directory not found: {root}")

    pattern = "**/*" if recursive else "*"
    candidates = [
        p for p in root.glob(pattern)
        if p.is_file() and p.suffix.lower() in SUPPORTED
    ]

    if not candidates:
        print(f"No supported images found in {root}")
        return

    print(f"Directory : {root}")
    print(f"Quality   : {quality}")
    print(f"Recursive : {recursive}")
    print(f"Images    : {len(candidates)}")
    print("─" * 70)

    converted, skipped, errors = 0, 0, 0
    for path in sorted(candidates):
        ok, msg = convert_image(path, quality)
        print(msg)
        if "[OK]" in msg:
            converted += 1
        elif "[SKIP]" in msg:
            skipped += 1
        else:
            errors += 1

    print("─" * 70)
    print(f"Done — converted: {converted}, skipped: {skipped}, errors: {errors}")


def main() -> None:
    parser = argparse.ArgumentParser(description="Convert images to WebP (keeps originals).")
    parser.add_argument("directory", help="Path to the folder containing images")
    parser.add_argument(
        "--quality", type=int, default=85, metavar="0-100",
        help="WebP quality (0-100, default: 85)"
    )
    parser.add_argument(
        "--recursive", action="store_true",
        help="Also process images inside subdirectories"
    )
    args = parser.parse_args()

    if not 0 <= args.quality <= 100:
        sys.exit("--quality must be between 0 and 100")

    run(args.directory, args.quality, args.recursive)


if __name__ == "__main__":
    main()
