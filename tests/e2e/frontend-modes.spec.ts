import { expect, test } from '@playwright/test';

test('frontend mode switcher is visible in SPA', async ({ page }) => {
    await page.goto('/spa');

    await expect(
        page.getByRole('link', { name: 'Vue-only SPA' }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Blade + Vue islands' }),
    ).toBeVisible();
});

test('frontend mode switcher is visible in islands', async ({ page }) => {
    await page.goto('/islands');

    await expect(
        page.getByRole('link', { name: 'Vue-only SPA' }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Blade + Vue islands' }),
    ).toBeVisible();
});
