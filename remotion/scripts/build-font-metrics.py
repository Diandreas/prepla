#!/usr/bin/env python3
"""Extrait les chasses (largeurs d'avance) des polices de la vidéo vers src/promo/fontMetrics.ts.

Remotion peut ainsi calculer la largeur d'un mot de façon synchrone et déterministe, sans
mesurer dans le navigateur (une mesure asynchrone peut bloquer le rendu d'une image).
Usage (depuis remotion/) : python3 scripts/build-font-metrics.py   — nécessite fonttools et brotli.
"""
import json
from pathlib import Path

from fontTools.ttLib import TTFont

ROOT = Path(__file__).resolve().parent.parent
FONTS = {
    'jakarta800': 'plus-jakarta-sans-latin-800-normal.woff2',
    'cormorant700i': 'cormorant-garamond-latin-700-italic.woff2',
}
CHARS = [chr(c) for c in range(0x20, 0x7F)] + list('àâäçéèêëîïôöùûüÿœÀÂÄÇÉÈÊËÎÏÔÖÙÛÜŸŒß’«»…·  –—')


def metrics(path: Path) -> dict:
    font = TTFont(path)
    upem = font['head'].unitsPerEm
    cmap = font.getBestCmap()
    hmtx = font['hmtx']
    widths = {}
    for ch in CHARS:
        glyph = cmap.get(ord(ch))
        if glyph is not None:
            widths[ch] = round(hmtx[glyph][0] / upem, 4)
    return widths


def main():
    data = {key: metrics(ROOT / 'public' / 'promo' / 'fonts' / name) for key, name in FONTS.items()}
    out = ROOT / 'src' / 'promo' / 'fontMetrics.ts'
    body = json.dumps(data, ensure_ascii=False, indent=4, sort_keys=True)
    out.write_text(
        '// Généré par scripts/build-font-metrics.py — chasses en em des polices de la vidéo.\n'
        f'export const ADVANCES: Record<string, Record<string, number>> = {body};\n',
        encoding='utf-8',
    )
    print(f'{out} ({", ".join(f"{k}: {len(v)} glyphes" for k, v in data.items())})')


if __name__ == '__main__':
    main()
