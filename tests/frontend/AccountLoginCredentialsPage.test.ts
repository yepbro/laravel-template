import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import AccountLoginCredentialsPage from '@/account/pages/AccountLoginCredentialsPage.vue';
import type { AuthFeatureSnapshot } from '@/auth/api/client';
import * as client from '@/auth/api/client';

vi.mock('@/auth/api/client', () => ({
    fetchAuthFeatures: vi.fn(),
    fetchCurrentUser: vi.fn(),
    requestLoginCredentialEmailChange: vi.fn(),
    requestLoginCredentialPhoneChange: vi.fn(),
    clearCurrentUserCache: vi.fn(),
    normalizeErrors: (errors: Record<string, string[]>) =>
        Object.fromEntries(
            Object.entries(errors).map(([k, v]) => [k, v[0] ?? '']),
        ),
}));

function makeRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/:pathMatch(.*)*', component: { template: '<div />' } },
        ],
    });
}

function makeAuthFeatures(
    overrides: Partial<AuthFeatureSnapshot> = {},
): AuthFeatureSnapshot {
    return {
        registration_mode: 'both',
        allows_email_registration: true,
        allows_phone_registration: true,
        email_verification_enabled: true,
        phone_verification_enabled: true,
        ...overrides,
    };
}

beforeEach(() => {
    vi.resetAllMocks();
});

describe('AccountLoginCredentialsPage', () => {
    it('shows email and phone forms when both credential changes are allowed', async () => {
        vi.mocked(client.fetchAuthFeatures).mockResolvedValue(
            makeAuthFeatures(),
        );

        const wrapper = mount(AccountLoginCredentialsPage, {
            global: { plugins: [makeRouter()] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Request email change');
        expect(wrapper.text()).toContain('Request phone change');
    });

    it('hides forms when no credential change is allowed', async () => {
        vi.mocked(client.fetchAuthFeatures).mockResolvedValue(
            makeAuthFeatures({
                allows_email_registration: false,
                allows_phone_registration: false,
            }),
        );

        const wrapper = mount(AccountLoginCredentialsPage, {
            global: { plugins: [makeRouter()] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain(
            'Login identifier changes are not available for this account configuration.',
        );
        expect(wrapper.text()).not.toContain('Request email change');
    });
});
