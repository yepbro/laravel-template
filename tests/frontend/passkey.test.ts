import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { createMemoryHistory, createRouter } from 'vue-router';

import * as client from '@/auth/api/client';
import ConfirmPasswordPage from '@/auth/pages/ConfirmPasswordPage.vue';
import LoginPage from '@/auth/pages/LoginPage.vue';
import SecuritySettingsPage from '@/auth/pages/SecuritySettingsPage.vue';

// ---------------------------------------------------------------------------
// Module mocks
// vi.hoisted() is required so variables are available inside vi.mock() factories
// after hoisting.
// ---------------------------------------------------------------------------

const { mockVerify, usePasskeyVerifyMock, mockRegister, mockIsSupported } =
    vi.hoisted(() => ({
        mockVerify: vi.fn(),
        usePasskeyVerifyMock: vi.fn(),
        mockRegister: vi.fn(),
        mockIsSupported: vi.fn(),
    }));

vi.mock('@laravel/passkeys/vue', () => ({
    usePasskeyVerify: usePasskeyVerifyMock,
}));

vi.mock('@laravel/passkeys', () => ({
    Passkeys: {
        register: mockRegister,
        isSupported: mockIsSupported,
    },
}));

vi.mock('@/auth/api/client', () => ({
    login: vi.fn(),
    confirmPassword: vi.fn(),
    securityStatus: vi.fn(),
    passkeyList: vi.fn(),
    passkeyDestroy: vi.fn(),
    twoFactorEnable: vi.fn(),
    twoFactorDisable: vi.fn(),
    twoFactorConfirm: vi.fn(),
    twoFactorQrCode: vi.fn(),
    twoFactorSecretKey: vi.fn(),
    twoFactorRecoveryCodes: vi.fn(),
    regenerateTwoFactorRecoveryCodes: vi.fn(),
    updatePassword: vi.fn(),
    updateProfileInformation: vi.fn(),
    normalizeErrors: (errors: Record<string, string[]>) =>
        Object.fromEntries(
            Object.entries(errors).map(([k, v]) => [k, v[0] ?? '']),
        ),
    ENDPOINTS: {
        passkeyLoginOptions: '/passkeys/login/options',
        passkeyLogin: '/passkeys/login',
        passkeyConfirmOptions: '/passkeys/confirm/options',
        passkeyConfirm: '/passkeys/confirm',
        passkeyRegisterOptions: '/user/passkeys/options',
        passkeyRegister: '/user/passkeys',
        passkeyDestroy: '/user/passkeys',
        passkeyList: '/user/passkeys',
    },
    PASSKEY_LOGIN_ROUTES: {
        options: '/passkeys/login/options',
        submit: '/passkeys/login',
    },
    PASSKEY_CONFIRM_ROUTES: {
        options: '/passkeys/confirm/options',
        submit: '/passkeys/confirm',
    },
    PASSKEY_REGISTER_ROUTES: {
        options: '/user/passkeys/options',
        submit: '/user/passkeys',
    },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeTestRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/:pathMatch(.*)*', component: { template: '<div />' } },
        ],
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

function mountLoginPage() {
    return mount(LoginPage, {
        global: { plugins: [makeTestRouter()] },
    });
}

function mountConfirmPasswordPage() {
    return mount(ConfirmPasswordPage, {
        global: { plugins: [makeTestRouter()] },
    });
}

function mountSecurityPage() {
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

// ---------------------------------------------------------------------------
// beforeEach: reset and re-apply mock defaults
// ---------------------------------------------------------------------------

beforeEach(() => {
    vi.resetAllMocks();

    usePasskeyVerifyMock.mockReturnValue({
        verify: mockVerify,
        isLoading: ref(false),
        error: ref(null),
        errorInstance: ref(null),
        isSupported: ref(true),
    });

    mockIsSupported.mockReturnValue(true);
    mockRegister.mockResolvedValue({ id: 'pk-1', name: 'My Key' });

    vi.mocked(client.securityStatus).mockResolvedValue(makeConfirmedStatus());
    vi.mocked(client.passkeyList).mockResolvedValue([]);
});

// ---------------------------------------------------------------------------
// LoginPage passkey tests
// ---------------------------------------------------------------------------

describe('LoginPage passkey', () => {
    it('renders the passkey sign-in button when supported', () => {
        const wrapper = mountLoginPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) => b.text().includes('passkey'));
        expect(passkeyBtn).toBeDefined();
    });

    it('does not render the passkey button when isSupported is false', () => {
        usePasskeyVerifyMock.mockReturnValue({
            verify: mockVerify,
            isLoading: ref(false),
            error: ref(null),
            errorInstance: ref(null),
            isSupported: ref(false),
        });

        const wrapper = mountLoginPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) => b.text().includes('passkey'));
        expect(passkeyBtn).toBeUndefined();
    });

    it('calls verify() when the passkey sign-in button is clicked', async () => {
        const wrapper = mountLoginPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) => b.text().includes('passkey'));
        expect(passkeyBtn).toBeDefined();
        await passkeyBtn!.trigger('click');
        expect(mockVerify).toHaveBeenCalledOnce();
    });

    it('passes PASSKEY_LOGIN_ROUTES to usePasskeyVerify', () => {
        mountLoginPage();
        expect(usePasskeyVerifyMock).toHaveBeenCalledWith(
            expect.objectContaining({
                routes: {
                    options: '/passkeys/login/options',
                    submit: '/passkeys/login',
                },
            }),
        );
    });

    it('shows an error alert when passkey error is set', () => {
        usePasskeyVerifyMock.mockReturnValue({
            verify: mockVerify,
            isLoading: ref(false),
            error: ref('Passkey not recognized.'),
            errorInstance: ref(null),
            isSupported: ref(true),
        });

        const wrapper = mountLoginPage();
        expect(wrapper.text()).toContain('Passkey not recognized.');
    });
});

