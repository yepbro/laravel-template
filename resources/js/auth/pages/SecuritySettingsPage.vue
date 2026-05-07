<script setup lang="ts">
import { Passkeys } from '@laravel/passkeys';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import type { RegisteredPasskey } from '@/auth/api/client';
import {
    PASSKEY_REGISTER_ROUTES,
    normalizeErrors,
    passkeyDestroy,
    passkeyList,
    regenerateTwoFactorRecoveryCodes,
    securityStatus,
    twoFactorConfirm,
    twoFactorDisable,
    twoFactorEnable,
    twoFactorQrCode,
    twoFactorRecoveryCodes,
    twoFactorSecretKey,
    updatePassword,
    updateProfileInformation,
} from '@/auth/api/client';
import {
    twoFactorConfirmSchema,
    updatePasswordSchema,
    updateProfileSchema,
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
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';

const router = useRouter();

// ----------------------------------------------------------------
// Password confirmation gate
// ----------------------------------------------------------------

const passwordConfirmed = ref<boolean | null>(null);
const isCheckingConfirmed = ref(true);

// ----------------------------------------------------------------
// Two-factor authentication
// ----------------------------------------------------------------

type TwoFactorStage = 'idle' | 'pending-confirm' | 'enabled';

const twoFactorStage = ref<TwoFactorStage>('idle');
const twoFactorError = ref('');
const twoFactorSuccess = ref('');
const isTwoFactorLoading = ref(false);
const qrCodeSvg = ref('');
const secretKey = ref('');
const recoveryCodes = ref<string[]>([]);

onMounted(async () => {
    try {
        const status = await securityStatus();
        passwordConfirmed.value = status.password_confirmed;
        if (status.two_factor_enabled) {
            twoFactorStage.value = 'enabled';
        }
    } catch {
        // If the check fails, default to unconfirmed so sensitive actions stay gated.
        passwordConfirmed.value = false;
    } finally {
        isCheckingConfirmed.value = false;
    }

    if (passwordConfirmed.value === true) {
        await loadPasskeys();
    }
});

/**
 * Central handler for 423 Password Confirmation Required responses.
 * Sets the confirmed flag to false so the gate callout re-appears.
 */
function handlePasswordConfirmRequired(): void {
    passwordConfirmed.value = false;
}

function isSensitiveError(error: unknown): boolean {
    return axios.isAxiosError(error) && error.response?.status === 423;
}

// ----------------------------------------------------------------
// Profile information
// ----------------------------------------------------------------

const profileSuccess = ref('');
const profileError = ref('');
const isProfileSubmitting = ref(false);

const profileForm = useForm({
    validationSchema: toTypedSchema(updateProfileSchema),
    initialValues: { name: '', email: '', phone: '' },
});

const onProfileSubmit = profileForm.handleSubmit(async (values) => {
    profileSuccess.value = '';
    profileError.value = '';
    isProfileSubmitting.value = true;

    try {
        await updateProfileInformation(values);
        profileSuccess.value = 'Profile information updated.';
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 422
        ) {
            profileForm.setErrors(
                normalizeErrors(error.response.data.errors ?? {}),
            );
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            profileError.value = error.response.data.message;
        } else {
            profileError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isProfileSubmitting.value = false;
    }
});

// ----------------------------------------------------------------
// Update password
// ----------------------------------------------------------------

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
            handlePasswordConfirmRequired();
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

const confirmForm = useForm({
    validationSchema: toTypedSchema(twoFactorConfirmSchema),
    initialValues: { code: '' },
});

async function handleEnable(): Promise<void> {
    twoFactorError.value = '';
    isTwoFactorLoading.value = true;

    try {
        await twoFactorEnable();
        const [qr, secret] = await Promise.all([
            twoFactorQrCode(),
            twoFactorSecretKey(),
        ]);
        qrCodeSvg.value = qr.svg;
        secretKey.value = secret.secretKey;
        twoFactorStage.value = 'pending-confirm';
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            twoFactorError.value = error.response.data.message;
        } else {
            twoFactorError.value =
                'Failed to enable two-factor authentication.';
        }
    } finally {
        isTwoFactorLoading.value = false;
    }
}

