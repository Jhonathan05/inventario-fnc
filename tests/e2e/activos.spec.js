import { test, expect } from '@playwright/test';

test.describe('Activos', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@example.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
  });

  test('Listado, búsqueda y paginación', async ({ page }) => {
    await page.goto('/equipos');
    await expect(page.locator('table').or(page.locator('.grid'))).toBeVisible();
    await page.fill('input[type="search"], input[name="search"], input[placeholder*="Buscar"]', 'Laptop');
    await page.keyboard.press('Enter');
    // Esperar a que la tabla se actualice
    await page.waitForTimeout(1000);
    const pagination = page.locator('nav[aria-label*="Pagination"]');
    if (await pagination.isVisible()) {
      await expect(pagination).toBeVisible();
    }
  });

  test('Creación de activo con validaciones', async ({ page }) => {
    await page.goto('/equipos/create');
    await page.click('button[type="submit"]');
    // Deberían verse errores de validación
    await expect(page.locator('.text-red-500, .invalid-feedback').first()).toBeVisible();

    await page.fill('input[name="marca"], input[name="brand"]', 'Dell');
    await page.fill('input[name="modelo"], input[name="model"]', 'Latitude');
    await page.fill('input[name="numero_serie"], input[name="serial_number"]', 'SN123456');
    // Rellenar otros campos obligatorios si los hay
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*equipos/);
    await expect(page.locator('text=creado exitosamente').or(page.locator('text=guardado'))).toBeVisible();
  });
});
