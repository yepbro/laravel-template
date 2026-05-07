import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import DemoLayout from '@/layouts/DemoLayout.vue';
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

describe('spa router account routes', () => {
    const paths = router.getRoutes().map((r) => r.path);

    it('registers /account/profile', () => {
        expect(paths).toContain('/account/profile');
    });

    it('registers /account/password', () => {
        expect(paths).toContain('/account/password');
    });

    it('registers /account/login-credentials', () => {
        expect(paths).toContain('/account/login-credentials');
    });

    it('registers /account/delete', () => {
        expect(paths).toContain('/account/delete');
    });

    it('resolves /account/profile with account layout meta', async () => {
        await router.push('/account/profile');
        expect(router.currentRoute.value.name).toBe('account.profile');
        expect(router.currentRoute.value.meta.layout).toBe('account');
        expect(router.currentRoute.value.meta.requiresAuth).toBe(true);
    });

    it('uses guest layout for auth login', async () => {
        await router.push('/spa/auth/login');
        expect(router.currentRoute.value.meta.layout).toBe('guest');
    });

    it('uses demo layout for spa overview', async () => {
        await router.push('/spa');
        expect(router.currentRoute.value.meta.layout).toBe('demo');
    });
});

// ------------------------------------------------------------------
// DemoLayout: navigation links
// ------------------------------------------------------------------

function mountDemoLayout() {
    const testRouter = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/:pathMatch(.*)*', component: { template: '<div />' } },
        ],
    });

    return shallowMount(DemoLayout, {
        global: {
            plugins: [testRouter, createSharedI18n()],
        },
    });
}

describe('DemoLayout navigation', () => {
    it('renders a Login link pointing to /spa/auth/login', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/login');
    });

    it('renders a Register link pointing to /spa/auth/register', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/register');
    });

    it('retains all original demo navigation links', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa');
        expect(linkTos).toContain('/spa/form');
        expect(linkTos).toContain('/spa/table');
        expect(linkTos).toContain('/spa/toast');
    });

    it('renders a Security link pointing to /spa/auth/security', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/spa/auth/security');
    });
});
