<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

import { fetchAuthFeatures } from '@/auth/api/client';
import AuthCard from '@/auth/components/AuthCard.vue';
import RegisterAccountFields from '@/auth/components/RegisterAccountFields.vue';
import type { AuthRegistrationMode } from '@/auth/schemas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';

function parseRegistrationMode(raw: unknown): AuthRegistrationMode | null {
    if (raw === 'email' || raw === 'phone' || raw === 'both') {
        return raw;
    }

    return null;
}

const featuresLoaded = ref(false);
const loadErrorMessage = ref('');
const registrationMode = ref<AuthRegistrationMode | null>(null);

const formReady = computed(
    () => Boolean(featuresLoaded.value) && registrationMode.value !== null,
);

onMounted(async () => {
    loadErrorMessage.value = '';

    try {
        const snapshot = await fetchAuthFeatures();
        const mode = parseRegistrationMode(snapshot.registration_mode);

        if (mode === null) {
            loadErrorMessage.value =
                'Registration is not configured correctly on this deployment.';
            registrationMode.value = null;

            return;
        }

        registrationMode.value = mode;
    } catch {
        loadErrorMessage.value =
            'Unable to load registration options. Refresh and try again.';
        registrationMode.value = null;
    } finally {
        featuresLoaded.value = true;
    }
});
</script>

<template>
    <AuthCard
        title="Create account"
        description="Fill in the details below to get started."
    >
        <div v-if="!featuresLoaded" class="flex flex-col gap-4">
            <div class="flex items-center gap-2 text-muted-foreground">
                <Spinner class="size-4" />
                <span class="text-sm">Loading registration form…</span>
            </div>
        </div>

        <template v-else>
            <Alert v-if="!formReady" variant="destructive">
                <AlertDescription>{{ loadErrorMessage }}</AlertDescription>
            </Alert>

            <RegisterAccountFields
                v-else-if="registrationMode !== null"
                :registration-mode="registrationMode"
            />
        </template>
    </AuthCard>
</template>
