<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ExampleFrame from '@/shared/components/examples/ExampleFrame.vue';
import { useDemoStore } from '@/shared/stores/demo';

interface Props {
    modeLabel: string;
}

const props = defineProps<Props>();

const demoStore = useDemoStore();
const { leads } = storeToRefs(demoStore);

const visibleLeads = computed(() => leads.value.slice(0, 5));

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'Qualified') {
        return 'default';
    }

    if (status === 'Trial') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <ExampleFrame
        title="Starter table example"
        description="Shows live data coming from the shared Pinia store, ready to swap with your own API-backed dataset."
        :mode-label="props.modeLabel"
    >
        <Table>
            <TableCaption>
                The table updates immediately after the form demo adds a lead.
            </TableCaption>

            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Company</TableHead>
                    <TableHead>Mode</TableHead>
                    <TableHead>Status</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-if="visibleLeads.length > 0">
                    <TableRow v-for="lead in visibleLeads" :key="lead.id">
                        <TableCell class="font-medium">
                            <div>{{ lead.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ lead.email }}
                            </div>
                        </TableCell>
                        <TableCell>{{ lead.company }}</TableCell>
                        <TableCell>{{ lead.mode }}</TableCell>
                        <TableCell>
                            <Badge :variant="badgeVariant(lead.status)">
                                {{ lead.status }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                </template>

                <TableEmpty v-else :colspan="4">
                    No demo leads yet.
                </TableEmpty>
            </TableBody>
        </Table>
    </ExampleFrame>
</template>
