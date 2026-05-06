import { describe, expect, it } from 'vitest';

import {
    ENDPOINTS,
    PASSKEY_CONFIRM_ROUTES,
    PASSKEY_LOGIN_ROUTES,
    PASSKEY_REGISTER_ROUTES,
    mapTwoFactorChallengeErrors,
    normalizeErrors,
} from '@/auth/api/client';
import {
    confirmPasswordSchema,
    createUpdateProfileSchema,
    forgotPasswordSchema,
    loginSchema,
    phoneVerificationSchema,
    registerSchema,
    resetPasswordSchema,
    twoFactorChallengeSchema,
    twoFactorConfirmSchema,
    updatePasswordSchema,
    updateProfileSchema,
} from '@/auth/schemas';

describe('loginSchema', () => {
    it('passes with valid credentials', () => {
        const result = loginSchema.safeParse({
            login: 'user@example.com',
            password: 'secret',
        });
        expect(result.success).toBe(true);
    });

    it('fails when login is empty', () => {
        const result = loginSchema.safeParse({ login: '', password: 'secret' });
        expect(result.success).toBe(false);
    });

    it('fails when password is empty', () => {
        const result = loginSchema.safeParse({
            login: 'user@example.com',
            password: '',
        });
        expect(result.success).toBe(false);
    });
});

describe('registerSchema', () => {
    it('passes with email and matching passwords', () => {
        const result = registerSchema.safeParse({
            name: 'Taylor',
            email: 'taylor@example.com',
            password: 'password',
            password_confirmation: 'password',
        });
        expect(result.success).toBe(true);
    });

    it('passes with phone and no email', () => {
        const result = registerSchema.safeParse({
            name: 'Taylor',
            phone: '+1234567890',
            password: 'password',
            password_confirmation: 'password',
        });
        expect(result.success).toBe(true);
    });

    it('fails when passwords do not match', () => {
        const result = registerSchema.safeParse({
            name: 'Taylor',
            email: 'taylor@example.com',
            password: 'password',
            password_confirmation: 'different',
        });
        expect(result.success).toBe(false);
    });

    it('fails when neither email nor phone is provided', () => {
        const result = registerSchema.safeParse({
            name: 'Taylor',
            password: 'password',
            password_confirmation: 'password',
        });
        expect(result.success).toBe(false);
    });

    it('fails with invalid email format', () => {
        const result = registerSchema.safeParse({
            name: 'Taylor',
            email: 'not-an-email',
            password: 'password',
            password_confirmation: 'password',
        });
        expect(result.success).toBe(false);
    });
});

describe('forgotPasswordSchema', () => {
    it('passes with valid email', () => {
        const result = forgotPasswordSchema.safeParse({
            email: 'user@example.com',
        });
        expect(result.success).toBe(true);
    });

    it('fails with invalid email', () => {
        const result = forgotPasswordSchema.safeParse({
            email: 'not-an-email',
        });
        expect(result.success).toBe(false);
    });

    it('fails when email is empty', () => {
        const result = forgotPasswordSchema.safeParse({ email: '' });
        expect(result.success).toBe(false);
    });
});

describe('resetPasswordSchema', () => {
    it('passes with token, email, and matching passwords', () => {
        const result = resetPasswordSchema.safeParse({
            token: 'abc123',
            email: 'user@example.com',
            password: 'newpassword',
            password_confirmation: 'newpassword',
        });
        expect(result.success).toBe(true);
    });

    it('fails when passwords do not match', () => {
        const result = resetPasswordSchema.safeParse({
            token: 'abc123',
            email: 'user@example.com',
            password: 'newpassword',
            password_confirmation: 'different',
        });
        expect(result.success).toBe(false);
    });

    it('fails without token', () => {
        const result = resetPasswordSchema.safeParse({
            token: '',
            email: 'user@example.com',
            password: 'newpassword',
            password_confirmation: 'newpassword',
        });
        expect(result.success).toBe(false);
    });
});

describe('phoneVerificationSchema', () => {
    it('passes with 6-digit numeric code', () => {
        const result = phoneVerificationSchema.safeParse({ code: '123456' });
        expect(result.success).toBe(true);
    });

    it('fails with fewer than 6 digits', () => {
        const result = phoneVerificationSchema.safeParse({ code: '123' });
        expect(result.success).toBe(false);
    });

    it('fails with non-numeric code', () => {
        const result = phoneVerificationSchema.safeParse({ code: 'abcdef' });
        expect(result.success).toBe(false);
    });

    it('fails with empty code', () => {
        const result = phoneVerificationSchema.safeParse({ code: '' });
        expect(result.success).toBe(false);
    });
});

describe('confirmPasswordSchema', () => {
    it('passes with a non-empty password', () => {
        const result = confirmPasswordSchema.safeParse({
            password: 'secret123',
        });
        expect(result.success).toBe(true);
    });

    it('fails when password is empty', () => {
        const result = confirmPasswordSchema.safeParse({ password: '' });
        expect(result.success).toBe(false);
    });
});

