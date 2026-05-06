<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import {
    normalizeErrors,
    sendPhoneVerification,
    verifyPhone,
} from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { phoneVerificationSchema } from '@/auth/schemas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';

const router = useRouter();

const generalError = ref('');
const isSubmitting = ref(false);
const isResending = ref(false);
const resendSuccess = ref('');

const form = useForm({
    validationSchema: toTypedSchema(phoneVerificationSchema),
    initialValues: { code: '' },
});

const onSubmit = form.handleSubmit(async (values) => {
    generalError.value = '';
    isSubmitting.value = true;

    try {
        await verifyPhone(values);
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

async function handleResend(): Promise<void> {
    generalError.value = '';
    resendSuccess.value = '';
    isResending.value = true;

    try {
        await sendPhoneVerification();
        resendSuccess.value = 'A new code has been sent to your phone.';
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.data?.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isResending.value = false;
    }
}
</script>

<template>
    <AuthCard
        title="Verify your phone"
        description="Enter the 6-digit code sent to your phone number."
    >
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <Alert v-if="resendSuccess">
                <AlertDescription>{{ resendSuccess }}</AlertDescription>
            </Alert>

            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <FormField v-slot="{ value, handleChange }" name="code">
                <FormItem class="flex flex-col items-center gap-2">
                    <FormLabel>Verification code</FormLabel>
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

            <Button :disabled="isSubmitting" class="w-full" type="submit">
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Verify phone
            </Button>

            <p class="text-center text-sm text-muted-foreground">
                Didn't receive a code?
                <button
                    :disabled="isResending"
                    class="font-medium text-foreground underline-offset-4 hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    @click="handleResend"
                >
                    <Spinner v-if="isResending" class="mr-1 inline size-3" />
                    Resend
                </button>
            </p>
        </form>
    </AuthCard>
</template>
