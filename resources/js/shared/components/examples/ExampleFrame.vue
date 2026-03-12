<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useDemoStore } from '@/shared/stores/demo';

interface Props {
    title: string;
    description: string;
    modeLabel: string;
}

const props = defineProps<Props>();

const route = useRoute();
const demoStore = useDemoStore();
const { lastSubmittedMode, submissionCount, toastCount, totalLeads } =
    storeToRefs(demoStore);

const routeLabel = computed(() => route.name ?? 'ready');
</script>

<template>
    <Card class="h-full">
        <CardHeader class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge>{{ props.modeLabel }}</Badge>
                <Badge variant="outline">router: {{ routeLabel }}</Badge>
                <Badge variant="secondary">pinia: {{ totalLeads }} leads</Badge>
            </div>

            <div class="space-y-1">
                <CardTitle>{{ props.title }}</CardTitle>
                <CardDescription>{{ props.description }}</CardDescription>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <slot />

            <div
                class="grid gap-3 rounded-lg border border-border bg-muted/30 p-4 text-xs text-muted-foreground sm:grid-cols-3"
            >
                <div class="space-y-1">
                    <p class="font-medium text-foreground">Submissions</p>
                    <p>{{ submissionCount }}</p>
                </div>

                <div class="space-y-1">
                    <p class="font-medium text-foreground">Toasts</p>
                    <p>{{ toastCount }}</p>
                </div>

                <div class="space-y-1">
                    <p class="font-medium text-foreground">Last mode</p>
                    <p>{{ lastSubmittedMode ?? 'No form submissions yet' }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
