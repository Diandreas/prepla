#!/usr/bin/env bash
# Rend une vidéo de présentation PrePla (composition PreplaPromo par défaut) en MP4 prêt à publier :
# 1080×1920, H.264 + AAC, BT.709, loudness normalisée à -14 LUFS (réseaux sociaux).
#
# Les images sont rendues par tranches : si le rendu est interrompu, relancer le script
# reprend à la première tranche manquante. Usage (depuis remotion/) :
#   bash scripts/render-promo.sh            # CONCURRENCY=3 par défaut
#   CRF=18 bash scripts/render-promo.sh     # qualité plus élevée (fichier plus lourd)
#   COMP=PreplaPromoDE OUT=out/promo-de NAME=prepla-allemand-9x16.mp4 bash scripts/render-promo.sh
#                                           # version allemande (Goethe / TestDaF)
set -euo pipefail
cd "$(dirname "$0")/.."

COMP="${COMP:-PreplaPromo}"
TOTAL="${TOTAL:-1800}"  # nombre d'images de la composition
CHUNK=300
OUT="${OUT:-out/promo}"
FRAMES="$OUT/frames"
FINAL="$OUT/${NAME:-prepla-presentation-9x16.mp4}"
mkdir -p "$FRAMES"

for ((start = 0; start < TOTAL; start += CHUNK)); do
    end=$((start + CHUNK - 1))
    ((end >= TOTAL)) && end=$((TOTAL - 1))
    marker="$OUT/frames-$start-$end.done"
    [[ -f "$marker" ]] && continue
    echo "Images $start → $end"
    npx remotion render "$COMP" "$FRAMES" --sequence --image-format=jpeg --jpeg-quality=95 \
        --frames="$start-$end" --concurrency="${CONCURRENCY:-3}" --timeout=120000 --log=error
    touch "$marker"
done

# Remotion nomme les images d'après leur numéro (largeur variable) : on renumérote sur 4 chiffres.
for f in "$FRAMES"/element-*.jpeg; do
    [[ -e "$f" ]] || continue
    n="${f##*/element-}"
    n="${n%.jpeg}"
    mv "$f" "$FRAMES/frame-$(printf '%04d' "$((10#$n))").jpeg"
done
count=$(find "$FRAMES" -name 'frame-*.jpeg' | wc -l)
[[ "$count" -eq "$TOTAL" ]] || { echo "Il manque des images ($count/$TOTAL)." >&2; exit 1; }

if [[ ! -f "$OUT/audio.wav" ]]; then
    echo "Audio"
    npx remotion render "$COMP" "$OUT/audio.wav" --codec=wav --timeout=120000 --log=error
fi

# Gain pour atteindre -14 LUFS, puis limiteur (-1,2 dBFS par défaut, LIMIT=0.8 pour plus de marge).
measured=$(ffmpeg -hide_banner -i "$OUT/audio.wav" -af loudnorm=I=-14:TP=-1:print_format=json -f null - 2>&1 |
    sed -n 's/.*"input_i" : "\(-\{0,1\}[0-9.]*\)".*/\1/p')
gain=$(awk -v m="$measured" 'BEGIN { printf "%.2f", -14 - m }')
echo "Loudness mesurée : $measured LUFS → gain $gain dB"

ffmpeg -y -hide_banner -loglevel error \
    -framerate 30 -i "$FRAMES/frame-%04d.jpeg" -i "$OUT/audio.wav" \
    -filter_complex "[0:v]scale=out_color_matrix=bt709:out_range=tv,format=yuv420p[v];[1:a]volume=${gain}dB,alimiter=limit=${LIMIT:-0.87}:attack=3:release=60:level=false[a]" \
    -map "[v]" -map "[a]" \
    -c:v libx264 -preset slow -crf "${CRF:-21}" -profile:v high -level 4.2 \
    -colorspace bt709 -color_primaries bt709 -color_trc bt709 \
    -c:a aac -b:a 256k -ar 48000 -movflags +faststart -shortest "$FINAL"

echo "Vidéo : $FINAL"
ffmpeg -hide_banner -i "$FINAL" -af loudnorm=print_format=summary -f null - 2>&1 | grep -E 'Input Integrated|Input True Peak' || true
