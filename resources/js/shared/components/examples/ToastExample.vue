<script setup lang="ts">
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import ExampleFrame from '@/shared/components/examples/ExampleFrame.vue';
import { useDemoStore } from '@/shared/stores/demo';

interface Props {
    modeLabel: string;
}

const props = defineProps<Props>();

const demoStore = useDemoStore();

function fireToast(kind: 'success' | 'info' | 'loading'): void {
    demoStore.trackToast();

    if (kind === 'success') {
        toast.success('Deployment checklist ready', {
            description: `${props.modeLabel} already has the toaster mounted in the base layout.`,
        });

        return;
    }

    if (kind === 'loading') {
        toast.loading('Installing starter dependencies...', {
            description: 'Use this for optimistic actions or background jobs.',
        });

        return;
    }

    toast('Feature branch created', {
        description: 'This is a neutral toast for non-critical feedback.',
    });
}
</script>

<template>
    <ExampleFrame
        title="Toast example"
        description="Uses the shadcn-vue Sonner wrapper so both modes share the same notification primitives."
        :mode-label="props.modeLabel"
    >
        <div class="flex flex-wrap gap-3">
            <Button type="button" @click="fireToast('success')">
                Success toast
            </Button>

            <Button
                type="button"
                variant="secondary"
                @click="fireToast('info')"
            >
                Neutral toast
            </Button>

            <Button
                type="button"
                variant="outline"
                @click="fireToast('loading')"
            >
                Loading toast
            </Button>
        </div>
    </ExampleFrame>
</template>
