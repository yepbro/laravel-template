<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { RouterLink } from 'vue-router';

import { forgotPassword, normalizeErrors } from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { forgotPasswordSchema } from '@/auth/schemas';
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

const successMessage = ref('');
const generalError = ref('');
const isSubmitting = ref(false);

const form = useForm({
    validationSchema: toTypedSchema(forgotPasswordSchema),
    initialValues: { email: '' },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    successMessage.value = '';
    isSubmitting.value = true;

    try {
        await forgotPassword(values);
        successMessage.value =
            'A password reset link has been sent to your email address.';
        form.resetForm();
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
        title="Forgot your password?"
        description="Enter your email and we'll send you a reset link."
    >
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <Alert v-if="successMessage">
                <AlertDescription>{{ successMessage }}</AlertDescription>
            </Alert>

            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <FormField v-slot="{ componentField }" name="email">
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

            <Button :disabled="isSubmitting" class="w-full" type="submit">
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Send reset link
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
