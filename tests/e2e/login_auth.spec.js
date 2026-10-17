import { test, expect } from '@playwright/test';
import { PROFILES, loginAs } from './helpers/app.js';

test.describe('Autenticación y sesiones', () => {
  test('Ruta protegida sin autenticación redirige al acceso', async ({ page }) => {
    await page.goto('/equipos');
    await expect(page).toHaveURL(/login/);
  });

  test('Login mock de administrador llega al sistema', async ({ page }) => {
    await loginAs(page, PROFILES.administrador);
    await expect(page).toHaveURL(/dashboard|inicio/);
  });

  test('Login mock de analista tic llega al sistema', async ({ page }) => {
    await loginAs(page, PROFILES.analistaTic);
    await expect(page).toHaveURL(/dashboard|inicio/);
  });

  test('La sesión se mantiene al navegar con el mismo contexto de pestaña', async ({ page }) => {
    const tab = await loginAs(page);

    const res = await page.goto(`/equipos?_tab=${tab}`);

    expect(res.status()).toBe(200);
    // El shell tiene dos <header> (topbar y cabecera de contenido): se apunta
    // a la topbar para no caer en la violación de modo estricto.
    await expect(page.locator('header.topbar')).toBeVisible();
    await expect(page).not.toHaveURL(/\/login/);
  });

  test('La pantalla de acceso no expone el formulario local (login deshabilitado)', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('[data-mock-role]').first()).toBeVisible();
    await expect(page.locator('input[type="password"]')).toHaveCount(0);
  });
});
