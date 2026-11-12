# PrePla Kids : transforme les PNG generes (1 a 2 Mo chacun) en WebP de 512 px
# (environ 40 Ko) que l'application sert aux telephones.
#
#   python scripts/optimiser-images-kids.py
#
# Lit public/illustrations/prepla-kids/**/*.png et ecrit le .webp a cote.
# Les PNG restent les sources : ils ne vont pas dans git (voir .gitignore).
# Une image deja convertie et plus recente que son PNG n'est pas refaite.
from pathlib import Path

from PIL import Image

RACINE = Path(__file__).resolve().parent.parent / 'public' / 'illustrations' / 'prepla-kids'
TAILLE = 512

faits = gardes = 0
for png in sorted(RACINE.rglob('*.png')):
    webp = png.with_suffix('.webp')
    if webp.exists() and webp.stat().st_mtime >= png.stat().st_mtime:
        gardes += 1
        continue
    im = Image.open(png).convert('RGBA')
    # Rogne le vide autour du sujet, puis le recentre sur un carre avec une marge :
    # les images generees n'ont pas toutes la meme marge.
    boite = im.getchannel('A').getbbox()
    if boite:
        im = im.crop(boite)
    cote = int(max(im.size) * 1.12)
    carre = Image.new('RGBA', (cote, cote), (0, 0, 0, 0))
    carre.paste(im, ((cote - im.width) // 2, (cote - im.height) // 2))
    carre.resize((TAILLE, TAILLE), Image.LANCZOS).save(webp, 'WEBP', quality=82, method=6)
    faits += 1
    print(f'{png.relative_to(RACINE)} -> {webp.name} ({webp.stat().st_size // 1024} Ko)')

print(f'{faits} image(s) convertie(s), {gardes} deja a jour.')
