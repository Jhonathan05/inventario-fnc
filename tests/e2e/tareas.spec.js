import { test, expect } from '@playwright/test';
import { loginAs, gotoTab, dismissAlert } from './helpers/app.js';

test.describe('Tareas', () => {
  test('tablero kanban: alta rápida, completar, reabrir y eliminar', async ({ page }) => {
    const tab = await loginAs(page);

    // El panel del dashboard enlaza el tablero completo.
    await gotoTab(page, '/dashboard', tab);
    await page.getByRole('link', { name: /Ver todas/ }).click();
    await expect(page).toHaveURL(/\/tareas/);

    // Cuatro columnas del kanban: Alta, Media, Baja y derivadas CMDB.
    for (const columna of ['Alta', 'Media', 'Baja', 'CMDB']) {
      await expect(page.locator('.fnc-kanban__head').filter({ hasText: columna })).toBeVisible();
    }

    // Alta rápida con prioridad Alta. El título se escribe ya en Title-case
    // porque el sanitizer global (CapitalizeFirstLetter) lo transforma así.
    const titulo = `E2E Verificar Inventario ${Date.now()}`;
    await page.getByPlaceholder('Describe la tarea pendiente…').fill(titulo);
    await page.locator('select[name="prioridad"]').selectOption('Alta');
    await page.getByRole('button', { name: 'Crear la tarea' }).click();
    await expect(page.locator('.fnc-kanban__card').filter({ hasText: titulo })).toBeVisible({ timeout: 20000 });

    // La columna Alta incrementa su contador y el buscador la encuentra.
    await page.getByPlaceholder('Buscar por título…').fill('E2E Verificar Inventario');
    await page.getByRole('button', { name: 'Aplicar filtros' }).click();    await expect(page.locator('.fnc-kanban__card').filter({ hasText: titulo })).toBeVisible();
    await expect(page.locator('.fnc-kanban__card-title').first()).toContainText(titulo);

    // Completar: la tarea pasa a «Hechas recientemente» (botón Reabrir único).
    const tarjeta = page.locator('.fnc-kanban__card').filter({ hasText: titulo });
    await tarjeta.getByRole('button', { name: /^Marcar como hecha/ }).click();
    await expect(page.getByRole('button', { name: `Reabrir: ${titulo}` })).toBeVisible({ timeout: 15000 });

    // Reabrir desde «Hechas»: vuelve al kanban.
    await page.getByRole('button', { name: `Reabrir: ${titulo}` }).click();
    await expect(page.locator('.fnc-kanban__card').filter({ hasText: titulo })).toBeVisible({ timeout: 15000 });

    // Eliminar con confirmación: desaparece del tablero.
    page.on('dialog', (dialog) => dialog.accept());
    await page
      .locator('.fnc-kanban__card')
      .filter({ hasText: titulo })
      .getByRole('button', { name: /^Eliminar:/ })
      .click();
    await expect(page.locator('.fnc-kanban__card').filter({ hasText: titulo })).toHaveCount(0, { timeout: 15000 });
  });

  test('las derivadas CMDB enlazan a la ficha de origen', async ({ page }) => {
    const tab = await loginAs(page);
    await gotoTab(page, '/tareas', tab);

    // En el entorno de desarrollo hay activos con marcadores PENDIENTE:
    // la columna CMDB debe tener tarjetas con acción «Corregir el dato».
    const columnaCmdb = page.locator('.fnc-kanban__col').filter({ hasText: 'Derivadas CMDB' });
    const tarjetas = columnaCmdb.locator('.fnc-kanban__card');
    const total = await tarjetas.count();
    test.skip(total === 0, 'sin activos incompletos en este entorno');

    await expect(tarjetas.first().getByRole('link', { name: /Corregir el dato de origen/ })).toHaveCount(1);
  });
});
