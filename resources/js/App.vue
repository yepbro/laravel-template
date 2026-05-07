<script setup lang="ts">
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';

import AccountLayout from '@/layouts/AccountLayout.vue';
import AuthGuestLayout from '@/layouts/AuthGuestLayout.vue';
import DemoLayout from '@/layouts/DemoLayout.vue';

const route = useRoute();

const layoutMap = {
    guest: AuthGuestLayout,
    account: AccountLayout,
    demo: DemoLayout,
} as const;

type LayoutKey = keyof typeof layoutMap;

const layoutComponent = computed(() => {
    const key = route.meta.layout as LayoutKey | undefined;
    if (key !== undefined && key in layoutMap) {
        return layoutMap[key];
    }

    return DemoLayout;
});
</script>

<template>
    <component :is="layoutComponent">
        <RouterView />
    </component>
</template>
