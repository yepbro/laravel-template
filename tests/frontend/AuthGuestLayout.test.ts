import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import AuthGuestLayout from '@/layouts/AuthGuestLayout.vue';
import { createSharedI18n } from '@/shared/i18n';

vi.mock('@unhead/vue', () => ({
    useHead: vi.fn(),
}));

describe('AuthGuestLayout', () => {
    it('renders the application heading in the header', () => {
        const wrapper = shallowMount(AuthGuestLayout, {
            global: {
                plugins: [createSharedI18n()],
            },
        });

        expect(wrapper.text()).toContain(
            'Frontend starter for a standalone Vue app',
        );
    });
});
