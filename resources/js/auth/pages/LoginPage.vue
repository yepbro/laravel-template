<script setup lang="ts">
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import {
    PASSKEY_LOGIN_ROUTES,
    login,
    normalizeErrors,
} from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { loginSchema } from '@/auth/schemas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    routes: PASSKEY_LOGIN_ROUTES,
    onSuccess: async (response) => {
        if (response.redirect) {
            window.location.href = response.redirect;
        } else {
            await router.push('/spa');
        }
    },
});

const form = useForm({
    validationSchema: toTypedSchema(loginSchema),
    initialValues: { login: '', password: '', remember: false },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        const data = await login(values);
        if (data.two_factor) {
            await router.push('/spa/auth/two-factor-challenge');
        } else {
            await router.push('/spa');
        }
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
        title="Sign in"
        description="Enter your credentials to access your account."
    >
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <FormField v-slot="{ componentField }" name="login">
                <FormItem>
                    <FormLabel>Email or phone</FormLabel>
                    <FormControl>
                        <Input
                            autocomplete="username webauthn"
                            placeholder="you@example.com"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="password">
                <FormItem>
                    <div class="flex items-center justify-between">
                        <FormLabel>Password</FormLabel>
                        <RouterLink
                            class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                            to="/spa/auth/forgot-password"
                        >
                            Forgot password?
                        </RouterLink>
                    </div>
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

            <FormField
                v-slot="{ value, handleChange }"
                name="remember"
                type="checkbox"
            >
                <FormItem class="flex items-center gap-2">
                    <FormControl>
                        <Checkbox
                            :checked="value"
                            @update:checked="handleChange"
                        />
                    </FormControl>
                    <FormLabel class="cursor-pointer font-normal"
                        >Remember me</FormLabel
                    >
                </FormItem>
            </FormField>

            <Button :disabled="isSubmitting" class="w-full" type="submit">
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Sign in
            </Button>

            <Separator />

            <div class="flex flex-col gap-2">
                <template v-if="isPasskeySupported">
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
                        Sign in with passkey
                    </Button>
                </template>
                <p v-else class="text-center text-sm text-muted-foreground">
                    Passkeys are not supported in this browser.
                </p>
            </div>

            <Separator />

            <p class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <RouterLink
                    class="font-medium text-foreground underline-offset-4 hover:underline"
                    to="/spa/auth/register"
                >
                    Create one
                </RouterLink>
            </p>
        </form>
    </AuthCard>
</template>
