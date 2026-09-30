# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: activos.spec.js >> Activos >> Listado, búsqueda y paginación
- Location: tests\e2e\activos.spec.js:11:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('table').or(locator('.grid'))
Expected: visible
Timeout: 5000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for locator('table').or(locator('.grid'))

```

```yaml
- text: Sistema Institucional
- heading "Sistema de Inventario" [level=1]
- paragraph: Comité Departamental de Cafeteros del Tolima — Plataforma de registro y control de equipos tecnológicos.
- img "Logo Federación de Cafeteros"
- heading "Comité de Cafeteros" [level=2]
- paragraph: del Tolima
- heading "Acceso Institucional" [level=3]
- paragraph: Ingresa tus credenciales para continuar
- text: Correo electrónico
- textbox "Correo electrónico":
  - /placeholder: nombre@cafedecolombia.com
- text: Contraseña
- textbox "Contraseña":
  - /placeholder: ••••••••
- checkbox "Solicitar cambio de contraseña"
- text: Solicitar cambio de contraseña
- button "Iniciar sesión"
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | test.describe('Activos', () => {
  4  |   test.beforeEach(async ({ page }) => {
  5  |     await page.goto('/login');
  6  |     await page.fill('input[type="email"]', 'admin@example.com');
  7  |     await page.fill('input[type="password"]', 'password');
  8  |     await page.click('button[type="submit"]');
  9  |   });
  10 | 
  11 |   test('Listado, búsqueda y paginación', async ({ page }) => {
  12 |     await page.goto('/equipos');
> 13 |     await expect(page.locator('table').or(page.locator('.grid'))).toBeVisible();
     |                                                                   ^ Error: expect(locator).toBeVisible() failed
  14 |     await page.fill('input[type="search"], input[name="search"], input[placeholder*="Buscar"]', 'Laptop');
  15 |     await page.keyboard.press('Enter');
  16 |     // Esperar a que la tabla se actualice
  17 |     await page.waitForTimeout(1000);
  18 |     const pagination = page.locator('nav[aria-label*="Pagination"]');
  19 |     if (await pagination.isVisible()) {
  20 |       await expect(pagination).toBeVisible();
  21 |     }
  22 |   });
  23 | 
  24 |   test('Creación de activo con validaciones', async ({ page }) => {
  25 |     await page.goto('/equipos/create');
  26 |     await page.click('button[type="submit"]');
  27 |     // Deberían verse errores de validación
  28 |     await expect(page.locator('.text-red-500, .invalid-feedback').first()).toBeVisible();
  29 | 
  30 |     await page.fill('input[name="marca"], input[name="brand"]', 'Dell');
  31 |     await page.fill('input[name="modelo"], input[name="model"]', 'Latitude');
  32 |     await page.fill('input[name="numero_serie"], input[name="serial_number"]', 'SN123456');
  33 |     // Rellenar otros campos obligatorios si los hay
  34 |     await page.click('button[type="submit"]');
  35 |     await expect(page).toHaveURL(/.*equipos/);
  36 |     await expect(page.locator('text=creado exitosamente').or(page.locator('text=guardado'))).toBeVisible();
  37 |   });
  38 | });
  39 | 
```