const onConfirmSubmit = confirmForm.handleSubmit(async (values) => {
    twoFactorError.value = '';
    isTwoFactorLoading.value = true;

    try {
        await twoFactorConfirm(values);
        const codes = await twoFactorRecoveryCodes();
        recoveryCodes.value = codes;
        twoFactorStage.value = 'enabled';
        twoFactorSuccess.value = 'Two-factor authentication is now active.';
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 422
        ) {
            confirmForm.setErrors(
                normalizeErrors(error.response.data.errors ?? {}),
            );
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            twoFactorError.value = error.response.data.message;
        } else {
            twoFactorError.value =
                'An unexpected error occurred. Please try again.';
        }
    } finally {
        isTwoFactorLoading.value = false;
    }
});

async function handleDisable(): Promise<void> {
    twoFactorError.value = '';
    twoFactorSuccess.value = '';
    isTwoFactorLoading.value = true;

    try {
        await twoFactorDisable();
        twoFactorStage.value = 'idle';
        qrCodeSvg.value = '';
        secretKey.value = '';
        recoveryCodes.value = [];
        confirmForm.resetForm();
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            twoFactorError.value = error.response.data.message;
        } else {
            twoFactorError.value =
                'Failed to disable two-factor authentication.';
        }
    } finally {
        isTwoFactorLoading.value = false;
    }
}

async function handleRegenerateCodes(): Promise<void> {
    twoFactorError.value = '';
    isTwoFactorLoading.value = true;

    try {
        await regenerateTwoFactorRecoveryCodes();
        const codes = await twoFactorRecoveryCodes();
        recoveryCodes.value = codes;
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            twoFactorError.value = error.response.data.message;
        } else {
            twoFactorError.value = 'Failed to regenerate recovery codes.';
        }
    } finally {
        isTwoFactorLoading.value = false;
    }
}

async function handleNavigateToConfirmPassword(): Promise<void> {
    await router.push('/user/confirm-password');
}

// ----------------------------------------------------------------
// Passkeys
// ----------------------------------------------------------------

const isPasskeySupported = Passkeys.isSupported();
const registeredPasskeys = ref<RegisteredPasskey[]>([]);
const passkeyName = ref('');
const isPasskeyLoading = ref(false);
const passkeyError = ref('');
const passkeySuccess = ref('');
const isDeletingPasskey = ref<string | null>(null);

async function loadPasskeys(): Promise<void> {
    try {
        registeredPasskeys.value = await passkeyList();
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            passkeyError.value = error.response.data.message;
        } else {
            passkeyError.value = 'Failed to load passkeys.';
        }
    }
}

async function handlePasskeyRegister(): Promise<void> {
    if (!passkeyName.value.trim()) {
        return;
    }

    passkeyError.value = '';
    passkeySuccess.value = '';
    isPasskeyLoading.value = true;

    try {
        await Passkeys.register({
            name: passkeyName.value.trim(),
            routes: PASSKEY_REGISTER_ROUTES,
        });
        passkeyName.value = '';
        passkeySuccess.value = 'Passkey registered successfully.';
        await loadPasskeys();
    } catch (error) {
        passkeyError.value =
            error instanceof Error
                ? error.message
                : 'Failed to register passkey.';
    } finally {
        isPasskeyLoading.value = false;
    }
}

async function handlePasskeyDelete(id: string): Promise<void> {
    passkeyError.value = '';
    isDeletingPasskey.value = id;

    try {
        await passkeyDestroy(id);
        registeredPasskeys.value = registeredPasskeys.value.filter(
            (p) => p.id !== id,
        );
        await loadPasskeys();
    } catch (error) {
        if (isSensitiveError(error)) {
            handlePasswordConfirmRequired();
        } else {
            passkeyError.value = 'Failed to delete passkey.';
        }
    } finally {
        isDeletingPasskey.value = null;
    }
}
</script>

