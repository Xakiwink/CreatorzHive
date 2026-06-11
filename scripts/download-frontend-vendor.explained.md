# download-frontend-vendor.sh — Explained

**File:** `scripts/download-frontend-vendor.sh`

---

## Purpose

One-time setup script. Downloads self-hosted frontend assets (Chart.js and web fonts) from jsDelivr CDN. Run once after cloning the repo or when upgrading asset versions.

---

## Usage

```bash
bash scripts/download-frontend-vendor.sh
```

Requires `curl` and internet access.

---

## Assets Downloaded

### Chart.js
- Version: 4.4.6
- File: `frontend/assets/chart.js/chart.umd.min.js`

### Inter Font (4 weights)
- 400, 500, 600, 700 — woff2 Latin subset
- Destination: `frontend/fonts/inter/`

### Playfair Display (3 weights + 1 italic)
- 500, 600, 700 normal + 500 italic — woff2 Latin
- Destination: `frontend/fonts/playfair-display/`

### JetBrains Mono (2 weights)
- 400, 500 — woff2 Latin
- Destination: `frontend/fonts/jetbrains-mono/`

---

## Notes

- Uses `set -euo pipefail` — exits immediately if any download fails
- Directories are created with `mkdir -p` before downloading
- All sources from `cdn.jsdelivr.net` (npm package mirror)
- Fonts are self-hosted so the app works without Google Fonts or external CDN at runtime

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/assets/chart.js/` | Chart.js destination |
| `frontend/fonts/` | Font destinations |
| `frontend/assets/README.md` | Documents the self-hosted asset approach |
