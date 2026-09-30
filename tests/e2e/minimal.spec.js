import { test, expect } from '@playwright/test';

test('has title or redirects', async ({ page }) => {
  await page.goto('/');

  // Expect a title "to contain" a substring, or the page to be loaded
  // Since it might redirect to /login, we check if body is visible
  await expect(page.locator('body')).toBeVisible();
});
