#!/usr/bin/env bash
# Fabrique les fichiers de la video du haut de page (public/videos/) a partir des
# deux montages livres : 16:9 (1280x720) et vertical 9:16 (720x1280).
#
# Usage : scripts/video/fabriquer-videos-accueil.sh <source-16x9.mp4> <source-9x16.mp4> [dossier-sortie]
#
# Tout sort en plage de couleurs « tv » (yuv420p limite) : les montages livres sont
# en plage pleine (yuvj420p) et certains decodeurs Android l'ignorent. Verifie le
# 2026-11-01 : les moyennes RGB restent a 1-2 niveaux de la source.
#
# Tailles obtenues le 2026-11-01 :
#   boucle-640 59 Ko · boucle-960 106 Ko · 720p 2,7 Mo · 360p 0,9 Mo
#   portrait-540 1,8 Mo · portrait-360 1,1 Mo
#   affiche-640.webp 11 Ko · affiche-1280.webp 26 Ko · affiche-portrait.webp 21 Ko
#   etape-1/2/3.webp 4-7 Ko
set -euo pipefail

P="$1"   # 16:9
V="$2"   # 9:16
OUT="${3:-public/videos}"
mkdir -p "$OUT"
N="par-ou-commencer"
X264=(-c:v libx264 -profile:v main -preset veryslow -g 125 -color_range tv -colorspace bt709 -color_primaries bt709 -color_trc bt709 -movflags +faststart)

# 1. Apercu muet en boucle : la question d'ouverture (0,8 s -> 4,8 s), sans piste
#    son du tout. Les 0,4 s finales fondent vers 0,4-0,8 s, si bien que la derniere
#    image rejoint la premiere : la boucle n'a pas de saut.
for W in 640 960; do
  H=$((W * 9 / 16))
  ffmpeg -v error -y -i "$P" -filter_complex \
    "[0:v]trim=start=0.8:end=4.8,setpts=PTS-STARTPTS,scale=${W}:${H}:flags=lanczos:out_range=tv,format=yuv420p[a];\
     [0:v]trim=start=0.4:end=0.8,setpts=PTS-STARTPTS,scale=${W}:${H}:flags=lanczos:out_range=tv,format=yuv420p[b];\
     [a][b]xfade=transition=fade:duration=0.4:offset=3.6,format=yuv420p[v]" \
    -map "[v]" -an "${X264[@]}" -crf 30 "$OUT/$N-boucle-$W.mp4"
done

# 2. Video complete, avec le son.
ffmpeg -v error -y -i "$P" -vf "scale=1280:720:out_range=tv,format=yuv420p" "${X264[@]}" -profile:v high -crf 26 -c:a aac -b:a 96k "$OUT/$N-720p.mp4"
ffmpeg -v error -y -i "$P" -vf "scale=640:360:flags=lanczos:out_range=tv,format=yuv420p" "${X264[@]}" -crf 29 -c:a aac -b:a 48k -ac 1 "$OUT/$N-360p.mp4"
ffmpeg -v error -y -i "$V" -vf "scale=540:960:flags=lanczos:out_range=tv,format=yuv420p" "${X264[@]}" -crf 29 -c:a aac -b:a 64k -ac 1 "$OUT/$N-portrait-540.mp4"
ffmpeg -v error -y -i "$V" -vf "scale=360:640:flags=lanczos:out_range=tv,format=yuv420p" "${X264[@]}" -crf 29 -c:a aac -b:a 48k -ac 1 "$OUT/$N-portrait-360.mp4"

# 3. Affiches : la PREMIERE image de la boucle (0,8 s), pour que l'apercu prenne le
#    relais sans saut. WebP, avec un JPEG de secours pour le haut de page.
ffmpeg -v error -y -ss 0.8 -i "$OUT/$N-720p.mp4" -frames:v 1 -vf "scale=640:360:flags=lanczos" -c:v libwebp -quality 72 "$OUT/$N-affiche-640.webp"
ffmpeg -v error -y -ss 0.8 -i "$OUT/$N-720p.mp4" -frames:v 1 -vf "scale=1280:720:flags=lanczos" -c:v libwebp -quality 72 "$OUT/$N-affiche-1280.webp"
ffmpeg -v error -y -ss 0.8 -i "$OUT/$N-720p.mp4" -frames:v 1 -vf "scale=640:360:flags=lanczos" -q:v 4 "$OUT/$N-affiche-640.jpg"
ffmpeg -v error -y -ss 0.8 -i "$OUT/$N-720p.mp4" -frames:v 1 -vf "scale=1280:720:flags=lanczos" -q:v 4 "$OUT/$N-affiche-1280.jpg"
ffmpeg -v error -y -ss 0.8 -i "$OUT/$N-portrait-540.mp4" -frames:v 1 -c:v libwebp -quality 70 "$OUT/$N-affiche-portrait.webp"

# 4. Vignettes des trois etapes (section « Comment ca marche ») : 0:06 choisir
#    l'examen, 0:10 « A travailler », 0:13 exercices sur mesure.
for T in 6.6:1 10.4:2 13.6:3; do
  ffmpeg -v error -y -ss "${T%%:*}" -i "$OUT/$N-720p.mp4" -frames:v 1 -vf "scale=400:225:flags=lanczos" -c:v libwebp -quality 70 "$OUT/$N-etape-${T##*:}.webp"
done

ls -la "$OUT"
