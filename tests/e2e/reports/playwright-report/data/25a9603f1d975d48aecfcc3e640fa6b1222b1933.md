# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: login_auth.spec.js >> Autenticación >> Login correcto
- Location: tests\e2e\login_auth.spec.js:4:3

# Error details

```
Error: expect(page).toHaveURL(expected) failed

Expected pattern: /.*dashboard|.*equipos/
Received string:  "http://localhost:3300/login"
Timeout: 5000ms

Call log:
  - Expect "toHaveURL" with timeout 5000ms
    13 × locator resolved to <html lang="es" class="swal2-shown swal2-height-auto">…</html>
       - unexpected value "http://localhost:3300/login"

```

```yaml
- dialog "Revise los campos del formulario":
  - heading "Revise los campos del formulario" [level=2]
  - list:
    - listitem: Demasiados intentos de inicio de sesión. Por favor, inténtelo de nuevo en 43 segundos.
  - button "Entendido"
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
> 9  |     await expect(page).toHaveURL(/.*dashboard|.*equipos/);
     |                        ^ Error: expect(page).toHaveURL(expected) failed
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
  24 |     await expect(page.locator('text=El campo correo electrónico es obligatorio')).toBeVisible();
  25 |   });
  26 | 
  27 |   test('Ruta protegida sin autenticación', async ({ page }) => {
  28 |     await page.goto('/equipos');
  29 |     await expect(page).toHaveURL(/.*login/);
  30 |   });
  31 | });
  32 | 
```