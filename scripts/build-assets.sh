#!/usr/bin/env bash
# ==============================================================================
# build-assets.sh — compilación idempotente de assets (Vite + Tailwind)
# ------------------------------------------------------------------------------
# - Calcula un hash de las fuentes que afectan al bundle.
# - Si el hash no cambió y existe public/build/manifest.json, no hace nada.
# - `npm ci` solo si falta node_modules o cambió package-lock.json.
# - Ejecutar dos veces seguidas produce el mismo resultado (segunda = no-op).
#
# Uso: ./scripts/build-assets.sh
# ==============================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

for cmd in node npm sha256sum; do
    command -v "$cmd" >/dev/null 2>&1 || {
        echo "[assets] FALTA '$cmd'. Instala Node.js 22+ antes de continuar." >&2
        exit 1
    }
done

NODE_MAJOR="$(node -p 'process.versions.node.split(".")[0]')"
if [ "$NODE_MAJOR" -lt 22 ]; then
    echo "[assets] Node 22+ requerido (actual: $(node -v))." >&2
    exit 1
fi

hash_sources() {
    {
        cat package.json package-lock.json
        find resources/css resources/js -type f -print0 | sort -z | xargs -0 cat
        cat vite.config.js postcss.config.js tailwind.config.js
    } | sha256sum | cut -d' ' -f1
}

STAMP="public/build/.sources.sha256"
HASH="$(hash_sources)"

if [ -f "$STAMP" ] && [ "$(cat "$STAMP")" = "$HASH" ] && [ -f public/build/manifest.json ]; then
    echo "[assets] Sin cambios en las fuentes (${HASH:0:12}). Build omitido."
    exit 0
fi

if [ ! -d node_modules ] || [ package-lock.json -nt node_modules/.package-lock.json ]; then
    echo "[assets] Dependencias desactualizadas → npm ci"
    npm ci --no-audit --no-fund
fi

echo "[assets] Compilando (${HASH:0:12})…"
npm run build

mkdir -p public/build
printf '%s\n' "$HASH" > "$STAMP"

ASSET="$(node -e "const m=require('./public/build/manifest.json');process.stdout.write(m['resources/css/app.css'].file)")"
echo "[assets] Listo: public/build/${ASSET}"