<template>
    <div class="mx-auto flex max-w-2xl flex-col gap-6 p-4">
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">
            Security settings
        </h1>

        <!-- Loading state -->
        <div
            v-if="isCheckingConfirmed"
            class="flex items-center gap-2 text-muted-foreground"
        >
            <Spinner class="size-4" />
            <span class="text-sm">Checking session...</span>
        </div>

        <template v-else>
            <!-- Password confirmation required callout -->
            <Alert v-if="passwordConfirmed === false" variant="destructive">
                <AlertDescription class="flex flex-col gap-2">
                    <span>
                        Your session requires password confirmation before you
                        can manage security settings.
                    </span>
                    <RouterLink
                        class="font-medium underline underline-offset-4"
                        to="/user/confirm-password"
                    >
                        Confirm your password
                    </RouterLink>
                </AlertDescription>
            </Alert>

            <!-- Profile information -->
            <Card>
                <CardHeader>
                    <CardTitle>Profile information</CardTitle>
                    <CardDescription>
                        Update your name and contact details.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-4"
                        @submit.prevent="onProfileSubmit"
                    >
                        <Alert v-if="profileSuccess">
                            <AlertDescription>{{
                                profileSuccess
                            }}</AlertDescription>
                        </Alert>
                        <Alert v-if="profileError" variant="destructive">
                            <AlertDescription>{{
                                profileError
                            }}</AlertDescription>
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
                            :disabled="
                                isProfileSubmitting ||
                                passwordConfirmed === false
                            "
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

            <!-- Update password -->
            <Card>
                <CardHeader>
                    <CardTitle>Update password</CardTitle>
                    <CardDescription
                        >Use a strong, unique password.</CardDescription
                    >
                </CardHeader>
                <CardContent>
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
                            <AlertDescription>{{
                                passwordError
                            }}</AlertDescription>
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

            <!-- Two-factor authentication -->
            <Card>
                <CardHeader>
                    <CardTitle>Two-factor management</CardTitle>
                    <CardDescription>
                        Add an extra layer of security with an authenticator
                        app.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4">
                        <Alert v-if="twoFactorSuccess">
                            <AlertDescription>{{
                                twoFactorSuccess
                            }}</AlertDescription>
                        </Alert>
                        <Alert v-if="twoFactorError" variant="destructive">
                            <AlertDescription>{{
                                twoFactorError
                            }}</AlertDescription>
                        </Alert>

                        <!-- Gated: not confirmed -->
                        <template v-if="passwordConfirmed === false">
                            <p class="text-sm text-muted-foreground">
                                <button
                                    class="font-medium text-foreground underline underline-offset-4"
                                    type="button"
                                    @click="handleNavigateToConfirmPassword"
                                >
                                    Confirm your password
                                </button>
                                to manage two-factor authentication.
                            </p>
                        </template>

                        <!-- Confirmed: show 2FA controls -->
                        <template v-else>
                            <!-- Idle: not yet enabled in this session -->
                            <template v-if="twoFactorStage === 'idle'">
                                <p class="text-sm text-muted-foreground">
                                    Use the button below to enable or manage
                                    two-factor authentication for your account.
                                </p>
                                <Button
                                    :disabled="isTwoFactorLoading"
                                    class="self-start"
                                    type="button"
                                    @click="handleEnable"
                                >
                                    <Spinner
                                        v-if="isTwoFactorLoading"
                                        class="mr-2 size-4"
                                    />
                                    Enable 2FA
                                </Button>
                            </template>

                            <!-- Pending confirmation: show QR + secret + confirm form -->
                            <template
                                v-else-if="twoFactorStage === 'pending-confirm'"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Scan the QR code with your authenticator
                                    app, then enter the generated code to
                                    confirm.
                                </p>

                                <!-- eslint-disable vue/no-v-html -->
                                <div
                                    v-if="qrCodeSvg"
                                    class="rounded-lg border border-border bg-white p-4"
                                    v-html="qrCodeSvg"
                                />
                                <!-- eslint-enable vue/no-v-html -->

                                <div
                                    v-if="secretKey"
                                    class="flex flex-col gap-1"
                                >
                                    <p
                                        class="text-xs font-medium text-muted-foreground"
                                    >
                                        Setup key (manual entry)
                                    </p>
                                    <code
                                        class="rounded bg-muted px-2 py-1 font-mono text-sm tracking-widest"
                                    >
                                        {{ secretKey }}
                                    </code>
                                </div>

                                <form @submit.prevent="onConfirmSubmit">
                                    <FormField
                                        v-slot="{ value, handleChange }"
                                        name="code"
                                    >
                                        <FormItem class="flex flex-col gap-2">
                                            <FormLabel>Confirm code</FormLabel>
                                            <FormControl>
                                                <InputOTP
                                                    :maxlength="6"
                                                    :model-value="value"
                                                    @update:model-value="
                                                        handleChange
                                                    "
                                                >
                                                    <InputOTPGroup>
                                                        <InputOTPSlot
                                                            :index="0"
                                                        />
                                                        <InputOTPSlot
                                                            :index="1"
                                                        />
                                                        <InputOTPSlot
                                                            :index="2"
                                                        />
                                                        <InputOTPSlot
                                                            :index="3"
                                                        />
                                                        <InputOTPSlot
                                                            :index="4"
                                                        />
                                                        <InputOTPSlot
                                                            :index="5"
                                                        />
                                                    </InputOTPGroup>
                                                </InputOTP>
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    </FormField>

                                    <Button
                                        :disabled="isTwoFactorLoading"
                                        class="mt-3 self-start"
                                        type="submit"
                                    >
                                        <Spinner
                                            v-if="isTwoFactorLoading"
                                            class="mr-2 size-4"
                                        />
                                        Confirm and activate
                                    </Button>
                                </form>
                            </template>

                            <!-- Enabled: show recovery codes + disable option -->
                            <template v-else-if="twoFactorStage === 'enabled'">
                                <p class="text-sm text-muted-foreground">
                                    Two-factor authentication is
                                    <strong class="text-foreground"
                                        >active</strong
                                    >. Store your recovery codes in a safe
                                    place.
                                </p>

                                <div
                                    v-if="recoveryCodes.length > 0"
                                    class="flex flex-col gap-1"
                                >
                                    <p
                                        class="text-xs font-medium text-muted-foreground"
                                    >
                                        Recovery codes
                                    </p>
                                    <ul
                                        class="rounded-lg border border-border bg-muted p-3 font-mono text-sm"
                                    >
                                        <li
                                            v-for="recoveryCode in recoveryCodes"
                                            :key="recoveryCode"
                                        >
                                            {{ recoveryCode }}
                                        </li>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        :disabled="isTwoFactorLoading"
                                        type="button"
                                        variant="outline"
                                        @click="handleRegenerateCodes"
                                    >
                                        <Spinner
                                            v-if="isTwoFactorLoading"
                                            class="mr-2 size-4"
                                        />
                                        Regenerate recovery codes
                                    </Button>

                                    <Button
                                        :disabled="isTwoFactorLoading"
                                        type="button"
                                        variant="destructive"
                                        @click="handleDisable"
                                    >
                                        Disable 2FA
                                    </Button>
                                </div>
                            </template>
                        </template>
                    </div>
                </CardContent>
            </Card>

            <!-- Passkeys -->
            <Card>
                <CardHeader>
                    <CardTitle>Passkeys</CardTitle>
                    <CardDescription>
                        Sign in without a password using a passkey.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <template v-if="passwordConfirmed === false">
                        <p class="text-sm text-muted-foreground">
                            <button
                                class="font-medium text-foreground underline underline-offset-4"
                                type="button"
                                @click="handleNavigateToConfirmPassword"
                            >
                                Confirm your password
                            </button>
                            to manage passkeys.
                        </p>
                    </template>

                    <template v-else>
                        <div class="flex flex-col gap-4">
                            <Alert v-if="passkeySuccess">
                                <AlertDescription>{{
                                    passkeySuccess
                                }}</AlertDescription>
                            </Alert>
                            <Alert v-if="passkeyError" variant="destructive">
                                <AlertDescription>{{
                                    passkeyError
                                }}</AlertDescription>
                            </Alert>

                            <!-- Registered passkeys list -->
                            <div
                                v-if="registeredPasskeys.length > 0"
                                class="flex flex-col gap-2"
                            >
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Registered passkeys
                                </p>
                                <ul class="flex flex-col gap-2">
                                    <li
                                        v-for="passkey in registeredPasskeys"
                                        :key="passkey.id"
                                        class="flex items-center justify-between rounded-lg border border-border px-3 py-2"
                                    >
                                        <span class="text-sm">{{
                                            passkey.name
                                        }}</span>
                                        <Button
                                            :disabled="
                                                isDeletingPasskey === passkey.id
                                            "
                                            size="sm"
                                            type="button"
                                            variant="destructive"
                                            @click="
                                                handlePasskeyDelete(passkey.id)
                                            "
                                        >
                                            <Spinner
                                                v-if="
                                                    isDeletingPasskey ===
                                                    passkey.id
                                                "
                                                class="mr-2 size-3"
                                            />
                                            Remove
                                        </Button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Register new passkey (only when browser supports WebAuthn) -->
                            <template v-if="isPasskeySupported">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="passkeyName"
                                        class="max-w-64"
                                        placeholder="Passkey name (e.g. MacBook)"
                                        type="text"
                                    />
                                    <Button
                                        :disabled="
                                            isPasskeyLoading ||
                                            !passkeyName.trim()
                                        "
                                        type="button"
                                        @click="handlePasskeyRegister"
                                    >
                                        <Spinner
                                            v-if="isPasskeyLoading"
                                            class="mr-2 size-4"
                                        />
                                        Add passkey
                                    </Button>
                                </div>
                            </template>
                            <p v-else class="text-sm text-muted-foreground">
                                Passkeys are not supported in this browser. You
                                can still remove passkeys registered on other
                                devices.
                            </p>
                        </div>
                    </template>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
