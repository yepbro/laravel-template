import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import {
    starterLeads,
    type DemoLead,
    type DemoLeadInput,
} from '@/shared/demo/data';

export const useDemoStore = defineStore(
    'frontend-demo',
    () => {
        const leads = ref<DemoLead[]>([...starterLeads]);
        const submissionCount = ref(0);
        const toastCount = ref(0);
        const lastSubmittedMode = ref<string | null>(null);

        const totalLeads = computed(() => leads.value.length);

        function addLead(input: DemoLeadInput): DemoLead {
            submissionCount.value += 1;
            lastSubmittedMode.value = input.mode;

            const nextLead: DemoLead = {
                id: Date.now(),
                ...input,
                status: submissionCount.value > 1 ? 'Qualified' : 'New',
            };

            leads.value = [nextLead, ...leads.value];

            return nextLead;
        }

        function trackToast(): void {
            toastCount.value += 1;
        }

        return {
            leads,
            submissionCount,
            toastCount,
            lastSubmittedMode,
            totalLeads,
            addLead,
            trackToast,
        };
    },
    {
        persist: {
            pick: [
                'leads',
                'submissionCount',
                'toastCount',
                'lastSubmittedMode',
            ],
        },
    },
);
