<script setup lang="ts">
import ToastExample from '@/shared/components/examples/ToastExample.vue';

const modeLabel = 'Vue-only SPA';
</script>

<template>
    <section class="grid gap-6">
        <ToastExample :mode-label="modeLabel" />
    </section>
</template>
