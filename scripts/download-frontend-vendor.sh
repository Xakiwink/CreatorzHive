#!/usr/bin/env bash
# Download self-hosted frontend assets (fonts + Chart.js). Run once after clone or when upgrading versions.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

mkdir -p frontend/assets/chart.js
mkdir -p frontend/fonts/inter frontend/fonts/playfair-display frontend/fonts/jetbrains-mono

echo "Chart.js 4.4.6..."
curl -fsSL "https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" \
  -o frontend/assets/chart.js/chart.umd.min.js

echo "Inter..."
for w in 400 500 600 700; do
  curl -fsSL "https://cdn.jsdelivr.net/npm/@fontsource/inter@5.0.16/files/inter-latin-${w}-normal.woff2" \
    -o "frontend/fonts/inter/inter-latin-${w}-normal.woff2"
done

echo "Playfair Display..."
for w in 500 600 700; do
  curl -fsSL "https://cdn.jsdelivr.net/npm/@fontsource/playfair-display@5.0.18/files/playfair-display-latin-${w}-normal.woff2" \
    -o "frontend/fonts/playfair-display/playfair-display-latin-${w}-normal.woff2"
done
curl -fsSL "https://cdn.jsdelivr.net/npm/@fontsource/playfair-display@5.0.18/files/playfair-display-latin-500-italic.woff2" \
  -o frontend/fonts/playfair-display/playfair-display-latin-500-italic.woff2

echo "JetBrains Mono..."
for w in 400 500; do
  curl -fsSL "https://cdn.jsdelivr.net/npm/@fontsource/jetbrains-mono@5.0.18/files/jetbrains-mono-latin-${w}-normal.woff2" \
    -o "frontend/fonts/jetbrains-mono/jetbrains-mono-latin-${w}-normal.woff2"
done

echo "Done. Assets are under frontend/assets/ and frontend/fonts/"
