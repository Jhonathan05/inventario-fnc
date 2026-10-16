import { test, expect } from '@playwright/test';
import { PROFILES, loginAs, csrfToken } from './helpers/app.js';

test.describe('Acceso institucional (Keycloak / mock)', () => {
  test('el selector mock lista los perfiles administrador y analista tic', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator(`[data-mock-role="${PROFILES.administrador}"]`)).toBeVisible();
    await expect(page.locator(`[data-mock-role="${PROFILES.analistaTic}"]`)).toBeVisible();

    // El distintivo debe ser el de la sesión mock (el hero se oculta en móvil).
    await expect(page.locator('.mock-badge')).toBeVisible();
    await expect(page.locator('.mock-badge')).toContainText('MOCK');
  });

  test('el login por perfil entra al sistema y conserva el contexto de pestaña', async ({ page }) => {
    const tab = await loginAs(page, PROFILES.administrador);

    await expect(page).toHaveURL(/dashboard|inicio/);
    expect(page.url()).toContain(`_tab=${tab}`);
    await expect(page.locator('.mock-mode-indicator')).toBeVisible();
  });

  test('el perfil analista tic respeta el gate RBAC (403 en licencias)', async ({ page }) => {
    const tab = await loginAs(page, PROFILES.analistaTic);

    const permitido = await page.goto(`/equipos?_tab=${tab}`);
    expect(permitido.status()).toBe(200);

    const denegado = await page.goto(`/licencias?_tab=${tab}`);
    expect(denegado.status()).toBe(403);
  });

  test('un perfil mock inválido responde 400', async ({ page }) => {
    await page.goto('/login');
    const token = await csrfToken(page);

    const res = await page.request.post('/mock-auth/mock-login', {
      form: { _token: token, role: 'perfil-inexistente' },
      maxRedirects: 0,
    });

    expect(res.status()).toBe(400);
  });

  test('el login local (email/contraseña) está deshabilitado: 403', async ({ page }) => {
    await page.goto('/login');
    const token = await csrfToken(page);

    const res = await page.request.post('/login', {
      form: { _token: token, email: 'admin@example.com', password: 'password' },
      maxRedirects: 0,
    });

    expect(res.status()).toBe(403);
  });

  test('el logout cierra la sesión y devuelve al acceso', async ({ page }) => {
    const tab = await loginAs(page);
    const token = await csrfToken(page);

    const res = await page.request.post(`/mock-auth/mock-logout?_tab=${tab}`, {
      form: { _token: token, _tab: tab },
      maxRedirects: 0,
    });
    expect(res.status()).toBe(302);

    await page.goto(`/equipos?_tab=${tab}`);
    await expect(page).toHaveURL(/login/);
  });
});
