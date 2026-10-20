import { test, expect } from '@playwright/test';
import { loginAs, gotoTab } from './helpers/app.js';

/**
 * Patrones de shell de fnc-ssp-platform aplicados con la paleta actual:
 * saludo por hora (§3), modo presentación con fallo silencioso (§4.6) y
 * marca apilada (§5). La transición de pantalla completa NO se valida por
 * automatización (la skill lo prohíbe): se verifica render y que el clic
 * no rompa nada.
 */
test.describe('Shell SSP', () => {
  test('saludo por hora, marca apilada y modo presentación', async ({ page }) => {
    const tab = await loginAs(page);
    await gotoTab(page, '/dashboard', tab);

    // Saludo por hora del patrón ejecutivo «inicio».
    await expect(page.locator('.fnc-page-head__title')).toContainText(
      /Buen(os días|as tardes|as noches)/
    );

    // Marca apilada: siempre presente; visible sólo en escritorio (≥lg).
    const marca = page.locator('.topbar-title__sub');
    await expect(marca).toBeAttached();
    await expect(marca).toHaveText(/Comité de Cafeteros del Tolima/);
    if ((page.viewportSize()?.width ?? 0) >= 992) {
      await expect(marca).toBeVisible();
    } else {
      await expect(marca).toBeHidden();
    }

    // Modo presentación: botón presente, estado inicial accesible y clic
    // con fallo silencioso (la página sigue viva tras el intento).
    const btn = page.locator('#presentacionBtn');
    await expect(btn).toBeAttached();
    await expect(btn).toHaveAttribute('aria-pressed', 'false');
    if ((page.viewportSize()?.width ?? 0) >= 768) {
      await expect(btn).toBeVisible();
      await btn.click();
      await expect(page.locator('.fnc-dash')).toBeVisible();
    }
  });
});
