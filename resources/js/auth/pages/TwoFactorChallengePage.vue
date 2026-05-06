<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { z } from 'zod';

import {
    mapTwoFactorChallengeErrors,
    normalizeErrors,
    twoFactorChallenge,
} from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
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
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const router = useRouter();

const activeTab = ref<'code' | 'recovery'>('code');
const generalError = ref('');
const isSubmitting = ref(false);

const codeSchema = z.object({
    code: z
        .string()
        .length(6, 'Code must be exactly 6 digits.')
        .regex(/^\d+$/, 'Code must contain digits only.'),
});

const recoverySchema = z.object({
    recovery_code: z.string().min(1, 'Recovery code is required.'),
});

const codeForm = useForm({
    validationSchema: toTypedSchema(codeSchema),
    initialValues: { code: '' },
});

const recoveryForm = useForm({
    validationSchema: toTypedSchema(recoverySchema),
    initialValues: { recovery_code: '' },
});

async function submit(data: {
    code?: string;
    recovery_code?: string;
}): Promise<void> {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        await twoFactorChallenge(data);
        await router.push('/spa');
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            const normalized = normalizeErrors(
                error.response.data.errors ?? {},
            );
            const mapped = mapTwoFactorChallengeErrors(
                normalized,
                activeTab.value,
            );
            if (activeTab.value === 'code') {
                codeForm.setErrors(mapped);
            } else {
                recoveryForm.setErrors(mapped);
            }
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
}

const onCodeSubmit = codeForm.handleSubmit((values) =>
    submit({ code: values.code }),
);
const onRecoverySubmit = recoveryForm.handleSubmit((values) =>
    submit({ recovery_code: values.recovery_code }),
);
</script>

<template>
    <AuthCard
        title="Two-factor authentication"
        description="Confirm access to your account."
    >
        <div class="flex flex-col gap-4">
            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <Tabs v-model="activeTab">
                <TabsList class="w-full">
                    <TabsTrigger class="flex-1" value="code">
                        Authentication code
                    </TabsTrigger>
                    <TabsTrigger class="flex-1" value="recovery">
                        Recovery code
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="code">
                    <form
                        class="flex flex-col gap-4 pt-2"
                        @submit.prevent="onCodeSubmit"
                    >
                        <p class="text-sm text-muted-foreground">
                            Enter the 6-digit code from your authenticator app.
                        </p>

                        <FormField v-slot="{ value, handleChange }" name="code">
                            <FormItem class="flex flex-col items-center gap-2">
                                <FormLabel>Authentication code</FormLabel>
                                <FormControl>
                                    <InputOTP
                                        :maxlength="6"
                                        :model-value="value"
                                        @update:model-value="handleChange"
                                    >
                                        <InputOTPGroup>
                                            <InputOTPSlot :index="0" />
                                            <InputOTPSlot :index="1" />
                                            <InputOTPSlot :index="2" />
                                            <InputOTPSlot :index="3" />
                                            <InputOTPSlot :index="4" />
                                            <InputOTPSlot :index="5" />
                                        </InputOTPGroup>
                                    </InputOTP>
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <Button
                            :disabled="isSubmitting"
                            class="w-full"
                            type="submit"
                        >
                            <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                            Verify
                        </Button>
                    </form>
                </TabsContent>

                <TabsContent value="recovery">
                    <form
                        class="flex flex-col gap-4 pt-2"
                        @submit.prevent="onRecoverySubmit"
                    >
                        <p class="text-sm text-muted-foreground">
                            Enter one of your emergency recovery codes.
                        </p>

                        <FormField
                            v-slot="{ componentField }"
                            name="recovery_code"
                        >
                            <FormItem>
                                <FormLabel>Recovery code</FormLabel>
                                <FormControl>
                                    <Input
                                        autocomplete="one-time-code"
                                        placeholder="xxxx-xxxx-xxxx"
                                        v-bind="componentField"
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <Button
                            :disabled="isSubmitting"
                            class="w-full"
                            type="submit"
                        >
                            <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                            Verify
                        </Button>
                    </form>
                </TabsContent>
            </Tabs>
        </div>
    </AuthCard>
</template>
