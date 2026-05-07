<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import { normalizeErrors, resetPassword } from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { resetPasswordSchema } from '@/auth/schemas';
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
import { Spinner } from '@/components/ui/spinner';

const route = useRoute();
const router = useRouter();

const token = String(route.params.token ?? '');
const emailFromQuery = String(route.query.email ?? '');

const generalError = ref('');
const isSubmitting = ref(false);

const form = useForm({
    validationSchema: toTypedSchema(resetPasswordSchema),
    initialValues: {
        token,
        email: emailFromQuery,
        password: '',
        password_confirmation: '',
    },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        await resetPassword(values);
        await router.push('/login');
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
    <AuthCard
        title="Set new password"
        description="Choose a secure password for your account."
    >
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <FormField v-slot="{ componentField }" name="email">
                <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="email"
                            type="email"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
                <FormItem>
                    <FormLabel>New password</FormLabel>
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
                    <FormLabel>Confirm new password</FormLabel>
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
                Reset password
            </Button>

            <p class="text-center text-sm text-muted-foreground">
                <RouterLink
                    class="font-medium text-foreground underline-offset-4 hover:underline"
                    to="/login"
                >
                    Back to sign in
                </RouterLink>
            </p>
        </form>
    </AuthCard>
</template>
