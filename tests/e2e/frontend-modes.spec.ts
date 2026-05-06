import { expect, test } from '@playwright/test';

test('SPA shell loads', async ({ page }) => {
    await page.goto('/spa');

    await expect(page.locator('#app')).toBeVisible();
});
