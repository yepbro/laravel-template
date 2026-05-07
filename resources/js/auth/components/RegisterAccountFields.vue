<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { computed, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import { normalizeErrors, register } from '@/auth/api/client';
import type { AuthRegistrationMode } from '@/auth/schemas';
import { registerSchemaForMode } from '@/auth/schemas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ registrationMode: AuthRegistrationMode }>();

const router = useRouter();

const generalError = ref('');
const isSubmitting = ref(false);

const allowsEmail = computed(
    () =>
        props.registrationMode === 'email' || props.registrationMode === 'both',
);

const allowsPhone = computed(
    () =>
        props.registrationMode === 'phone' || props.registrationMode === 'both',
);

const form = useForm({
    validationSchema: toTypedSchema(
        registerSchemaForMode(props.registrationMode),
    ),
    initialValues: {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        await register(values);
        await router.push('/spa');
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            form.setErrors(normalizeErrors(error.response.data.errors ?? {}));
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <Alert v-if="generalError" variant="destructive">
            <AlertDescription>{{ generalError }}</AlertDescription>
        </Alert>

        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <FormField v-slot="{ componentField }" name="name">
                <FormItem>
                    <FormLabel>Full name</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="name"
                            placeholder="Taylor Brooks"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField
                v-if="allowsEmail"
                v-slot="{ componentField }"
                name="email"
            >
                <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="email"
                            placeholder="you@example.com"
                            type="email"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField
                v-if="allowsPhone"
                v-slot="{ componentField }"
                name="phone"
            >
                <FormItem>
                    <FormLabel>Phone</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="tel"
                            placeholder="+1 555 000 0000"
                            type="tel"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
                <FormItem>
                    <FormLabel>Password</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="new-password"
                            type="password"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password_confirmation">
                <FormItem>
                    <FormLabel>Confirm password</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="new-password"
                            type="password"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <Button :disabled="isSubmitting" class="w-full" type="submit">
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Create account
            </Button>

            <Separator />

            <p class="text-center text-sm text-muted-foreground">
                Already have an account?
                <RouterLink
                    class="font-medium text-foreground underline-offset-4 hover:underline"
                    to="/login"
                >
                    Sign in
                </RouterLink>
            </p>
        </form>
    </div>
</template>
