import { describe, expect, it } from 'vitest';

import {
    ENDPOINTS,
    mapTwoFactorChallengeErrors,
    normalizeErrors,
} from '@/auth/api/client';

describe('normalizeErrors', () => {
    it('picks the first message per Laravel field payload', () => {
        expect(
            normalizeErrors({
                email: ['Taken.', 'Duplicate.'],
                name: [],
            }),
        ).toStrictEqual({
            email: 'Taken.',
            name: '',
        });
    });

    it('handles an empty payload', () => {
        expect(normalizeErrors({})).toStrictEqual({});
    });
});

describe('mapTwoFactorChallengeErrors', () => {
    it('copies backend code errors onto recovery_code during recovery submission', () => {
        expect(
            mapTwoFactorChallengeErrors({ code: 'Invalid.' }, 'recovery'),
        ).toStrictEqual({
            recovery_code: 'Invalid.',
        });
    });

    it('leaves payloads unchanged on the otp tab', () => {
        const errors = { code: 'Bad.' };

        expect(mapTwoFactorChallengeErrors(errors, 'code')).toStrictEqual(
            errors,
        );
    });

    it('does not remap when recovery_code already has an opinion', () => {
        expect(
            mapTwoFactorChallengeErrors(
                {
                    code: 'x',
                    recovery_code: 'Recovery failed.',
                },
                'recovery',
            ),
        ).toStrictEqual({
            code: 'x',
            recovery_code: 'Recovery failed.',
        });
    });

    it('does not remap for recovery tab without a standalone code error', () => {
        const errors = { recovery_code: 'Only recovery.' };

        expect(mapTwoFactorChallengeErrors(errors, 'recovery')).toStrictEqual(
            errors,
        );
    });
});

describe('auth API endpoint map', () => {
    it('uses /user for current session reads and deletes', () => {
        expect(ENDPOINTS.currentUser).toBe('/user');
        expect(ENDPOINTS.deleteAccount).toBe('/user');
    });
});