describe('updatePasswordSchema', () => {
    it('passes with valid current and matching new passwords', () => {
        const result = updatePasswordSchema.safeParse({
            current_password: 'oldpassword',
            password: 'newpassword',
            password_confirmation: 'newpassword',
        });
        expect(result.success).toBe(true);
    });

    it('fails when new passwords do not match', () => {
        const result = updatePasswordSchema.safeParse({
            current_password: 'oldpassword',
            password: 'newpassword',
            password_confirmation: 'different',
        });
        expect(result.success).toBe(false);
    });

    it('fails when new password is too short', () => {
        const result = updatePasswordSchema.safeParse({
            current_password: 'oldpassword',
            password: 'short',
            password_confirmation: 'short',
        });
        expect(result.success).toBe(false);
    });

    it('fails when current_password is empty', () => {
        const result = updatePasswordSchema.safeParse({
            current_password: '',
            password: 'newpassword',
            password_confirmation: 'newpassword',
        });
        expect(result.success).toBe(false);
    });
});

describe('updateProfileSchema', () => {
    it('passes with name and email', () => {
        const result = updateProfileSchema.safeParse({
            name: 'Taylor',
            email: 'taylor@example.com',
        });
        expect(result.success).toBe(true);
    });

    it('fails with name and phone only (email required in default mode)', () => {
        const result = updateProfileSchema.safeParse({
            name: 'Taylor',
            phone: '+1234567890',
        });
        expect(result.success).toBe(false);
    });

    it('fails when name is empty', () => {
        const result = updateProfileSchema.safeParse({
            name: '',
            email: 'taylor@example.com',
        });
        expect(result.success).toBe(false);
    });

    it('fails when neither email nor phone is provided', () => {
        const result = updateProfileSchema.safeParse({ name: 'Taylor' });
        expect(result.success).toBe(false);
    });

    it('fails with invalid email format', () => {
        const result = updateProfileSchema.safeParse({
            name: 'Taylor',
            email: 'not-an-email',
        });
        expect(result.success).toBe(false);
    });
});

describe('twoFactorChallengeSchema', () => {
    it('passes with a 6-digit code', () => {
        const result = twoFactorChallengeSchema.safeParse({ code: '123456' });
        expect(result.success).toBe(true);
    });

    it('passes with a recovery_code only', () => {
        const result = twoFactorChallengeSchema.safeParse({
            recovery_code: 'abcd-efgh-1234',
        });
        expect(result.success).toBe(true);
    });

    it('fails when neither code nor recovery_code is provided', () => {
        const result = twoFactorChallengeSchema.safeParse({});
        expect(result.success).toBe(false);
    });

    it('fails when both code and recovery_code are empty strings', () => {
        const result = twoFactorChallengeSchema.safeParse({
            code: '',
            recovery_code: '',
        });
        expect(result.success).toBe(false);
    });
});

describe('twoFactorConfirmSchema', () => {
    it('passes with a 6-digit numeric code', () => {
        const result = twoFactorConfirmSchema.safeParse({ code: '123456' });
        expect(result.success).toBe(true);
    });

    it('fails with fewer than 6 digits', () => {
        const result = twoFactorConfirmSchema.safeParse({ code: '123' });
        expect(result.success).toBe(false);
    });

    it('fails with non-numeric code', () => {
        const result = twoFactorConfirmSchema.safeParse({ code: 'abcdef' });
        expect(result.success).toBe(false);
    });

    it('fails with empty code', () => {
        const result = twoFactorConfirmSchema.safeParse({ code: '' });
        expect(result.success).toBe(false);
    });
});

describe('ENDPOINTS passkey entries', () => {
    it('includes passkeyLoginOptions', () => {
        expect(ENDPOINTS).toHaveProperty(
            'passkeyLoginOptions',
            '/passkeys/login/options',
        );
    });

    it('includes passkeyLogin', () => {
        expect(ENDPOINTS).toHaveProperty('passkeyLogin', '/passkeys/login');
    });

    it('includes passkeyConfirmOptions', () => {
        expect(ENDPOINTS).toHaveProperty(
            'passkeyConfirmOptions',
            '/passkeys/confirm/options',
        );
    });

    it('includes passkeyConfirm', () => {
        expect(ENDPOINTS).toHaveProperty('passkeyConfirm', '/passkeys/confirm');
    });

    it('includes passkeyRegisterOptions', () => {
        expect(ENDPOINTS).toHaveProperty(
            'passkeyRegisterOptions',
            '/user/passkeys/options',
        );
    });

    it('includes passkeyRegister', () => {
        expect(ENDPOINTS).toHaveProperty('passkeyRegister', '/user/passkeys');
    });

    it('includes passkeyDestroy', () => {
        expect(ENDPOINTS).toHaveProperty('passkeyDestroy', '/user/passkeys');
    });

    it('includes passkeyList', () => {
        expect(ENDPOINTS).toHaveProperty('passkeyList', '/user/passkeys');
    });
});

