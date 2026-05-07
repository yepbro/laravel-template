<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="w-full max-w-sm">
        <Card>
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">{{ title }}</CardTitle>
                <CardDescription v-if="description">
                    {{ description }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <slot />
            </CardContent>
        </Card>
    </div>
</template>
