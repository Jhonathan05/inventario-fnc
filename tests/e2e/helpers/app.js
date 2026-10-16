import { expect } from '@playwright/test';

/**
 * Perfiles mock expuestos por el selector (skill fnc-mock-auth).
 * Los valores son exactamente el `data-mock-role` renderizado por la vista.
 */
export const PROFILES = {
  administrador: 'Administrador',
  analistaTic: 'Analista Tic',
};

/**
 * Usuario local sembrado por DatabaseSeeder. Sólo se usa para comprobar que el
 * login local (email/contraseña) está deshabilitado por defecto.
 */
export const ADMIN = { email: 'admin@example.com', password: 'password' };

/**
 * Inicia sesión con un perfil mock y devuelve el identificador de pestaña
 * (`_tab`) que usa TabSessionGuard. Es imprescindible reutilizarlo en las
 * navegaciones posteriores: la sesión de Laravel está particionada por
 * pestaña, de modo que una URL sin `_tab` crea un contexto nuevo sin
 * autenticar.
 */
export async function loginAs(page, role = PROFILES.administrador) {
  await page.goto('/login');

  const card = page.locator(`[data-mock-role="${role}"]`);
  await expect(card).toBeVisible({ timeout: 15000 });

  await Promise.all([
    page.waitForURL(/\/(dashboard|inicio|equipos)/, { timeout: 30000 }),
    card.click(),
  ]);

  const tab = new URL(page.url()).searchParams.get('_tab');
  expect(tab, 'la sesión debe conservar el contexto de pestaña (_tab)').toBeTruthy();

  return tab;
}

/**
 * Inicia sesión con el perfil administrador (perfil por defecto de la suite).
 */
export async function login(page) {
  return loginAs(page, PROFILES.administrador);
}

/**
 * Token CSRF de la página actual.
 */
export async function csrfToken(page) {
  return page.locator('meta[name="csrf-token"]').getAttribute('content');
}

/**
 * Cierra la sesión por el endpoint real (POST /logout) conservando el `_tab`,
 * imprescindible para que el guard elimine la clave de sesión correcta.
 */
export async function logout(page, tab) {
  const token = await csrfToken(page);

  return page.request.post(`/logout?_tab=${tab}`, {
    form: { _token: token, _tab: tab },
    maxRedirects: 0,
  });
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
