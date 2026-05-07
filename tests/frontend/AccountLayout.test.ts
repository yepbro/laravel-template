import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import AccountLayout from '@/layouts/AccountLayout.vue';
import { createSharedI18n } from '@/shared/i18n';

const logoutMock = vi.fn().mockResolvedValue(undefined);

vi.mock('@unhead/vue', () => ({
    useHead: vi.fn(),
}));

vi.mock('@/auth/api/client', () => ({
    logout: (): Promise<void> => logoutMock(),
}));

describe('AccountLayout', () => {
    it('logs out and navigates to the SPA login route', async () => {
        logoutMock.mockClear();

        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/', component: { template: '<div />' } }],
        });

        const pushSpy = vi.spyOn(router, 'push').mockResolvedValue(undefined);

        const wrapper = shallowMount(AccountLayout, {
            global: {
                plugins: [router, createSharedI18n()],
                stubs: {
                    RouterLink: {
                        template: '<a><slot /></a>',
                    },
                    DropdownMenu: {
                        template: '<div><slot /></div>',
                    },
                    DropdownMenuTrigger: {
                        template: '<div><slot /></div>',
                    },
                    DropdownMenuContent: {
                        template: '<div><slot /></div>',
                    },
                    DropdownMenuLabel: {
                        template: '<div><slot /></div>',
                    },
                    DropdownMenuSeparator: {
                        template: '<hr />',
                    },
                    DropdownMenuItem: {
                        template:
                            '<button type="button" v-bind="$attrs"><slot /></button>',
                    },
                    Button: {
                        template: '<button type="button"><slot /></button>',
                    },
                    Avatar: {
                        template: '<div><slot /></div>',
                    },
                    AvatarFallback: {
                        template: '<span><slot /></span>',
                    },
                    LogOut: {
                        template: '<svg />',
                    },
                },
            },
        });

        const logoutItem = wrapper.find('[data-testid="account-logout"]');

        expect(logoutItem.exists()).toBe(true);

        await logoutItem.trigger('click');

        expect(logoutMock).toHaveBeenCalledTimes(1);
        expect(pushSpy).toHaveBeenCalledWith('/login');
    });
});
