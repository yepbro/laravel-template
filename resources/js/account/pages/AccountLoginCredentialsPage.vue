<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { computed, onMounted, ref } from 'vue';

import {
    fetchAuthFeatures,
    normalizeErrors,
    requestLoginCredentialEmailChange,
    requestLoginCredentialPhoneChange,
} from '@/auth/api/client';
import {
    requestLoginCredentialEmailChangeSchema,
    requestLoginCredentialPhoneChangeSchema,
} from '@/auth/schemas';
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

const featuresLoading = ref(true);
const allowsEmailLoginChange = ref(false);
const allowsPhoneLoginChange = ref(false);

const generalError = ref('');

const emailSuccess = ref('');
const emailSubmitting = ref(false);
const emailForm = useForm({
    validationSchema: toTypedSchema(requestLoginCredentialEmailChangeSchema),
    initialValues: { email: '', current_password: '' },
});

const phoneSuccess = ref('');
const phoneSubmitting = ref(false);
const phoneForm = useForm({
    validationSchema: toTypedSchema(requestLoginCredentialPhoneChangeSchema),
    initialValues: { phone: '', current_password: '' },
});

const showAnyCredentialForm = computed(
    () =>
        !featuresLoading.value &&
        (allowsEmailLoginChange.value || allowsPhoneLoginChange.value),
);

onMounted(async () => {
    try {
        const f = await fetchAuthFeatures();
        allowsEmailLoginChange.value = f.allows_email_registration;
        allowsPhoneLoginChange.value = f.allows_phone_registration;
    } catch {
        generalError.value =
            'Unable to load sign-in options. Refresh and try again.';
    } finally {
        featuresLoading.value = false;
    }
});

const submitEmail = emailForm.handleSubmit(async (values) => {
    generalError.value = '';
    emailSuccess.value = '';
    emailSubmitting.value = true;
    try {
        await requestLoginCredentialEmailChange(values);
        emailSuccess.value =
            'Check your new email inbox for a confirmation link. Your login email does not change until you confirm.';
        emailForm.resetForm({
            values: { email: '', current_password: '' },
        });
    } catch (error) {
        if (
            axios.isAxiosError(error) &&
            error.response?.status === 422 &&
            error.response.data?.errors !== undefined
        ) {
            emailForm.setErrors(normalizeErrors(error.response.data.errors));
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 404
        ) {
            generalError.value =
                'Changing your login email is disabled for this deployment.';
        } else {
            generalError.value =
                axios.isAxiosError(error) && error.response?.data?.message
                    ? String(error.response.data.message)
                    : 'Could not start the email change. Try again.';
        }
    } finally {
        emailSubmitting.value = false;
    }
});

const submitPhone = phoneForm.handleSubmit(async (values) => {
    generalError.value = '';
    phoneSuccess.value = '';
    phoneSubmitting.value = true;
    try {
        await requestLoginCredentialPhoneChange(values);
        phoneSuccess.value =
            'We sent a confirmation message to the new number. Your phone login does not change until you open the signed link we texted.';
        phoneForm.resetForm({
            values: { phone: '', current_password: '' },
        });
    } catch (error) {
        if (
            axios.isAxiosError(error) &&
            error.response?.status === 422 &&
            error.response.data?.errors !== undefined
        ) {
            phoneForm.setErrors(normalizeErrors(error.response.data.errors));
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 404
        ) {
            generalError.value =
                'Changing your login phone is disabled for this deployment.';
        } else {
            generalError.value =
                axios.isAxiosError(error) && error.response?.data?.message
                    ? String(error.response.data.message)
                    : 'Could not start the phone change. Try again.';
        }
    } finally {
        phoneSubmitting.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Login credentials
            </h1>
            <p class="text-sm text-muted-foreground">
                Change the email or phone you use to sign in. You must confirm
                with a one-time link before the new value takes effect.
            </p>
        </div>

        <div
            v-if="featuresLoading"
            class="flex items-center gap-2 text-muted-foreground"
        >
            <Spinner class="size-4" />
            <span class="text-sm">Loading options…</span>
        </div>

        <Alert v-else-if="!showAnyCredentialForm" variant="destructive">
            <AlertDescription>
                Login identifier changes are not available for this account
                configuration.
            </AlertDescription>
        </Alert>

        <Alert v-if="generalError" variant="destructive">
            <AlertDescription>{{ generalError }}</AlertDescription>
        </Alert>

        <Card v-if="allowsEmailLoginChange && !featuresLoading">
            <CardHeader>
                <CardTitle>Email sign-in</CardTitle>
                <CardDescription>
                    We will email you (and your current address, if any) about
                    the pending change.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Alert v-if="emailSuccess" class="mb-4">
                    <AlertDescription>{{ emailSuccess }}</AlertDescription>
                </Alert>
                <form class="flex flex-col gap-4" @submit.prevent="submitEmail">
                    <FormField v-slot="{ componentField }" name="email">
                        <FormItem>
                            <FormLabel>New email</FormLabel>
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
                    <Button
                        :disabled="emailSubmitting"
                        class="w-full sm:w-auto"
                        type="submit"
                    >
                        <Spinner v-if="emailSubmitting" class="mr-2 size-4" />
                        Request email change
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="allowsPhoneLoginChange && !featuresLoading">
            <CardHeader>
                <CardTitle>Phone sign-in</CardTitle>
                <CardDescription>
                    We text a signed confirmation link to the new number.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Alert v-if="phoneSuccess" class="mb-4">
                    <AlertDescription>{{ phoneSuccess }}</AlertDescription>
                </Alert>
                <form class="flex flex-col gap-4" @submit.prevent="submitPhone">
                    <FormField v-slot="{ componentField }" name="phone">
                        <FormItem>
                            <FormLabel>New phone</FormLabel>
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
                    <Button
                        :disabled="phoneSubmitting"
                        class="w-full sm:w-auto"
                        type="submit"
                    >
                        <Spinner v-if="phoneSubmitting" class="mr-2 size-4" />
                        Request phone change
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
