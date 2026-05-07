import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

vi.mock('@/auth/api/client', async (importActual) => {
    const actual = await importActual<typeof import('@/auth/api/client')>();

    return {
        ...actual,
        fetchCurrentUser: vi.fn().mockResolvedValue({
            id: 1,
            name: 'Vitest User',
            email: 'vitest@example.com',
            phone: null,
            email_verified_at: null,
            phone_verified_at: null,
        }),
    };
});

import DemoLayout from '@/layouts/DemoLayout.vue';
import { createSharedI18n } from '@/shared/i18n';
import { router } from '@/spa/router';

// useHead accesses browser APIs that are unnecessary for these tests.
vi.mock('@unhead/vue', () => ({
    useHead: vi.fn(),
}));

// ------------------------------------------------------------------
// Router: canonical auth route registration
// ------------------------------------------------------------------

describe('spa router auth routes', () => {
    const paths = router.getRoutes().map((r) => r.path);

    it('registers canonical /login', () => {
        expect(paths).toContain('/login');
    });

    it('registers canonical /register', () => {
        expect(paths).toContain('/register');
    });

    it('registers canonical /forgot-password', () => {
        expect(paths).toContain('/forgot-password');
    });

    it('registers canonical /reset-password/:token?', () => {
        expect(paths).toContain('/reset-password/:token?');
    });

    it('registers canonical /email/verify', () => {
        expect(paths).toContain('/email/verify');
    });

    it('registers canonical /phone/verify', () => {
        expect(paths).toContain('/phone/verify');
    });

    // Legacy URLs remain as redirect stubs for bookmarks and older links.
    it('registers legacy /spa/auth/login redirect', () => {
        expect(paths).toContain('/spa/auth/login');
    });

    it('registers legacy /spa/auth/register redirect', () => {
        expect(paths).toContain('/spa/auth/register');
    });

    it('registers legacy /spa/auth/forgot-password redirect', () => {
        expect(paths).toContain('/spa/auth/forgot-password');
    });

    it('registers legacy /spa/auth/reset-password/:token? redirect', () => {
        expect(paths).toContain('/spa/auth/reset-password/:token?');
    });

    it('registers legacy /spa/auth/verify-email redirect', () => {
        expect(paths).toContain('/spa/auth/verify-email');
    });

    it('registers legacy /spa/auth/verify-phone redirect', () => {
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

    it('registers legacy /spa/auth/two-factor-challenge redirect', () => {
        expect(paths).toContain('/spa/auth/two-factor-challenge');
    });

    it('registers legacy /spa/auth/confirm-password redirect', () => {
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
    it('resolves /login to auth.login', async () => {
        await router.push('/login');
        expect(router.currentRoute.value.name).toBe('auth.login');
    });

    it('resolves legacy /spa/auth/login to auth.login', async () => {
        await router.push('/spa/auth/login');
        expect(router.currentRoute.value.name).toBe('auth.login');
        expect(router.currentRoute.value.path).toBe('/login');
    });

    it('resolves /register to auth.register', async () => {
        await router.push('/register');
        expect(router.currentRoute.value.name).toBe('auth.register');
    });

    it('resolves /reset-password/sometoken with token param', async () => {
        await router.push('/reset-password/sometoken');
        expect(router.currentRoute.value.name).toBe('auth.reset-password');
        expect(router.currentRoute.value.params.token).toBe('sometoken');
    });

    it('resolves legacy reset-password path with token param', async () => {
        await router.push('/spa/auth/reset-password/sometoken');
        expect(router.currentRoute.value.name).toBe('auth.reset-password');
        expect(router.currentRoute.value.params.token).toBe('sometoken');
    });

    it('preserves email query when resolving legacy reset-password', async () => {
        await router.push(
            '/spa/auth/reset-password/sometoken?email=user%40example.com',
        );
        expect(router.currentRoute.value.name).toBe('auth.reset-password');
        expect(router.currentRoute.value.params.token).toBe('sometoken');
        expect(router.currentRoute.value.query.email).toBe('user@example.com');
    });
});

describe('spa router account routes', () => {
    const paths = router.getRoutes().map((r) => r.path);

    it('registers /account dashboard', () => {
        expect(paths).toContain('/account');
    });

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

    it('resolves /account with account dashboard meta', async () => {
        await router.push('/account');
        expect(router.currentRoute.value.name).toBe('account.dashboard');
        expect(router.currentRoute.value.meta.layout).toBe('account');
        expect(router.currentRoute.value.meta.requiresAuth).toBe(true);
    });

    it('resolves /account/profile with account layout meta', async () => {
        await router.push('/account/profile');
        expect(router.currentRoute.value.name).toBe('account.profile');
        expect(router.currentRoute.value.meta.layout).toBe('account');
        expect(router.currentRoute.value.meta.requiresAuth).toBe(true);
    });

    it('uses guest layout for canonical auth login', async () => {
        await router.push('/login');
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
    it('renders a Login link pointing to /login', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/login');
    });

    it('renders a Register link pointing to /register', () => {
        const wrapper = mountDemoLayout();
        const linkTos = wrapper
            .findAll('router-link-stub')
            .map((el) => el.attributes('to'));
        expect(linkTos).toContain('/register');
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
