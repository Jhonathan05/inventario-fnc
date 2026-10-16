import { test, expect } from '@playwright/test';
import { login, gotoTab } from './helpers/app.js';

test.describe('Préstamos', () => {
  test('Caso 1 y 2: formulario de préstamo y sus datos obligatorios', async ({ page }) => {
    const tab = await login(page);

    await gotoTab(page, '/prestamos/create', tab);

    // El formulario sólo es alcanzable con sesión y oferta los campos
    // obligatorios del flujo de préstamo. (No se envía para no persistir datos.)
    await expect(page.locator('select[name="equipo_id"]')).toBeVisible();
    await expect(page.locator('input[name="persona_nombre"]')).toBeVisible();
    await expect(page.locator('input[name="persona_documento"]')).toBeVisible();
    await expect(page.locator('input[name="fecha_inicio"]')).toBeVisible();
    await expect(page.locator('input[name="fecha_devolucion_prevista"]')).toBeVisible();
    await expect(page.locator('textarea[name="motivo"], input[name="motivo"]')).toBeVisible();
  });
});
