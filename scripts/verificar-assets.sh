#!/usr/bin/env bash
# ==============================================================================
# verificar-assets.sh — comprobación de assets/paleta servidos (solo lectura)
# ------------------------------------------------------------------------------
# Verifica en dos niveles:
#   1. Local: public/build/manifest.json existe y el CSS contiene los tokens de
#      marca (`#16181d` claro, `#ededf0` oscuro).
#   2. Servido: el CSS que entrega la app responde 200 y contiene la marca
#      negra (detecta builds viejos o `public/build` desactualizado).
#
# Uso: ./scripts/verificar-assets.sh [URL]      (default http://localhost:3300)
# Código de salida: 0 = todo correcto, 1 = falla (imprime el motivo).
# Idempotente: no escribe nada en el repositorio.
# ==============================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

BASE="${1:-${APP_URL:-http://localhost:3300}}"
BASE="${BASE%/}"

fail() {
    echo "[verify] FALLA: $*" >&2
    exit 1
}

[ -f public/build/manifest.json ] || fail "no existe public/build/manifest.json. Ejecuta ./scripts/build-assets.sh"
command -v node >/dev/null 2>&1 || fail "falta node (necesario para leer el manifest)"
command -v curl >/dev/null 2>&1 || fail "falta curl"

ASSET="$(node -e "const m=require('./public/build/manifest.json');process.stdout.write(m['resources/css/app.css'].file)")"
LOCAL="public/build/${ASSET}"
[ -f "$LOCAL" ] || fail "el manifest apunta a ${ASSET}, pero no existe ${LOCAL}"

grep -q '16181d' "$LOCAL" || fail "el CSS local no contiene la marca clara #16181d (¿build viejo?)"
grep -q 'ededf0' "$LOCAL" || fail "el CSS local no contiene la marca oscura #ededf0 (¿build viejo?)"
echo "[verify] Local OK: ${ASSET} (marca negra y token oscuro presentes)"

TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

CODE="$(curl -sS -o "$TMP" -w '%{http_code}' "${BASE}/build/${ASSET}" 2>/dev/null || true)"
[ "$CODE" = "200" ] || fail "${BASE}/build/${ASSET} respondió ${CODE:-sin respuesta}"
grep -q '16181d' "$TMP" || fail "el CSS servido por ${BASE} no tiene #16181d (assets viejos o bind mount desactualizado)"

echo "[verify] Servido OK: ${BASE}/build/${ASSET}"
echo "[verify] Todo correcto."
