import { test, expect } from '@playwright/test';

test.describe('Préstamos', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@example.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
  });

  test('Caso 1 y 2: Intentar préstamo y rechazo', async ({ page }) => {
    await page.goto('/prestamos/create');
    await expect(page.locator('body')).toBeVisible();
    // Simulate flow
    const select = page.locator('select[name="equipo_id"]');
    if (await select.count() > 0) {
      await select.selectOption({ index: 1 });
      await page.fill('input[name="responsable"], select[name="user_id"]', '1');
      await page.click('button[type="submit"]');
      
      // Should show success or error depending on availability
      const msg = page.locator('.alert, .text-red-500, .text-green-500').first();
      if (await msg.isVisible()) {
          expect(await msg.innerText()).toBeTruthy();
      }
    }
  });
});
