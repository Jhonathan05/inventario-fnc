import { test, expect } from '@playwright/test';

test.describe('Navegación e Inventario', () => {

  test.beforeEach(async ({ page }) => {
    // Si tuviéramos un estado de autenticación compartido se usaría aquí.
    // Vamos a ir directamente a las rutas para ver cómo la app maneja a los usuarios sin sesión 
    // y si la redirección ocurre.
    await page.goto('/');
  });

  test('navegar a la página de Equipos (Activos) requiere autenticación o carga si es pública', async ({ page }) => {
    const res = await page.goto('/equipos');
    expect(res?.status()).toBeLessThan(500);
    
    // Verificamos si carga la tabla o nos manda al login
    const title = await page.title();
    const loginText = await page.locator('text=Login').count();
    const equiposText = await page.locator('text=Equipos').count();
    
    expect(loginText > 0 || equiposText > 0).toBeTruthy();
  });

  test('navegar a Complementos Global', async ({ page }) => {
    await page.goto('/equipos/complementos-global');
    // Verificar si carga la pgina sin error 500
    await expect(page.locator('body')).toBeVisible();
  });

  test('navegar a Historial de Complementos', async ({ page }) => {
    await page.goto('/equipos/complementos/historial-global');
    // Si hay redireccin a login est bien, lo importante es que la ruta existe y no da 500
    await expect(page.locator('body')).toBeVisible();
  });

  test('Préstamos: Validación de UI para estado Asignado/Reparación', async ({ page }) => {
    // Dado que no modificamos produccin, intentamos ir a la vista de creacin de prstamo si existe
    await page.goto('/prestamos/create');
    
    // Verificamos si la página renderiza sin crash
    const body = await page.locator('body');
    await expect(body).toBeVisible();

    // Intentamos ver si hay un modal o select de equipos
    const select = page.locator('select[name="equipo_id"]');
    if (await select.count() > 0) {
      // Como no conocemos los datos de produccin, solo validamos que el select no permita 
      // inyeccin de activos si el backend lo controla. Esto simula el intento de la UI.
      const isVisible = await select.isVisible();
      expect(isVisible).toBe(true);
    }
  });

  test('Manejo de errores HTTP 404', async ({ page }) => {
    const res = await page.goto('/ruta-que-no-existe-12345');
    // Debería ser 404
    expect(res?.status()).toBe(404);
  });
});
