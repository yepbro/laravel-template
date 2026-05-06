<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a two-factor challenge submission.
 *
 * Accepts either a TOTP code or a recovery code but requires at least one.
 * The controller determines which field is present and routes accordingly.
 */
class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Whether the request contains a TOTP code submission.
     */
    public function hasCode(): bool
    {
        return $this->filled('code');
    }

    /**
     * Whether the request contains a recovery code submission.
     */
    public function hasRecoveryCode(): bool
    {
        return $this->filled('recovery_code');
    }
}
