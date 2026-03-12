import { shallowMount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import App from '@/App.vue';

describe('App', () => {
    it('renders the SPA layout shell', () => {
        const wrapper = shallowMount(App, {
            global: {
                stubs: {
                    SpaLayout: {
                        template: '<div data-test="spa-layout" />',
                    },
                },
            },
        });

        expect(wrapper.find('[data-test="spa-layout"]').exists()).toBe(true);
    });
});
