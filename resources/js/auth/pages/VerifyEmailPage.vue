<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';

import { resendEmailVerification } from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

const successMessage = ref('');
const generalError = ref('');
const isSubmitting = ref(false);

async function handleResend(): Promise<void> {
    generalError.value = '';
    successMessage.value = '';
    isSubmitting.value = true;

    try {
        await resendEmailVerification();
        successMessage.value =
            'A new verification link has been sent to your email address.';
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.data?.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <AuthCard
        title="Verify your email"
        description="Check your inbox for a verification link."
    >
        <div class="flex flex-col gap-4">
            <Alert v-if="successMessage">
                <AlertDescription>{{ successMessage }}</AlertDescription>
            </Alert>

            <Alert v-if="generalError" variant="destructive">
                <AlertDescription>{{ generalError }}</AlertDescription>
            </Alert>

            <p class="text-sm text-muted-foreground">
                Before continuing, please verify your email address by clicking
                the link we sent you. If you did not receive the email, we can
                send another.
            </p>

            <Button
                :disabled="isSubmitting"
                class="w-full"
                @click="handleResend"
            >
                <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                Resend verification email
            </Button>
        </div>
    </AuthCard>
</template>
