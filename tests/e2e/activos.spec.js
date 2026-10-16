import { test, expect } from '@playwright/test';
import { login, gotoTab, submitButton, dismissAlert } from './helpers/app.js';

test.describe('Activos', () => {
  let tab;

  test.beforeEach(async ({ page }) => {
    tab = await login(page);
  });

  test('Listado, búsqueda y paginación', async ({ page }) => {
    await gotoTab(page, '/equipos', tab);

    // La vista lista los activos en una o más tablas.
    await expect(page.locator('table').first()).toBeVisible();

    // Búsqueda libre: el formulario se envía por GET con el parámetro `buscar`.
    await page.fill('input[name="buscar"]', 'Laptop');
    await Promise.all([
      page.waitForURL((url) => url.searchParams.get('buscar') === 'Laptop', { timeout: 15000 }),
      page.getByRole('button', { name: 'Filtrar' }).click(),
    ]);

    await expect(page.locator('table').first()).toBeVisible();

    const pagination = page.locator('nav[aria-label*="Pagination"]').first();
    if (await pagination.isVisible().catch(() => false)) {
      await expect(pagination).toBeVisible();
    }
  });

  test('Creación de activo con validaciones', async ({ page }) => {
    await gotoTab(page, '/equipos/create', tab);

    // Envío vacío: el formulario usa `novalidate`, así que la validación del
    // servidor devuelve los errores en línea y en un modal de SweetAlert2.
    await submitButton(page).click();
    await expect(page.locator('.invalid-feedback').first()).toBeVisible();
    await expect(page).toHaveURL(/equipos\/create/);
    await dismissAlert(page);

    // Camino feliz con un tipo de recurso sencillo.
    const serial = `SN-E2E-${Date.now()}`;
    await page.selectOption('select[name="tipo_recurso_id"]', { index: 1 });
    await page.fill('input[name="marca"]', 'Dell');
    await page.fill('input[name="modelo"]', 'Latitude');
    await page.fill('input[name="placa"]', '12345');
    await page.fill('input[name="serial"]', serial);

    await Promise.all([
      page.waitForURL(/\/equipos(\?|$)/, { timeout: 20000 }),
      submitButton(page).click(),
    ]);

    // El alta correcta redirige al listado mostrando un aviso de éxito.
    await expect(page.locator('.swal2-title').first()).toContainText('Equipo registrado correctamente');

    // El activo recién creado debe ser localizable por su serial.
    await page.fill('input[name="buscar"]', serial);
    await Promise.all([
      page.waitForURL((url) => url.searchParams.get('buscar') === serial, { timeout: 15000 }),
      page.getByRole('button', { name: 'Filtrar' }).click(),
    ]);
    await expect(page.locator('table').first()).toContainText(serial);
  });
});
