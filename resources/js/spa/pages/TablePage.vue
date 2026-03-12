<script setup lang="ts">
import TableExample from '@/shared/components/examples/TableExample.vue';

const modeLabel = 'Vue-only SPA';
</script>

<template>
    <section class="grid gap-6">
        <TableExample :mode-label="modeLabel" />
    </section>
</template>
