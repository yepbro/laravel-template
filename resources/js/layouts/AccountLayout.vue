<script setup lang="ts">
import { useHead } from '@unhead/vue';
import { RouterLink } from 'vue-router';

import AppHeaderUserToolbar from '@/layouts/AppHeaderUserToolbar.vue';

useHead({
    title: () => 'Account',
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="flex items-center justify-between gap-4 border-b border-border bg-card px-4 py-3 shadow-sm sm:px-6"
        >
            <RouterLink
                class="text-sm font-semibold tracking-tight text-foreground hover:underline"
                to="/account"
            >
                {{ $t('app.heading') }}
            </RouterLink>

            <AppHeaderUserToolbar :redirect-after-logout="'/login'" />
        </header>

        <div class="flex-1">
            <slot />
        </div>
    </div>
</template>
