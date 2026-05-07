<script setup lang="ts">
import { useHead } from '@unhead/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const route = useRoute();
const { t } = useI18n();

const navigation = [
    {
        label: t('app.navigation.overview'),
        to: '/spa',
    },
    {
        label: t('app.navigation.form'),
        to: '/spa/form',
    },
    {
        label: t('app.navigation.table'),
        to: '/spa/table',
    },
    {
        label: t('app.navigation.toast'),
        to: '/spa/toast',
    },
    {
        label: 'Login',
        to: '/login',
    },
    {
        label: 'Register',
        to: '/register',
    },
    {
        label: 'Security',
        to: '/spa/auth/security',
    },
];

const currentPath = computed(() => route.path);

useHead({
    title: computed(() => `Laravel Template - ${String(route.name ?? 'spa')}`),
});
</script>

<template>
    <div class="space-y-8">
        <header class="rounded-2xl border border-border bg-card p-8 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <Badge>{{ t('app.modeLabel') }}</Badge>
                <Badge variant="outline">Vue Router + Pinia</Badge>
            </div>

            <div class="mt-4 space-y-3">
                <h1
                    class="text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ t('app.heading') }}
                </h1>

                <p class="max-w-3xl text-sm leading-6 text-muted-foreground">
                    {{ t('app.modeDescription') }}
                </p>
            </div>

            <nav class="mt-6 flex flex-wrap gap-2">
                <RouterLink
                    v-for="link in navigation"
                    :key="link.to"
                    :class="
                        cn(
                            'inline-flex rounded-md border border-border px-3 py-2 text-sm font-medium transition-colors',
                            currentPath === link.to
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-background text-foreground hover:bg-accent hover:text-accent-foreground',
                        )
                    "
                    :to="link.to"
                >
                    {{ link.label }}
                </RouterLink>
            </nav>
        </header>

        <slot />
    </div>
</template>
