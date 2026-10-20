import { test, expect } from '@playwright/test';
import { loginAs, gotoTab, dismissAlert } from './helpers/app.js';

test.describe('Backups', () => {
  test('admin crea, descarga y elimina un backup', async ({ page }) => {
    const tab = await loginAs(page);

    await gotoTab(page, '/backups', tab);
    await expect(page.getByRole('heading', { name: 'Backups' })).toBeVisible();

    // Crear backup ahora: el POST redirige de vuelta con la fila nueva.
    await page.getByRole('button', { name: 'Crear backup ahora' }).click();
    await expect(page.locator('table tbody').first()).toContainText('.sql.gz', { timeout: 45000 });
    await dismissAlert(page);

    const nombre = (await page.locator('.fnc-udash__name').first().textContent()).trim();
    expect(nombre).toMatch(/^inventario-fnc_backup_\d{4}_\d{2}_\d{2}_\d{6}\.sql\.gz$/);

    // Descarga autenticada del volcado (comparte cookies del contexto).
    const origen = new URL(page.url()).origin;
    const respuesta = await page.request.get(
      `${origen}/backups/${encodeURIComponent(nombre)}/descargar?_tab=${tab}`
    );
    expect(respuesta.status()).toBe(200);
    expect(respuesta.headers()['content-type']).toContain('gzip');
    expect((await respuesta.body()).length).toBeGreaterThan(100);

    // Eliminar: el confirm() del formulario se acepta.
    page.on('dialog', (dialog) => dialog.accept());
    await page.locator('button[aria-label^="Eliminar"]').first().click();
    await expect(page.locator('table tbody').first()).not.toContainText(nombre, { timeout: 15000 });

    // Y volver a crear para dejar el módulo con un backup de ejemplo.
    await page.getByRole('button', { name: 'Crear backup ahora' }).click();
    await expect(page.locator('table tbody').first()).toContainText('.sql.gz', { timeout: 45000 });
  });
});
