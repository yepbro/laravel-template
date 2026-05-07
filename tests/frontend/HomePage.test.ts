import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import LandingLayout from '@/layouts/LandingLayout.vue';
import { createSharedI18n } from '@/shared/i18n';
import HomePage from '@/spa/pages/HomePage.vue';

const { fetchCurrentUserMock, logoutMock } = vi.hoisted(() => ({
    fetchCurrentUserMock: vi.fn(),
    logoutMock: vi.fn().mockResolvedValue(undefined),
}));

vi.mock('@unhead/vue', () => ({
    useHead: vi.fn(),
}));

vi.mock('@/auth/api/client', async (importActual) => {
    const actual = await importActual<typeof import('@/auth/api/client')>();

    return {
        ...actual,
        fetchCurrentUser: fetchCurrentUserMock,
        logout: logoutMock,
    };
});

function createHomeRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            {
                path: '/',
                name: 'home',
                component: HomePage,
            },
            {
                path: '/login',
                name: 'auth.login',
                component: { template: '<div />' },
            },
            {
                path: '/register',
                name: 'auth.register',
                component: { template: '<div />' },
            },
        ],
    });
}

describe('HomePage', () => {
    beforeEach(() => {
        fetchCurrentUserMock.mockReset();
        logoutMock.mockClear();
        logoutMock.mockResolvedValue(undefined);
    });

    it('shows login and register links when the session is a guest', async () => {
        fetchCurrentUserMock.mockRejectedValue(new Error('unauthenticated'));

        const router = createHomeRouter();
        await router.push('/');

        const wrapper = mount(LandingLayout, {
            slots: {
                default: HomePage,
            },
            global: {
                plugins: [router, createSharedI18n()],
            },
        });

        await flushPromises();

        expect(wrapper.find('[data-testid="home-login-link"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.find('[data-testid="home-register-link"]').exists(),
        ).toBe(true);
        expect(
            wrapper.find('[data-testid="header-logout-button"]').exists(),
        ).toBe(false);
    });

    it('shows logout when the session is authenticated', async () => {
        fetchCurrentUserMock.mockResolvedValue({
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            phone: null,
            email_verified_at: null,
            phone_verified_at: null,
            allows_email_login_credential_change: true,
            allows_phone_login_credential_change: false,
        });

        const router = createHomeRouter();
        await router.push('/');

        const wrapper = mount(LandingLayout, {
            slots: {
                default: HomePage,
            },
            global: {
                plugins: [router, createSharedI18n()],
            },
        });

        await flushPromises();

        expect(
            wrapper.find('[data-testid="header-logout-button"]').exists(),
        ).toBe(true);
        expect(wrapper.find('[data-testid="home-login-link"]').exists()).toBe(
            false,
        );
        expect(wrapper.text()).toContain('Test User');
    });

    it('logs out and shows guest links', async () => {
        fetchCurrentUserMock.mockResolvedValue({
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            phone: null,
            email_verified_at: null,
            phone_verified_at: null,
            allows_email_login_credential_change: true,
            allows_phone_login_credential_change: false,
        });

        const router = createHomeRouter();
        const pushSpy = vi.spyOn(router, 'push').mockResolvedValue(undefined);
        await router.push('/');

        const wrapper = mount(LandingLayout, {
            slots: {
                default: HomePage,
            },
            global: {
                plugins: [router, createSharedI18n()],
            },
        });

        await flushPromises();

        await wrapper
            .find('[data-testid="header-logout-button"]')
            .trigger('click');
        await flushPromises();

        expect(logoutMock).toHaveBeenCalledTimes(1);
        expect(pushSpy).toHaveBeenCalledWith('/');

        expect(wrapper.find('[data-testid="home-login-link"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.find('[data-testid="home-register-link"]').exists(),
        ).toBe(true);
    });
});
