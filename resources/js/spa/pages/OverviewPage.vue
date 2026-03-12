<script setup lang="ts">
import FormExample from '@/shared/components/examples/FormExample.vue';
import TableExample from '@/shared/components/examples/TableExample.vue';
import ToastExample from '@/shared/components/examples/ToastExample.vue';

const modeLabel = 'Vue-only SPA';
</script>

<template>
    <section class="grid gap-6 xl:grid-cols-3">
        <FormExample :mode-label="modeLabel" />
        <TableExample :mode-label="modeLabel" />
        <ToastExample :mode-label="modeLabel" />
    </section>
</template>
