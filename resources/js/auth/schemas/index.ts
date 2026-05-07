import { z } from 'zod';

export const loginSchema = z.object({
    login: z.string().min(1, 'Login is required.'),
    password: z.string().min(1, 'Password is required.'),
    remember: z.boolean().optional(),
});

export type LoginData = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        name: z.string().min(1, 'Name is required.'),
        email: z
            .string()
            .email('Must be a valid email.')
            .optional()
            .or(z.literal('')),
        phone: z.string().optional(),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z
            .string()
            .min(1, 'Please confirm your password.'),
    })
    .refine((data) => Boolean(data.email) || Boolean(data.phone), {
        message: 'An email address or phone number is required.',
        path: ['email'],
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type RegisterData = z.infer<typeof registerSchema>;

export const forgotPasswordSchema = z.object({
    email: z
        .string()
        .min(1, 'Email is required.')
        .email('Must be a valid email.'),
});

export type ForgotPasswordData = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z
    .object({
        token: z.string().min(1, 'Reset token is required.'),
        email: z.string().email('Must be a valid email.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z
            .string()
            .min(1, 'Please confirm your password.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type ResetPasswordData = z.infer<typeof resetPasswordSchema>;

export const phoneVerificationSchema = z.object({
    code: z
        .string()
        .length(6, 'Code must be exactly 6 digits.')
        .regex(/^\d+$/, 'Code must contain digits only.'),
});

export type PhoneVerificationData = z.infer<typeof phoneVerificationSchema>;

export const confirmPasswordSchema = z.object({
    password: z.string().min(1, 'Password is required.'),
});

export type ConfirmPasswordData = z.infer<typeof confirmPasswordSchema>;

export const updatePasswordSchema = z
    .object({
        current_password: z.string().min(1, 'Current password is required.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z
            .string()
            .min(1, 'Please confirm your password.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type UpdatePasswordData = z.infer<typeof updatePasswordSchema>;

export const deleteAccountSchema = z.object({
    current_password: z.string().min(1, 'Current password is required.'),
});

export type DeleteAccountData = z.infer<typeof deleteAccountSchema>;

// Default: email mode (matches current backend registration_mode = 'email').
// Email is required; phone is optional.
export const updateProfileSchema = z.object({
    name: z.string().min(1, 'Name is required.'),
    email: z
        .string()
        .min(1, 'Email is required.')
        .email('Must be a valid email.'),
    phone: z.string().optional(),
});

export type UpdateProfileData = z.infer<typeof updateProfileSchema>;

const updateProfilePhoneSchema = z.object({
    name: z.string().min(1, 'Name is required.'),
    email: z
        .string()
        .email('Must be a valid email.')
        .optional()
        .or(z.literal('')),
    phone: z.string().min(1, 'Phone is required.'),
});

/**
 * Returns the correct profile update schema for the current registration mode.
 * Defaults to 'email' mode which matches the current backend configuration.
 */
export function createUpdateProfileSchema(mode: 'email' | 'phone' = 'email') {
    return mode === 'phone' ? updateProfilePhoneSchema : updateProfileSchema;
}

export const twoFactorChallengeSchema = z
    .object({
        code: z.string().optional(),
        recovery_code: z.string().optional(),
    })
    .refine((data) => Boolean(data.code) || Boolean(data.recovery_code), {
        message: 'A code or recovery code is required.',
        path: ['code'],
    });

export type TwoFactorChallengeData = z.infer<typeof twoFactorChallengeSchema>;

const sixDigitNumeric = z
    .string()
    .length(6, 'Code must be exactly 6 digits.')
    .regex(/^\d+$/, 'Code must contain digits only.');

export const twoFactorConfirmSchema = z.object({
    code: sixDigitNumeric,
});

export type TwoFactorConfirmData = z.infer<typeof twoFactorConfirmSchema>;
