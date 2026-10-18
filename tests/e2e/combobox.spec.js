import { test, expect } from '@playwright/test';
import { login, gotoTab } from './helpers/app.js';

test.describe('Combobox premium (selects del aplicativo)', () => {
  let tab;

  test.beforeEach(async ({ page }) => {
    tab = await login(page);
  });

  test('el select del acta se convierte en buscador con panel flotante', async ({ page }) => {
    await gotoTab(page, '/actas/create', tab);

    const combo = page.locator('.fnc-combo').filter({ has: page.locator('select[name="tipo_accion"]') });
    await expect(combo).toBeVisible();
    const input = combo.locator('.fnc-combo__input');
    await expect(input).toBeVisible();

    // El select nativo sigue en el DOM (enviable con el form) pero fuera de la vista
    await expect(combo.locator('select[name="tipo_accion"]')).toHaveCount(1);

    // Al enfocar se abre el panel con las opciones
    await input.click();
    const panel = page.locator('.fnc-combo__panel').first();
    await expect(panel).toBeVisible();
    expect(await panel.locator('.fnc-combo__opt').count()).toBeGreaterThan(1);

    // Escribir filtra las opciones
    await input.fill('pre');
    await expect(panel.locator('.fnc-combo__opt').first()).toContainText(/réstamo|restamo/i);

    // Enter confirma la opción activa
    await input.press('Enter');
    await expect(panel).toBeHidden();
    await expect(input).toHaveValue(/réstamo|restamo/i);
    await expect(input).not.toHaveAttribute('data-empty', '1');
    const valor = await page.$eval('select[name="tipo_accion"]', (el) => el.value);
    expect(valor).toBe('prestamo');

    // Al reabrir aparecen las selecciones recientes (icono de reloj)
    await input.click();
    await expect(panel).toBeVisible();
    await expect(panel.locator('.fnc-combo__recents')).toContainText('Recientes');

    // Escape cierra el panel y restaura el texto de la selección
    await input.fill('xxxx');
    await input.press('Escape');
    await expect(panel).toBeHidden();
    await expect(input).toHaveValue(/réstamo|restamo/i);
  });

  test('selectOption programatico (tests y scripts externos) sigue funcionando', async ({ page }) => {
    await gotoTab(page, '/equipos/create', tab);

    // Playwright selectOption sobre el select nativo (visible por bounding box)
    await page.selectOption('select[name="tipo_recurso_id"]', { index: 1 });

    // El combobox refleja el cambio trazado por el listener de 'change'
    const comboInput = page.locator('.fnc-combo').filter({
      has: page.locator('select[name="tipo_recurso_id"]'),
    }).locator('.fnc-combo__input');
    await expect(comboInput).not.toHaveAttribute('data-empty', '1');
  });

  test('selects añadidos dinamicamente se mejoran solos (Observer)', async ({ page }) => {
    await gotoTab(page, '/actas/create', tab);

    await page.evaluate(() => {
      const div = document.createElement('div');
      div.id = 'cbk-dinamico';
      div.innerHTML = '<select name="campo_dinamico" class="fnc-input">'
        + '<option value="">Elegir…</option><option value="ALPHA">Alfa</option><option value="BETA">Beta</option></select>';
      document.getElementById('formActa').appendChild(div);
    });

    const combo = page.locator('.fnc-combo').filter({ has: page.locator('select[name="campo_dinamico"]') });
    await expect(combo).toBeVisible();
    await combo.locator('.fnc-combo__input').fill('bet');
    await page.locator('.fnc-combo__opt').first().click();

    const valor = await page.$eval('select[name="campo_dinamico"]', (el) => el.value);
    expect(valor).toBe('BETA');
  });
});
