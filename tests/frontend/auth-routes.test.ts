import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import SpaLayout from '@/layouts/SpaLayout.vue';
import { createSharedI18n } from '@/shared/i18n';
import { router } from '@/spa/router';

// useHead accesses browser APIs that are unnecessary for these tests.
vi.mock('@unhead/vue', () => ({
    useHead: vi.fn(),
}));

// ------------------------------------------------------------------
// Router: auth route registration
// ------------------------------------------------------------------

describe('spa router auth routes', () => {
    const paths = router.getRoutes().map((r) => r.path);

    it('registers /spa/auth/login', () => {
        expect(paths).toContain('/spa/auth/login');
    });

    it('registers /spa/auth/register', () => {
        expect(paths).toContain('/spa/auth/register');
    });

    it('registers /spa/auth/forgot-password', () => {
        expect(paths).toContain('/spa/auth/forgot-password');
    });

    it('registers /spa/auth/reset-password/:token?', () => {
        expect(paths).toContain('/spa/auth/reset-password/:token?');
    });

    it('registers /spa/auth/verify-email', () => {
        expect(paths).toContain('/spa/auth/verify-email');
    });

    it('registers /spa/auth/verify-phone', () => {
        expect(paths).toContain('/spa/auth/verify-phone');
    });

    // Existing demo routes must not regress.
    it('retains /spa', () => {
        expect(paths).toContain('/spa');
    });

    it('retains /spa/form', () => {
        expect(paths).toContain('/spa/form');
    });

    it('retains /spa/table', () => {
        expect(paths).toContain('/spa/table');
    });

    it('retains /spa/toast', () => {
        expect(paths).toContain('/spa/toast');
    });

    it('registers /spa/auth/two-factor-challenge', () => {
        expect(paths).toContain('/spa/auth/two-factor-challenge');
    });

    it('registers /spa/auth/confirm-password', () => {
        expect(paths).toContain('/spa/auth/confirm-password');
    });

    it('registers /spa/auth/security', () => {
        expect(paths).toContain('/spa/auth/security');
    });
});

// ------------------------------------------------------------------
// Router: auth route resolution
// ------------------------------------------------------------------

describe('spa router auth route resolution', () => {
    it('resolves /spa/auth/login to auth.login', async () => {
        await router.push('/spa/auth/login');
        expect(router.currentRoute.value.name).toBe('auth.login');
    });

    it('resolves /spa/auth/register to auth.register', async () => {
        await router.push('/spa/auth/register');
        expect(router.currentRoute.value.name).toBe('auth.register');
    });

    it('resolves /spa/auth/reset-password/sometoken with token param', async () => {
        await router.push('/spa/auth/reset-password/sometoken');
        expect(router.currentRoute.value.name).toBe('auth.reset-password');
        expect(router.currentRoute.value.params.token).toBe('sometoken');
    });
});

// ------------------------------------------------------------------
// SpaLayout: navigation links
// ------------------------------------------------------------------

function mountSpaLayout() {
    const testRouter = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/:pathMatch(.*)*', component: { template: '<div />' } },
        ],
    });

    return shallowMount(SpaLayout, {
        global: {
            plugins: [testRouter, createSharedI18n()],
        },
    });
}

describe('SpaLayout navigation', () => {
    it('renders a Login link pointing to /spa/auth/login', () => {
        const wrapper = mountSpaLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/login');
    });

    it('renders a Register link pointing to /spa/auth/register', () => {
        const wrapper = mountSpaLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/register');
    });

    it('retains all original demo navigation links', () => {
        const wrapper = mountSpaLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa');
        expect(linkTos).toContain('/spa/form');
        expect(linkTos).toContain('/spa/table');
        expect(linkTos).toContain('/spa/toast');
    });

    it('renders a Security link pointing to /spa/auth/security', () => {
        const wrapper = mountSpaLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/security');
    });
});
