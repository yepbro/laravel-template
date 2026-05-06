import { afterEach, describe, expect, it, vi } from 'vitest';

describe('startMocking', () => {
    afterEach(() => {
        vi.unstubAllEnvs();
        vi.resetModules();
    });

    it('resolves immediately when MSW is disabled', async () => {
        vi.stubEnv('VITE_ENABLE_MSW', '');

        const { startMocking } = await import('@/shared/mocks/browser');

        await expect(startMocking()).resolves.toBeUndefined();
    });
});
