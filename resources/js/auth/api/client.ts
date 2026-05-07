import axios from 'axios';

import type {
    ConfirmPasswordData,
    DeleteAccountData,
    ForgotPasswordData,
    LoginData,
    PhoneVerificationData,
    RegisterData,
    ResetPasswordData,
    TwoFactorChallengeData,
    TwoFactorConfirmData,
    UpdatePasswordData,
    UpdateProfileData,
} from '@/auth/schemas';

export const ENDPOINTS = {
    /** GET current session user (SPA). */
    currentUser: '/user',
    deleteAccount: '/user',
    login: '/login',
    logout: '/logout',
    register: '/register',
    forgotPassword: '/forgot-password',
    resetPassword: '/reset-password',
    resendEmailVerification: '/email/verification-notification',
    sendPhoneVerification: '/phone/verification-notification',
    verifyPhone: '/phone/verify',
    confirmPassword: '/user/confirm-password',
    updatePassword: '/user/password',
    updateProfileInformation: '/user/profile-information',
    twoFactorChallenge: '/two-factor-challenge',
    twoFactorEnable: '/user/two-factor-authentication',
    twoFactorConfirm: '/user/confirmed-two-factor-authentication',
    twoFactorDisable: '/user/two-factor-authentication',
    twoFactorQrCode: '/user/two-factor-qr-code',
    twoFactorSecretKey: '/user/two-factor-secret-key',
    twoFactorRecoveryCodes: '/user/two-factor-recovery-codes',
    regenerateTwoFactorRecoveryCodes: '/user/two-factor-recovery-codes',
    confirmedPasswordStatus: '/user/confirmed-password-status',
    securityStatus: '/user/security-status',
    passkeyLoginOptions: '/passkeys/login/options',
    passkeyLogin: '/passkeys/login',
    passkeyConfirmOptions: '/passkeys/confirm/options',
    passkeyConfirm: '/passkeys/confirm',
    passkeyRegisterOptions: '/user/passkeys/options',
    passkeyRegister: '/user/passkeys',
    passkeyDestroy: '/user/passkeys',
    passkeyList: '/user/passkeys',
} as const;

export const PASSKEY_LOGIN_ROUTES = {
    options: ENDPOINTS.passkeyLoginOptions,
    submit: ENDPOINTS.passkeyLogin,
} as const;

export const PASSKEY_CONFIRM_ROUTES = {
    options: ENDPOINTS.passkeyConfirmOptions,
    submit: ENDPOINTS.passkeyConfirm,
} as const;

export const PASSKEY_REGISTER_ROUTES = {
    options: ENDPOINTS.passkeyRegisterOptions,
    submit: ENDPOINTS.passkeyRegister,
} as const;

export interface TwoFactorQrCodeResponse {
    svg: string;
    url: string;
}

export interface TwoFactorSecretKeyResponse {
    secretKey: string;
}

export interface ConfirmedPasswordStatusResponse {
    confirmed: boolean;
}

export interface SecurityStatusResponse {
    password_confirmed: boolean;
    two_factor_enabled: boolean;
    two_factor_confirmed: boolean;
}

export interface CurrentUserResponse {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    email_verified_at: string | null;
    phone_verified_at: string | null;
}

export interface RegisteredPasskey {
    id: string;
    name: string;
    authenticator: string | null;
    last_used_at: string | null;
    created_at: string | null;
}

/**
 * Normalizes Laravel 422 validation errors (Record<string, string[]>) into a
 * flat Record<string, string> by picking the first message for each field.
 */
export function normalizeErrors(
    errors: Record<string, string[]>,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(errors).map(([field, messages]) => [
            field,
            messages[0] ?? '',
        ]),
    );
}

/**
 * Maps two-factor challenge errors by active tab so that a backend `code`
 * error (which Fortify may return regardless of which field was submitted)
 * is surfaced on the `recovery_code` field when the recovery tab is active.
 * Does not remap when a `recovery_code` error is already present.
 */
export function mapTwoFactorChallengeErrors(
    errors: Record<string, string>,
    activeTab: 'code' | 'recovery',
): Record<string, string> {
    if (
        activeTab === 'recovery' &&
        errors.code !== undefined &&
        errors.recovery_code === undefined
    ) {
        const { code, ...rest } = errors;
        return { ...rest, recovery_code: code };
    }
    return errors;
}

const client = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

let cachedCurrentUser: CurrentUserResponse | null = null;

export function clearCurrentUserCache(): void {
    cachedCurrentUser = null;
}

