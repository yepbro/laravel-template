<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import {
    normalizeErrors,
    securityStatus,
    updatePassword,
} from '@/auth/api/client';
import { updatePasswordSchema } from '@/auth/schemas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

const router = useRouter();

const passwordConfirmed = ref<boolean | null>(null);
const isCheckingConfirmed = ref(true);

const passwordSuccess = ref('');
const passwordError = ref('');
const isPasswordSubmitting = ref(false);

const passwordForm = useForm({
    validationSchema: toTypedSchema(updatePasswordSchema),
    initialValues: {
        current_password: '',
        password: '',
        password_confirmation: '',
    },
});

onMounted(async () => {
    try {
        const status = await securityStatus();
        passwordConfirmed.value = status.password_confirmed;
    } catch {
        passwordConfirmed.value = false;
    } finally {
        isCheckingConfirmed.value = false;
    }
});

function isSensitiveError(error: unknown): boolean {
    return axios.isAxiosError(error) && error.response?.status === 423;
}

async function handleNavigateToConfirmPassword(): Promise<void> {
    await router.push('/user/confirm-password');
}

const onPasswordSubmit = passwordForm.handleSubmit(async (values) => {
    passwordSuccess.value = '';
    passwordError.value = '';
    isPasswordSubmitting.value = true;

    try {
        await updatePassword(values);
        passwordForm.resetForm();
        passwordSuccess.value = 'Password updated successfully.';
    } catch (error) {
        if (isSensitiveError(error)) {
            passwordConfirmed.value = false;
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 422
        ) {
            passwordForm.setErrors(
                normalizeErrors(error.response.data.errors ?? {}),
            );
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            passwordError.value = error.response.data.message;
        } else {
            passwordError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isPasswordSubmitting.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Password
            </h1>
            <p class="text-sm text-muted-foreground">
                Change your password using your current password.
            </p>
        </div>

        <div
            v-if="isCheckingConfirmed"
            class="flex items-center gap-2 text-muted-foreground"
        >
            <Spinner class="size-4" />
            <span class="text-sm">Checking session…</span>
        </div>

        <Card v-else>
            <CardHeader>
                <CardTitle>Update password</CardTitle>
                <CardDescription
                    >Use a strong, unique password.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Alert
                    v-if="passwordConfirmed === false"
                    class="mb-4"
                    variant="destructive"
                >
                    <AlertDescription class="flex flex-col gap-2">
                        <span>
                            Confirm your password before you can change it from
                            this screen.
                        </span>
                        <RouterLink
                            class="font-medium underline underline-offset-4"
                            to="/user/confirm-password"
                        >
                            Confirm your password
                        </RouterLink>
                    </AlertDescription>
                </Alert>

                <template v-if="passwordConfirmed === false">
                    <p class="text-sm text-muted-foreground">
                        <button
                            class="font-medium text-foreground underline underline-offset-4"
                            type="button"
                            @click="handleNavigateToConfirmPassword"
                        >
                            Confirm your password
                        </button>
                        to update it.
                    </p>
                </template>

                <form
                    v-else
                    class="flex flex-col gap-4"
                    @submit.prevent="onPasswordSubmit"
                >
                    <Alert v-if="passwordSuccess">
                        <AlertDescription>{{
                            passwordSuccess
                        }}</AlertDescription>
                    </Alert>
                    <Alert v-if="passwordError" variant="destructive">
                        <AlertDescription>{{ passwordError }}</AlertDescription>
                    </Alert>

                    <FormField
                        v-slot="{ componentField }"
                        name="current_password"
                    >
                        <FormItem>
                            <FormLabel>Current password</FormLabel>
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

                    <FormField
                        v-slot="{ componentField }"
                        name="password_confirmation"
                    >
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

                    <Button
                        :disabled="isPasswordSubmitting"
                        class="self-start"
                        type="submit"
                    >
                        <Spinner
                            v-if="isPasswordSubmitting"
                            class="mr-2 size-4"
                        />
                        Update password
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