// ---------------------------------------------------------------------------
// ConfirmPasswordPage passkey tests
// ---------------------------------------------------------------------------

describe('ConfirmPasswordPage passkey', () => {
    it('renders the passkey confirmation button when supported', () => {
        const wrapper = mountConfirmPasswordPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) =>
            b.text().toLowerCase().includes('passkey'),
        );
        expect(passkeyBtn).toBeDefined();
    });

    it('does not render the passkey button when isSupported is false', () => {
        usePasskeyVerifyMock.mockReturnValue({
            verify: mockVerify,
            isLoading: ref(false),
            error: ref(null),
            errorInstance: ref(null),
            isSupported: ref(false),
        });

        const wrapper = mountConfirmPasswordPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) =>
            b.text().toLowerCase().includes('passkey'),
        );
        expect(passkeyBtn).toBeUndefined();
    });

    it('calls verify() when the passkey button is clicked', async () => {
        const wrapper = mountConfirmPasswordPage();
        const buttons = wrapper.findAll('button');
        const passkeyBtn = buttons.find((b) =>
            b.text().toLowerCase().includes('passkey'),
        );
        expect(passkeyBtn).toBeDefined();
        await passkeyBtn!.trigger('click');
        expect(mockVerify).toHaveBeenCalledOnce();
    });

    it('passes PASSKEY_CONFIRM_ROUTES to usePasskeyVerify', () => {
        mountConfirmPasswordPage();
        expect(usePasskeyVerifyMock).toHaveBeenCalledWith(
            expect.objectContaining({
                routes: {
                    options: '/passkeys/confirm/options',
                    submit: '/passkeys/confirm',
                },
            }),
        );
    });
});

// ---------------------------------------------------------------------------
// SecuritySettingsPage passkey tests
// ---------------------------------------------------------------------------