export async function fetchCurrentUser(
    force = false,
): Promise<CurrentUserResponse> {
    if (!force && cachedCurrentUser !== null) {
        return cachedCurrentUser;
    }

    const response = await client.get<CurrentUserResponse>(
        ENDPOINTS.currentUser,
    );
    const data = response.data;

    if (data === undefined) {
        throw new Error('Unexpected empty current user.');
    }

    cachedCurrentUser = data;

    return data;
}

export async function login(
    data: LoginData,
): Promise<{ two_factor?: boolean }> {
    const response = await client.post<{ two_factor?: boolean }>(
        ENDPOINTS.login,
        data,
    );
    return response.data ?? {};
}

export async function logout(): Promise<void> {
    await client.post(ENDPOINTS.logout);
    clearCurrentUserCache();
}

export async function deleteAccount(data: DeleteAccountData): Promise<{
    redirect: string;
}> {
    const response = await client.delete<{ redirect: string }>(
        ENDPOINTS.deleteAccount,
        { data },
    );
    clearCurrentUserCache();

    const payload = response.data;

    if (payload === undefined) {
        return { redirect: '/login' };
    }

    return payload;
}

export async function register(data: RegisterData): Promise<void> {
    await client.post(ENDPOINTS.register, data);
}

export async function forgotPassword(data: ForgotPasswordData): Promise<void> {
    await client.post(ENDPOINTS.forgotPassword, data);
}

export async function resetPassword(data: ResetPasswordData): Promise<void> {
    await client.post(ENDPOINTS.resetPassword, data);
}

export async function resendEmailVerification(): Promise<void> {
    await client.post(ENDPOINTS.resendEmailVerification);
}

export async function sendPhoneVerification(): Promise<void> {
    await client.post(ENDPOINTS.sendPhoneVerification);
}

export async function verifyPhone(data: PhoneVerificationData): Promise<void> {
    await client.post(ENDPOINTS.verifyPhone, data);
}

export async function confirmPassword(
    data: ConfirmPasswordData,
): Promise<void> {
    await client.post(ENDPOINTS.confirmPassword, data);
}

export async function updatePassword(data: UpdatePasswordData): Promise<void> {
    await client.put(ENDPOINTS.updatePassword, data);
}

export async function updateProfileInformation(
    data: UpdateProfileData,
): Promise<void> {
    await client.put(ENDPOINTS.updateProfileInformation, data);
}

export async function twoFactorChallenge(
    data: TwoFactorChallengeData,
): Promise<void> {
    await client.post(ENDPOINTS.twoFactorChallenge, data);
}

export async function twoFactorEnable(): Promise<void> {
    await client.post(ENDPOINTS.twoFactorEnable);
}

export async function twoFactorConfirm(
    data: TwoFactorConfirmData,
): Promise<void> {
    await client.post(ENDPOINTS.twoFactorConfirm, data);
}

export async function twoFactorDisable(): Promise<void> {
    await client.delete(ENDPOINTS.twoFactorDisable);
}

export async function twoFactorQrCode(): Promise<TwoFactorQrCodeResponse> {
    const response = await client.get<TwoFactorQrCodeResponse>(
        ENDPOINTS.twoFactorQrCode,
    );
    return response.data;
}

export async function twoFactorSecretKey(): Promise<TwoFactorSecretKeyResponse> {
    const response = await client.get<TwoFactorSecretKeyResponse>(
        ENDPOINTS.twoFactorSecretKey,
    );
    return response.data;
}

export async function twoFactorRecoveryCodes(): Promise<string[]> {
    const response = await client.get<string[]>(
        ENDPOINTS.twoFactorRecoveryCodes,
    );
    return response.data;
}

export async function regenerateTwoFactorRecoveryCodes(): Promise<void> {
    await client.post(ENDPOINTS.regenerateTwoFactorRecoveryCodes);
}

export async function confirmedPasswordStatus(): Promise<ConfirmedPasswordStatusResponse> {
    const response = await client.get<ConfirmedPasswordStatusResponse>(
        ENDPOINTS.confirmedPasswordStatus,
    );
    return response.data;
}

export async function securityStatus(): Promise<SecurityStatusResponse> {
    const response = await client.get<SecurityStatusResponse>(
        ENDPOINTS.securityStatus,
    );
    return response.data;
}

export async function passkeyDestroy(id: string): Promise<void> {
    await client.delete(`${ENDPOINTS.passkeyDestroy}/${id}`);
}

export async function passkeyList(): Promise<RegisteredPasskey[]> {
    const response = await client.get<RegisteredPasskey[]>(
        ENDPOINTS.passkeyList,
    );
    return response.data;
}
