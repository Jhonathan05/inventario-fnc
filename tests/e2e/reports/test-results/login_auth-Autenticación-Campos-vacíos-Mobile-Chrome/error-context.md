# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: login_auth.spec.js >> Autenticación >> Campos vacíos
- Location: tests\e2e\login_auth.spec.js:21:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('text=El campo correo electrónico es obligatorio')
Expected: visible
Timeout: 5000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for locator('text=El campo correo electrónico es obligatorio')

```

```yaml
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
  3  | test.describe('Autenticación', () => {
  4  |   test('Login correcto', async ({ page }) => {
  5  |     await page.goto('/login');
  6  |     await page.fill('input[type="email"]', 'admin@example.com');
  7  |     await page.fill('input[type="password"]', 'password');
  8  |     await page.click('button[type="submit"]');
  9  |     await expect(page).toHaveURL(/.*dashboard|.*equipos/);
  10 |     await expect(page.locator('text=Cerrar Sesión').or(page.locator('text=Logout'))).toBeVisible();
  11 |   });
  12 | 
  13 |   test('Login incorrecto', async ({ page }) => {
  14 |     await page.goto('/login');
  15 |     await page.fill('input[type="email"]', 'admin@example.com');
  16 |     await page.fill('input[type="password"]', 'incorrecta');
  17 |     await page.click('button[type="submit"]');
  18 |     await expect(page.locator('text=Estas credenciales no coinciden')).toBeVisible();
  19 |   });
  20 | 
  21 |   test('Campos vacíos', async ({ page }) => {
  22 |     await page.goto('/login');
  23 |     await page.click('button[type="submit"]');
> 24 |     await expect(page.locator('text=El campo correo electrónico es obligatorio')).toBeVisible();
     |                                                                                   ^ Error: expect(locator).toBeVisible() failed
  25 |   });
  26 | 
  27 |   test('Ruta protegida sin autenticación', async ({ page }) => {
  28 |     await page.goto('/equipos');
  29 |     await expect(page).toHaveURL(/.*login/);
  30 |   });
  31 | });
  32 | 
```