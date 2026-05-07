import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import * as client from '@/auth/api/client';
import SecuritySettingsPage from '@/auth/pages/SecuritySettingsPage.vue';

// Replace the entire auth client module so the page's import bindings point to
// vi.fn() instances. normalizeErrors is kept as a real function because it is
// not a vi.fn() (plain object property) and therefore survives vi.resetAllMocks().
vi.mock('@/auth/api/client', () => ({
    securityStatus: vi.fn(),
    passkeyList: vi.fn(),
    twoFactorEnable: vi.fn(),
    twoFactorDisable: vi.fn(),
    twoFactorConfirm: vi.fn(),
    twoFactorQrCode: vi.fn(),
    twoFactorSecretKey: vi.fn(),
    twoFactorRecoveryCodes: vi.fn(),
    regenerateTwoFactorRecoveryCodes: vi.fn(),
    updatePassword: vi.fn(),
    updateProfileInformation: vi.fn(),
    mapTwoFactorChallengeErrors: vi.fn((e: Record<string, string>) => e),
    normalizeErrors: (errors: Record<string, string[]>) =>
        Object.fromEntries(
            Object.entries(errors).map(([k, v]) => [k, v[0] ?? '']),
        ),
}));

function makeTestRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/:pathMatch(.*)*', component: { template: '<div />' } },
        ],
    });
}

// Mount with real components so slot content and text are actually rendered.
// Only the InputOTP family is stubbed because it may reference browser
// clipboard/selection APIs not present in jsdom.
function mountPage() {
    return mount(SecuritySettingsPage, {
        global: {
            plugins: [makeTestRouter()],
            stubs: {
                InputOTP: { template: '<div class="input-otp" />' },
                InputOTPGroup: { template: '<div />' },
                InputOTPSlot: { template: '<span />' },
            },
        },
    });
}

function makeConfirmedStatus(
    overrides: Partial<client.SecurityStatusResponse> = {},
): client.SecurityStatusResponse {
    return {
        password_confirmed: true,
        two_factor_enabled: false,
        two_factor_confirmed: false,
        ...overrides,
    };
}

function make423Error(): axios.AxiosError {
    const err = new axios.AxiosError('Password confirmation required.');
    err.response = {
        status: 423,
        data: { message: 'Password confirmation required.' },
        statusText: 'Payment Required',
        headers: {},
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        config: { headers: {} } as any,
    };
    return err;
}

beforeEach(() => {
    vi.resetAllMocks();
    vi.mocked(client.passkeyList).mockResolvedValue([]);
});

// ------------------------------------------------------------------
// Loading state
// ------------------------------------------------------------------

describe('SecuritySettingsPage loading state', () => {
    it('shows loading text while the security status request is pending', () => {
        vi.mocked(client.securityStatus).mockReturnValue(new Promise(() => {}));

        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Checking session...');
    });

    it('does not show the confirm-password link while loading', () => {
        vi.mocked(client.securityStatus).mockReturnValue(new Promise(() => {}));

        const wrapper = mountPage();

        expect(wrapper.find('a[href="/user/confirm-password"]').exists()).toBe(
            false,
        );
    });
});

// ------------------------------------------------------------------
// Unconfirmed password
// ------------------------------------------------------------------

describe('SecuritySettingsPage unconfirmed password', () => {
    it('shows the confirm-password link when password_confirmed is false', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({ password_confirmed: false }),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.find('a[href="/user/confirm-password"]').exists()).toBe(
            true,
        );
    });

    it('does not show the Enable 2FA button when password is not confirmed', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({ password_confirmed: false }),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.text()).not.toContain('Enable 2FA');
    });
});

// ------------------------------------------------------------------
// Confirmed + 2FA not enabled
// ------------------------------------------------------------------

describe('SecuritySettingsPage confirmed, 2FA not enabled', () => {
    it('shows the Enable 2FA button when confirmed and 2FA is not enabled', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus(),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.text()).toContain('Enable 2FA');
    });

    it('does not show the active 2FA label when 2FA is not enabled', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus(),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.find('strong').exists()).toBe(false);
    });
});

// ------------------------------------------------------------------
// Confirmed + 2FA enabled
// ------------------------------------------------------------------

describe('SecuritySettingsPage confirmed, 2FA enabled', () => {
    it('shows active 2FA label when two_factor_enabled is true', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({
                two_factor_enabled: true,
                two_factor_confirmed: true,
            }),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.find('strong').text()).toBe('active');
    });

    it('shows the Disable 2FA button when 2FA is enabled', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({
                two_factor_enabled: true,
                two_factor_confirmed: true,
            }),
        );

        const wrapper = mountPage();
        await flushPromises();

        const allButtons = wrapper.findAll('button');
        const disableBtn = allButtons.find((b) => b.text() === 'Disable 2FA');
        expect(disableBtn).toBeDefined();
    });

    it('does not show the Enable 2FA button when 2FA is already enabled', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({
                two_factor_enabled: true,
                two_factor_confirmed: true,
            }),
        );

        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.text()).not.toContain('Enable 2FA');
    });
});

// ------------------------------------------------------------------
// 423 handling
// ------------------------------------------------------------------

describe('SecuritySettingsPage 423 handling', () => {
    it('shows confirm-password link after a sensitive action returns 423', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({
                two_factor_enabled: true,
                two_factor_confirmed: true,
            }),
        );
        vi.mocked(client.twoFactorDisable).mockRejectedValue(make423Error());

        const wrapper = mountPage();
        await flushPromises();

        // Find and click "Disable 2FA".
        const allButtons = wrapper.findAll('button');
        const disableBtn = allButtons.find((b) => b.text() === 'Disable 2FA');
        expect(disableBtn).toBeDefined();
        await disableBtn!.trigger('click');
        await flushPromises();

        expect(wrapper.find('a[href="/user/confirm-password"]').exists()).toBe(
            true,
        );
    });
});
