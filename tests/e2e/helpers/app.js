import { expect } from '@playwright/test';

export const ADMIN = { email: 'admin@example.com', password: 'password' };

/**
 * Inicia sesión con el usuario administrador sembrado por DatabaseSeeder.
 *
 * Devuelve el identificador de pestaña (`_tab`) que usa TabSessionGuard.
 * Es imprescindible reutilizarlo en las navegaciones posteriores: la sesión
 * de Laravel está particionada por pestaña, de modo que una URL sin `_tab`
 * crea un contexto nuevo sin autenticar.
 */
export async function login(page) {
  await page.goto('/login');
  await page.fill('input[type="email"]', ADMIN.email);
  await page.fill('input[type="password"]', ADMIN.password);
  await Promise.all([
    page.waitForURL(/dashboard|equipos|inicio/, { timeout: 20000 }),
    page.click('button[type="submit"]'),
  ]);

  return new URL(page.url()).searchParams.get('_tab');
}

/**
 * Navega a `path` conservando el contexto de pestaña.
 */
export async function gotoTab(page, path, tab) {
  const suffix = tab ? `${path.includes('?') ? '&' : '?'}_tab=${tab}` : '';
  return page.goto(`${path}${suffix}`);
}

/**
 * Botón de envío visible del formulario principal.
 * Las páginas incluyen botones `type=submit` ocultos (menú de usuario), por lo
 * que un selector genérico provoca un "strict mode violation".
 */
export function submitButton(page) {
  return page.locator('form button[type="submit"]:visible, form input[type="submit"]:visible').first();
}

/**
 * Cierra el modal de SweetAlert2 que la capa global abre para mostrar errores
 * de validación y que, mientras está abierto, intercepta los clics.
 */
export async function dismissAlert(page) {
  const confirm = page.locator('.swal2-container .swal2-confirm');
  if (await confirm.isVisible().catch(() => false)) {
    await confirm.click();
    await expect(page.locator('.swal2-container')).toHaveCount(0);
  }
}

export { expect };
