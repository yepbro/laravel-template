<script setup lang="ts">
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import {
    PASSKEY_CONFIRM_ROUTES,
    confirmPassword,
    normalizeErrors,
} from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { confirmPasswordSchema } from '@/auth/schemas';
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

const router = useRouter();

const generalError = ref('');
const isSubmitting = ref(false);

const {
    verify: passkeyVerify,
    isLoading: isPasskeyLoading,
    error: passkeyError,
    isSupported: isPasskeySupported,
} = usePasskeyVerify({
    routes: PASSKEY_CONFIRM_ROUTES,
    onSuccess: async () => {
        await router.push('/spa/auth/security');
    },
});

const form = useForm({
    validationSchema: toTypedSchema(confirmPasswordSchema),
    initialValues: { password: '' },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        await confirmPassword(values);
        await router.push('/spa/auth/security');
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
        title="Confirm password"
        description="Please confirm your password before continuing."
    >
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <FormField v-slot="{ componentField }" name="password">
                <FormItem>
                    <FormLabel>Password</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="current-password"
                            type="password"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <Button :disabled="isSubmitting" class="w-full" type="submit">
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Confirm
            </Button>
        </form>

        <template v-if="isPasskeySupported">
            <Separator />
            <div class="flex flex-col gap-2">
                <Alert v-if="passkeyError" variant="destructive">
                    <AlertDescription>{{ passkeyError }}</AlertDescription>
                </Alert>
                <Button
                    :disabled="isPasskeyLoading"
                    class="w-full"
                    type="button"
                    variant="outline"
                    @click="passkeyVerify"
                >
                    <Spinner v-if="isPasskeyLoading" class="mr-2 size-4" />
                    Use passkey instead
                </Button>
            </div>
        </template>
    </AuthCard>
</template>