describe('PASSKEY_LOGIN_ROUTES', () => {
    it('options points to passkeyLoginOptions endpoint', () => {
        expect(PASSKEY_LOGIN_ROUTES.options).toBe(
            ENDPOINTS.passkeyLoginOptions,
        );
    });

    it('submit points to passkeyLogin endpoint', () => {
        expect(PASSKEY_LOGIN_ROUTES.submit).toBe(ENDPOINTS.passkeyLogin);
    });
});

describe('PASSKEY_CONFIRM_ROUTES', () => {
    it('options points to passkeyConfirmOptions endpoint', () => {
        expect(PASSKEY_CONFIRM_ROUTES.options).toBe(
            ENDPOINTS.passkeyConfirmOptions,
        );
    });

    it('submit points to passkeyConfirm endpoint', () => {
        expect(PASSKEY_CONFIRM_ROUTES.submit).toBe(ENDPOINTS.passkeyConfirm);
    });
});

describe('PASSKEY_REGISTER_ROUTES', () => {
    it('options points to passkeyRegisterOptions endpoint', () => {
        expect(PASSKEY_REGISTER_ROUTES.options).toBe(
            ENDPOINTS.passkeyRegisterOptions,
        );
    });

    it('submit points to passkeyRegister endpoint', () => {
        expect(PASSKEY_REGISTER_ROUTES.submit).toBe(ENDPOINTS.passkeyRegister);
    });
});

describe('ENDPOINTS', () => {
    it('includes confirmedPasswordStatus', () => {
        expect(ENDPOINTS).toHaveProperty('confirmedPasswordStatus');
    });

    it('includes securityStatus', () => {
        expect(ENDPOINTS).toHaveProperty('securityStatus');
    });

    it('includes twoFactorEnable', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorEnable');
    });

    it('includes twoFactorConfirm', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorConfirm');
    });

    it('includes twoFactorDisable', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorDisable');
    });

    it('includes twoFactorQrCode', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorQrCode');
    });

    it('includes twoFactorSecretKey', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorSecretKey');
    });

    it('includes twoFactorRecoveryCodes', () => {
        expect(ENDPOINTS).toHaveProperty('twoFactorRecoveryCodes');
    });

    it('includes regenerateTwoFactorRecoveryCodes', () => {
        expect(ENDPOINTS).toHaveProperty('regenerateTwoFactorRecoveryCodes');
    });
});

describe('normalizeErrors', () => {
    it('returns the first message per field', () => {
        const result = normalizeErrors({
            email: ['Email is required.', 'Email must be valid.'],
            password: ['Password is too short.'],
        });
        expect(result).toEqual({
            email: 'Email is required.',
            password: 'Password is too short.',
        });
    });

    it('handles an empty errors object', () => {
        expect(normalizeErrors({})).toEqual({});
    });

    it('handles a field with an empty array', () => {
        expect(normalizeErrors({ email: [] })).toEqual({ email: '' });
    });
});

describe('createUpdateProfileSchema', () => {
    it('email mode (default) requires email', () => {
        const schema = createUpdateProfileSchema();
        const result = schema.safeParse({
            name: 'Taylor',
            phone: '+1234567890',
        });
        expect(result.success).toBe(false);
    });

    it('email mode accepts valid email without phone', () => {
        const schema = createUpdateProfileSchema('email');
        const result = schema.safeParse({
            name: 'Taylor',
            email: 'taylor@example.com',
        });
        expect(result.success).toBe(true);
    });

    it('phone mode allows phone-only profile update', () => {
        const schema = createUpdateProfileSchema('phone');
        const result = schema.safeParse({
            name: 'Taylor',
            phone: '+1234567890',
        });
        expect(result.success).toBe(true);
    });

    it('phone mode rejects missing phone', () => {
        const schema = createUpdateProfileSchema('phone');
        const result = schema.safeParse({ name: 'Taylor' });
        expect(result.success).toBe(false);
    });
});

describe('mapTwoFactorChallengeErrors', () => {
    it('remaps code error to recovery_code when on recovery tab', () => {
        const result = mapTwoFactorChallengeErrors(
            { code: 'Invalid code.' },
            'recovery',
        );
        expect(result).toEqual({ recovery_code: 'Invalid code.' });
    });

    it('leaves code error intact when on code tab', () => {
        const result = mapTwoFactorChallengeErrors(
            { code: 'Invalid code.' },
            'code',
        );
        expect(result).toEqual({ code: 'Invalid code.' });
    });

    it('does not remap code when recovery_code already present', () => {
        const result = mapTwoFactorChallengeErrors(
            { code: 'Code err.', recovery_code: 'Recovery err.' },
            'recovery',
        );
        expect(result).toEqual({
            code: 'Code err.',
            recovery_code: 'Recovery err.',
        });
    });

    it('passes through non-code errors unchanged on recovery tab', () => {
        const result = mapTwoFactorChallengeErrors(
            { message: 'Too many attempts.' },
            'recovery',
        );
        expect(result).toEqual({ message: 'Too many attempts.' });
    });
});
