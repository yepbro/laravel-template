<script setup lang="ts">
import FormExample from '@/shared/components/examples/FormExample.vue';

const modeLabel = 'Vue-only SPA';
</script>

<template>
    <section class="grid gap-6">
        <FormExample :mode-label="modeLabel" />
    </section>
</template>