describe('SecuritySettingsPage passkey section', () => {
    it('shows the passkey card with Add passkey button when confirmed and supported', async () => {
        const wrapper = mountSecurityPage();
        await flushPromises();

        const buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        expect(addBtn).toBeDefined();
    });

    it('shows "not supported" message when Passkeys.isSupported() returns false', async () => {
        mockIsSupported.mockReturnValue(false);

        const wrapper = mountSecurityPage();
        await flushPromises();

        expect(wrapper.text()).toContain('not supported');
    });

    it('gates passkey management behind password confirmation', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus({ password_confirmed: false }),
        );

        const wrapper = mountSecurityPage();
        await flushPromises();

        // Should show confirm-password prompt instead of Add passkey button
        const buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        expect(addBtn).toBeUndefined();
    });

    it('calls Passkeys.register() with PASSKEY_REGISTER_ROUTES when Add passkey is clicked', async () => {
        const wrapper = mountSecurityPage();
        await flushPromises();

        // Type a passkey name
        const input = wrapper.find('input[placeholder*="Passkey name"]');
        expect(input.exists()).toBe(true);
        await input.setValue('My Laptop');

        const buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        expect(addBtn).toBeDefined();
        await addBtn!.trigger('click');
        await flushPromises();

        expect(mockRegister).toHaveBeenCalledWith(
            expect.objectContaining({
                name: 'My Laptop',
                routes: {
                    options: '/user/passkeys/options',
                    submit: '/user/passkeys',
                },
            }),
        );
    });

    it('adds the registered passkey to the list after successful registration', async () => {
        const registeredKey = {
            id: 'pk-99',
            name: 'My Laptop',
            authenticator: null,
            last_used_at: null,
            created_at: null,
        };
        // First call (on mount) returns empty; second call (after register) returns new key.
        vi.mocked(client.passkeyList)
            .mockResolvedValueOnce([])
            .mockResolvedValueOnce([registeredKey]);

        const wrapper = mountSecurityPage();
        await flushPromises();

        const input = wrapper.find('input[placeholder*="Passkey name"]');
        await input.setValue('My Laptop');

        const buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        await addBtn!.trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('My Laptop');
    });

    it('shows a Remove button for each registered passkey', async () => {
        const registeredKey = {
            id: 'pk-99',
            name: 'My Laptop',
            authenticator: null,
            last_used_at: null,
            created_at: null,
        };
        vi.mocked(client.passkeyList)
            .mockResolvedValueOnce([])
            .mockResolvedValueOnce([registeredKey]);

        const wrapper = mountSecurityPage();
        await flushPromises();

        const input = wrapper.find('input[placeholder*="Passkey name"]');
        await input.setValue('My Laptop');

        const buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        await addBtn!.trigger('click');
        await flushPromises();

        const allButtons = wrapper.findAll('button');
        const removeBtn = allButtons.find((b) => b.text().includes('Remove'));
        expect(removeBtn).toBeDefined();
    });

    it('calls passkeyDestroy when the Remove button is clicked', async () => {
        const registeredKey = {
            id: 'pk-99',
            name: 'My Laptop',
            authenticator: null,
            last_used_at: null,
            created_at: null,
        };
        vi.mocked(client.passkeyList)
            .mockResolvedValueOnce([])
            .mockResolvedValueOnce([registeredKey])
            .mockResolvedValue([]);
        vi.mocked(client.passkeyDestroy).mockResolvedValue(undefined);

        const wrapper = mountSecurityPage();
        await flushPromises();

        const input = wrapper.find('input[placeholder*="Passkey name"]');
        await input.setValue('My Laptop');

        let buttons = wrapper.findAll('button');
        const addBtn = buttons.find((b) => b.text().includes('Add passkey'));
        await addBtn!.trigger('click');
        await flushPromises();

        buttons = wrapper.findAll('button');
        const removeBtn = buttons.find((b) => b.text().includes('Remove'));
        expect(removeBtn).toBeDefined();
        await removeBtn!.trigger('click');
        await flushPromises();

        expect(client.passkeyDestroy).toHaveBeenCalledWith('pk-99');
    });

    it('renders passkeys returned by passkeyList on mount', async () => {
        vi.mocked(client.passkeyList).mockResolvedValue([
            {
                id: 'server-pk-1',
                name: 'Work MacBook',
                authenticator: null,
                last_used_at: null,
                created_at: null,
            },
            {
                id: 'server-pk-2',
                name: 'iPhone 15',
                authenticator: 'Apple',
                last_used_at: null,
                created_at: null,
            },
        ]);

        const wrapper = mountSecurityPage();
        await flushPromises();

        expect(wrapper.text()).toContain('Work MacBook');
        expect(wrapper.text()).toContain('iPhone 15');
    });

    it('calls passkeyDestroy with the server-loaded passkey id when Remove is clicked', async () => {
        vi.mocked(client.passkeyList).mockResolvedValue([
            {
                id: 'server-pk-1',
                name: 'Work MacBook',
                authenticator: null,
                last_used_at: null,
                created_at: null,
            },
        ]);
        vi.mocked(client.passkeyDestroy).mockResolvedValue(undefined);

        const wrapper = mountSecurityPage();
        await flushPromises();

        const buttons = wrapper.findAll('button');
        const removeBtn = buttons.find((b) => b.text().includes('Remove'));
        expect(removeBtn).toBeDefined();
        await removeBtn!.trigger('click');
        await flushPromises();

        expect(client.passkeyDestroy).toHaveBeenCalledWith('server-pk-1');
    });

    it('flips to confirm-password callout when passkeyList returns 423', async () => {
        vi.mocked(client.securityStatus).mockResolvedValue(
            makeConfirmedStatus(),
        );

        const err = new Error('Forbidden') as import('axios').AxiosError;
        Object.defineProperty(err, 'response', {
            value: {
                status: 423,
                data: { message: 'Password confirmation required.' },
                statusText: 'Locked',
                headers: {},
                config: { headers: {} },
            },
        });
        Object.defineProperty(err, 'isAxiosError', { value: true });
        vi.mocked(client.passkeyList).mockRejectedValue(err);

        const wrapper = mountSecurityPage();
        await flushPromises();

        expect(wrapper.find('a[href="/user/confirm-password"]').exists()).toBe(
            true,
        );
    });
});
