<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';

import { deleteAccount } from '@/auth/api/client';
import { deleteAccountSchema } from '@/auth/schemas';
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

const formDelete = useForm({
    validationSchema: toTypedSchema(deleteAccountSchema),
    initialValues: { current_password: '' },
});

const isDeleting = ref(false);

const onSubmitDelete = formDelete.handleSubmit(async (values) => {
    formDelete.setFieldError('current_password', '');
    isDeleting.value = true;

    try {
        const { redirect } = await deleteAccount(values);
        window.location.assign(redirect);
    } catch (error) {
        if (
            axios.isAxiosError(error) &&
            error.response?.status === 422 &&
            error.response.data?.errors !== undefined
        ) {
            const errors =
                (
                    error.response.data.errors as Record<
                        string,
                        string[] | undefined
                    >
                ).current_password ?? [];

            formDelete.setFieldError(
                'current_password',
                errors[0] ?? 'Something went wrong.',
            );
        }
    } finally {
        isDeleting.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Delete account
            </h1>
            <p class="text-sm text-muted-foreground">
                This hides your profile and prevents future sign-ins. You will
                be signed out on this browser immediately afterward.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Danger zone</CardTitle>
                <CardDescription>
                    Enter your password to confirm you want to close this
                    account.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Alert class="mb-4" variant="destructive">
                    <AlertDescription>
                        This cannot be undone from the UI. Restore from backups
                        is the only rollback path outside this prototype.
                    </AlertDescription>
                </Alert>

                <form
                    class="flex flex-col gap-4"
                    @submit.prevent="onSubmitDelete"
                >
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
                        variant="destructive"
                        type="submit"
                        :disabled="isDeleting"
                    >
                        {{ isDeleting ? 'Deleting…' : 'Delete my account' }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
