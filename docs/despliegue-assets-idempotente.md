# Despliegue idempotente de assets y paleta

> **Problema que resuelve:** `public/build/` está en `.gitignore`, así que
> `git pull` **no** actualiza el CSS/JS compilado. Una máquina podía quedar
> sirviendo la paleta anterior (vinotinto) aunque el código fuente ya tuviera
> el tema negro/grafito.

---

## 1. Principio

| Nivel | Fuente de verdad | Quién lo genera |
|---|---|---|
| Fuente | `resources/css/*`, `resources/js/*`, `tailwind.config.js` | Repositorio (git) |
| Compilado dev | `public/build/` | `scripts/build-assets.sh` en el host |
| Compilado imagen | `public/build/` dentro de la imagen | Stage `frontend` del `Dockerfile` |

El stage `frontend` del `Dockerfile` ejecuta `npm ci && npm run build` y copia
el resultado a la imagen final con `COPY --from=frontend`. Por eso **una imagen
reconstruida siempre trae la paleta del código que empaquetó**, sin depender de
lo que hubiera en la máquina que construye.

---

## 2. Contrato de idempotencia

| Paso | Cuándo actúa | Repetir dos veces |
|---|---|---|
| `scripts/build-assets.sh` | Solo si cambió el hash de fuentes o falta el manifest | Segunda ejecución = **no-op** (`exit 0`) |
| `npm ci` (dentro del script) | Solo si falta `node_modules` o cambió `package-lock.json` | No reinstala |
| `docker compose up -d --force-recreate` | Recrea contenedores con la misma definición | Mismo estado final |
| `docker compose build` | Reutiliza capas si las fuentes no cambiaron | Misma imagen |
| `scripts/verificar-assets.sh` | Solo lectura | No modifica nada |

Ningún paso borra datos: los volúmenes `mysql_data`, `vendor_data` y
`actas_firmadas_data` se conservan.

---

## 3. Desarrollo (compose con bind mount)

El compose de desarrollo monta `.:/var/www`, por lo que el contenedor sirve el
`public/build` **del host**.

```bash
git pull --ff-only

./scripts/build-assets.sh          # compila solo si hace falta
docker compose up -d --force-recreate

./scripts/verificar-assets.sh http://localhost:3300
```

En este equipo (podman + systemd de usuario) el equivalente es:

```bash
./scripts/build-assets.sh
systemctl --user restart inventario-fnc.service
./scripts/verificar-assets.sh http://localhost:3300
```

---

## 4. Producción (imagen preconstruida)

```bash
git pull --ff-only

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

./scripts/verificar-assets.sh http://IP:3300
```

> El `build` compila los assets **dentro** de la imagen. No hace falta ejecutar
> `npm run build` en el host de producción.
>
> Si se publica la imagen en un registro (Docker Hub), hay que reconstruirla y
> subirla con un tag nuevo cuando cambien CSS/JS: reutilizar un tag viejo
> mantiene los assets viejos.

---

## 5. Verificación

`scripts/verificar-assets.sh [URL]` comprueba:

1. Existe `public/build/manifest.json`.
2. El CSS local contiene `#16181d` (marca clara) y `#ededf0` (marca oscura).
3. El CSS servido en `${URL}/build/<asset>` responde **200** y contiene
   `#16181d`.

Salida esperada:

```
[verify] Local OK: assets/app-XXXXXXXX.css (marca negra y token oscuro presentes)
[verify] Servido OK: http://localhost:3300/build/assets/app-XXXXXXXX.css
[verify] Todo correcto.
```

Código de salida `1` = algo está desactualizado; el mensaje indica qué.

Prueba de comportamiento (opcional, requiere Playwright):

```bash
npx playwright test -c playwright.container.config.js shell.spec.js
# "el énfasis es negro y las alertas conservan su color"
```

---

## 6. Diagnóstico rápido

| Síntoma | Causa probable | Acción |
|---|---|---|
| **Login** borgoña **e** interior borgoña | Commit/rama vieja en esa máquina | `git log -1`; `git checkout main`; `git pull --ff-only` |
| Login negro, **interior borgoña** | `public/build` viejo (dev) o imagen vieja (prod) | `./scripts/build-assets.sh` + recrear; en prod `build` + `up -d` |
| Todo negro pero un navegador no | Caché del navegador con hash viejo | `Ctrl+Shift+R` |
| `manifest.json` no existe | Nunca se compiló en esa máquina | `./scripts/build-assets.sh` |
| El CSS servido no cambia tras compilar | Bind mount o contenedor sin recrear | `up -d --force-recreate` / `restart` |

Comprobación manual equivalente:

```bash
grep -c 16181d public/build/assets/app-*.css
curl -s http://localhost:3300/build/assets/app-*.css | grep -c 16181d
```

---

## 7. Reversión

```bash
git reset --hard restore-point-tema-negro   # vuelve al punto previo a la integración
./scripts/build-assets.sh
```

El tag `restore-point-tema-negro` (commit `8e93397`) contiene el tema negro y el
frontend de la sesión anterior.
