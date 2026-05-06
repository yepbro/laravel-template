<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the current and new password for the authenticated password update flow.
 *
 * The current_password rule verifies the value against the authenticated user's
 * stored hash using the configured guard. The new password rules come from the
 * project-owned PasswordValidationRules trait.
 */
class UpdatePasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $guard = AuthFeatures::make()->guard();

        return [
            'current_password' => ['required', 'string', "current_password:{$guard}"],
            'password'         => $this->passwordRules(),
        ];
    }
}
