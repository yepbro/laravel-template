<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { onMounted, ref } from 'vue';

import {
    fetchCurrentUser,
    normalizeErrors,
    updateProfileInformation,
} from '@/auth/api/client';
import { updateProfileSchema } from '@/auth/schemas';
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

const profileSuccess = ref('');
const profileError = ref('');
const isProfileSubmitting = ref(false);
const isLoadingUser = ref(true);

const profileForm = useForm({
    validationSchema: toTypedSchema(updateProfileSchema),
    initialValues: { name: '', email: '', phone: '' },
});

onMounted(async () => {
    isLoadingUser.value = true;

    try {
        const user = await fetchCurrentUser(true);

        profileForm.resetForm({
            values: {
                name: user.name,
                email: user.email,
                phone: user.phone ?? '',
            },
        });
    } catch {
        profileError.value = 'Unable to load your profile. Please refresh.';
    } finally {
        isLoadingUser.value = false;
    }
});

const onProfileSubmit = profileForm.handleSubmit(async (values) => {
    profileSuccess.value = '';
    profileError.value = '';
    isProfileSubmitting.value = true;

    try {
        await updateProfileInformation(values);
        profileSuccess.value = 'Profile information updated.';
        await fetchCurrentUser(true);
    } catch (error) {
        if (
            axios.isAxiosError(error) &&
            error.response?.status === 422 &&
            error.response.data?.errors !== undefined
        ) {
            profileForm.setErrors(
                normalizeErrors(error.response.data.errors ?? {}),
            );
        } else if (
            axios.isAxiosError(error) &&
            error.response?.data?.message !== undefined
        ) {
            profileError.value = String(error.response.data.message);
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 423
        ) {
            profileError.value =
                'Confirm your password in Security settings before updating sensitive profile fields.';
        } else {
            profileError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isProfileSubmitting.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Profile
            </h1>
            <p class="text-sm text-muted-foreground">
                Update your name and contact details.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Profile information</CardTitle>
                <CardDescription>
                    Changes here use the same rules as the main application.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="isLoadingUser"
                    class="flex items-center gap-2 text-muted-foreground"
                >
                    <Spinner class="size-4" />
                    <span class="text-sm">Loading profile…</span>
                </div>

                <form
                    v-else
                    class="flex flex-col gap-4"
                    @submit.prevent="onProfileSubmit"
                >
                    <Alert v-if="profileSuccess">
                        <AlertDescription>{{
                            profileSuccess
                        }}</AlertDescription>
                    </Alert>
                    <Alert v-if="profileError" variant="destructive">
                        <AlertDescription>{{ profileError }}</AlertDescription>
                    </Alert>

                    <FormField v-slot="{ componentField }" name="name">
                        <FormItem>
                            <FormLabel>Name</FormLabel>
                            <FormControl>
                                <Input
                                    autocomplete="name"
                                    placeholder="Your name"
                                    v-bind="componentField"
                                />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

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

                    <FormField v-slot="{ componentField }" name="phone">
                        <FormItem>
                            <FormLabel>Phone</FormLabel>
                            <FormControl>
                                <Input
                                    autocomplete="tel"
                                    placeholder="+1234567890"
                                    type="tel"
                                    v-bind="componentField"
                                />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <Button
                        :disabled="isProfileSubmitting"
                        class="self-start"
                        type="submit"
                    >
                        <Spinner
                            v-if="isProfileSubmitting"
                            class="mr-2 size-4"
                        />
                        Save profile
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
