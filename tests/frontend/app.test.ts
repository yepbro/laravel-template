import { shallowMount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import App from '@/App.vue';

function layoutStubTestId(layout: string): string {
    return `layout-${layout}`;
}

function stubsForLayoutAssertions() {
    return {
        AuthGuestLayout: {
            template: `<div data-test="${layoutStubTestId('guest')}"><slot /></slot></div>`,
        },
        AccountLayout: {
            template: `<div data-test="${layoutStubTestId('account')}"><slot /></slot></div>`,
        },
        DemoLayout: {
            template: `<div data-test="${layoutStubTestId('demo')}"><slot /></slot></div>`,
        },
        RouterView: {
            template: '<div data-test="router-view" />',
        },
    };
}

describe('App', () => {
    it('wraps the router view in a layout shell', () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/:pathMatch(.*)*',
                    component: { template: '<div />' },
                },
            ],
        });

        const wrapper = shallowMount(App, {
            global: {
                plugins: [router],
                stubs: stubsForLayoutAssertions(),
            },
        });

        expect(
            wrapper.find(`[data-test="${layoutStubTestId('demo')}"]`).exists(),
        ).toBe(true);
        expect(wrapper.find('[data-test="router-view"]').exists()).toBe(true);
    });

    it('selects the guest layout from route meta', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/guest',
                    component: { template: '<div />' },
                    meta: { layout: 'guest' },
                },
            ],
        });

        const wrapper = shallowMount(App, {
            global: {
                plugins: [router],
                stubs: stubsForLayoutAssertions(),
            },
        });

        await router.push('/guest');
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find(`[data-test="${layoutStubTestId('guest')}"]`).exists(),
        ).toBe(true);
    });

    it('selects the account layout from route meta', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/account',
                    component: { template: '<div />' },
                    meta: { layout: 'account' },
                },
            ],
        });

        const wrapper = shallowMount(App, {
            global: {
                plugins: [router],
                stubs: stubsForLayoutAssertions(),
            },
        });

        await router.push('/account');
        await wrapper.vm.$nextTick();

        expect(
            wrapper
                .find(`[data-test="${layoutStubTestId('account')}"]`)
                .exists(),
        ).toBe(true);
    });

    it('selects the demo layout when meta.layout is demo', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/demo',
                    component: { template: '<div />' },
                    meta: { layout: 'demo' },
                },
            ],
        });

        const wrapper = shallowMount(App, {
            global: {
                plugins: [router],
                stubs: stubsForLayoutAssertions(),
            },
        });

        await router.push('/demo');
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find(`[data-test="${layoutStubTestId('demo')}"]`).exists(),
        ).toBe(true);
    });

    it('falls back to the demo layout for unknown meta.layout values', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/unknown',
                    component: { template: '<div />' },
                    meta: { layout: 'not-a-real-layout' },
                },
            ],
        });

        const wrapper = shallowMount(App, {
            global: {
                plugins: [router],
                stubs: stubsForLayoutAssertions(),
            },
        });

        await router.push('/unknown');
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find(`[data-test="${layoutStubTestId('demo')}"]`).exists(),
        ).toBe(true);
    });
});
