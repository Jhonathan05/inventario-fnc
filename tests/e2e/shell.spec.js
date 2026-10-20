import { test, expect } from '@playwright/test';
import { loginAs, gotoTab } from './helpers/app.js';

/**
 * Patrones de shell de fnc-ssp-platform aplicados con la paleta actual:
 * saludo por hora (§3) y modo presentación con fallo silencioso (§4.6).
 * La transición de pantalla completa NO se valida por automatización (la
 * skill lo prohíbe): se verifica render y que el clic no rompa nada.
 *
 * La marca apilada (§5) se probó y se retiró a petición del usuario: el
 * topbar vuelve a mostrar sólo «Inventario ITAM».
 */
test.describe('Shell SSP', () => {
  test('saludo por hora y modo presentación', async ({ page }) => {
    const tab = await loginAs(page);
    await gotoTab(page, '/dashboard', tab);

    // Saludo por hora del patrón ejecutivo «inicio».
    await expect(page.locator('.fnc-page-head__title')).toContainText(
      /Buen(os días|as tardes|as noches)/
    );

    // La institución ya no se apila bajo la marca.
    await expect(page.locator('.topbar-title__sub')).toHaveCount(0);

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

  test('el topbar sólo se muestra en el tope de la página', async ({ page }) => {
    const tab = await loginAs(page);
    await gotoTab(page, '/dashboard', tab);

    const html = page.locator('html');
    const topbar = page.locator('header.topbar');
    const oculta = () => html.evaluate((el) => el.classList.contains('is-topbar-hidden'));
    const bordeInferior = () => topbar.evaluate((el) => Math.round(el.getBoundingClientRect().bottom));

    // En el tope la barra está visible y dentro de la vista.
    await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 10_000 }).toBe(0);
    await expect.poll(oculta, { timeout: 10_000 }).toBe(false);
    await expect(topbar).toBeVisible();
    await expect.poll(bordeInferior).toBeGreaterThan(0);

    // Al bajar desaparece (espera la transición): no queda pintada sobre el
    // contenido.
    await page.evaluate(() => window.scrollTo(0, 900));
    await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 10_000 }).toBeGreaterThan(500);
    await expect.poll(oculta, { timeout: 10_000 }).toBe(true);
    await expect
      .poll(bordeInferior, { timeout: 10_000 })
      .toBeLessThanOrEqual(0);

    // Subir un poco (sin llegar al tope) NO la devuelve.
    await page.evaluate(() => window.scrollTo(0, 400));
    await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 10_000 }).toBeLessThan(500);
    expect(await oculta(), 'la barra sigue oculta a mitad de página').toBe(true);

    // Sólo al volver al tope reaparece.
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 10_000 }).toBe(0);
    await expect.poll(oculta, { timeout: 10_000 }).toBe(false);
    await expect.poll(bordeInferior, { timeout: 10_000 }).toBeGreaterThan(0);
    await expect(topbar).toBeVisible();
  });

  test('el énfasis es negro y las alertas conservan su color', async ({ page }) => {
    const tab = await loginAs(page);
    await gotoTab(page, '/dashboard', tab);

    const tokens = await page.evaluate(() => {
      const cs = getComputedStyle(document.documentElement);
      const leer = (nombre) => cs.getPropertyValue(nombre).trim();
      return {
        brand: leer('--fnc-brand'),
        brandStrong: leer('--fnc-brand-strong'),
        danger: leer('--fnc-danger'),
        warning: leer('--fnc-warning'),
        info: leer('--fnc-info'),
        success: leer('--fnc-success'),
      };
    });

    const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
    const croma = ([r, g, b]) => Math.max(r, g, b) - Math.min(r, g, b);

    // La marca es negro grafito (sin croma) y su versión fuerte es negro puro.
    expect(croma(rgb(tokens.brand)), 'la marca debe ser gris/negro sin croma').toBeLessThanOrEqual(10);
    expect(Math.max(...rgb(tokens.brand)), 'la marca debe ser oscura').toBeLessThanOrEqual(40);
    expect(tokens.brandStrong.toLowerCase()).toBe('#000000');

    // Las alertas y acentos de categoría siguen siendo cromáticos.
    for (const [nombre, hex] of Object.entries({
      danger: tokens.danger,
      warning: tokens.warning,
      info: tokens.info,
      success: tokens.success,
    })) {
      expect(croma(rgb(hex)), `${nombre} debe conservar su color`).toBeGreaterThan(30);
    }
  });
});
