#!/usr/bin/env bash
# Generate the app's UI sound effects via the ElevenLabs Sound Effects API.
# Usage: ELEVENLABS_API_KEY=sk_... bash scripts/generate-sfx.sh
set -e
KEY="${ELEVENLABS_API_KEY:?Set ELEVENLABS_API_KEY}"
OUT="public/sounds"
mkdir -p "$OUT"

gen() {
  local name="$1" dur="$2" prompt="$3"
  echo "→ $name.mp3"
  curl -s -X POST "https://api.elevenlabs.io/v1/sound-generation" \
    -H "xi-api-key: $KEY" \
    -H "Content-Type: application/json" \
    -d "{\"text\":\"$prompt\",\"duration_seconds\":$dur,\"prompt_influence\":0.5}" \
    -o "$OUT/$name.mp3" -w "   HTTP %{http_code}, %{size_download} bytes\n"
}

gen correct   1.0 "short bright positive ding, correct answer chime, clean UI success, cheerful"
gen incorrect 0.8 "soft low buzzer, wrong answer, gentle error tone, not harsh, muted"
gen complete  2.0 "triumphant short victory fanfare, level complete, celebratory chime sparkle, rewarding"
gen xp        0.9 "light coin collect sparkle, points earned, positive reward blip, game xp"
gen click     0.4 "subtle soft UI tap, button click, minimal pop, clean interface"
gen pop       0.4 "light playful pop, bubble pop, soft UI accent, short"

echo "Done. Files in $OUT/"
