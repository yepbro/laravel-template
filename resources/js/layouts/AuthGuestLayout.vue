<script setup lang="ts">
import { useHead } from '@unhead/vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

useHead({
    title: () => `${t('app.heading')} · Account`,
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="border-b border-border bg-background/95 px-4 py-3 backdrop-blur supports-backdrop-filter:bg-background/80"
        >
            <p
                class="text-center text-sm font-semibold tracking-tight text-foreground sm:text-left"
            >
                {{ t('app.heading') }}
            </p>
        </header>
        <main class="flex flex-1 flex-col items-center justify-center p-4">
            <slot />
        </main>
    </div>
</template>
