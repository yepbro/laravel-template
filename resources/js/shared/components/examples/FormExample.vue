<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { toast } from 'vue-sonner';
import { z } from 'zod';

import { Button } from '@/components/ui/button';
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import ExampleFrame from '@/shared/components/examples/ExampleFrame.vue';
import { useDemoStore } from '@/shared/stores/demo';

interface Props {
    modeLabel: string;
}

const props = defineProps<Props>();

const demoStore = useDemoStore();

const formSchema = toTypedSchema(
    z.object({
        name: z.string().min(2, 'Name must be at least 2 characters.'),
        email: z.string().email('Use a valid work email.'),
        company: z.string().min(2, 'Company must be at least 2 characters.'),
    }),
);

const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        name: '',
        email: '',
        company: '',
    },
});

const onSubmit = form.handleSubmit((values, actions) => {
    const lead = demoStore.addLead({
        ...values,
        mode: props.modeLabel,
    });

    toast.success(`Saved ${lead.company}`, {
        description: `${lead.name} is now queued from ${props.modeLabel}.`,
    });

    demoStore.trackToast();
    actions.resetForm();
});
</script>

<template>
    <ExampleFrame
        title="Validated form example"
        description="Uses vee-validate with Zod, shared Pinia state, and a toast confirmation after submit."
        :mode-label="props.modeLabel"
    >
        <form class="space-y-4" @submit.prevent="onSubmit">
            <FormField v-slot="{ componentField }" name="name">
                <FormItem>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                        <Input
                            placeholder="Taylor Brooks"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormDescription>
                        Keep the starter form realistic and easy to replace.
                    </FormDescription>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="email">
                <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                        <Input
                            placeholder="taylor@company.dev"
                            type="email"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="company">
                <FormItem>
                    <FormLabel>Company</FormLabel>
                    <FormControl>
                        <Input placeholder="Acme" v-bind="componentField" />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <Button class="w-full sm:w-auto" type="submit">
                Save demo lead
            </Button>
        </form>
    </ExampleFrame>
</template>
