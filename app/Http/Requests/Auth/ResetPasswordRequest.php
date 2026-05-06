<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the token, email, and new password for the reset-password flow.
 *
 * Password rules come from the project-owned PasswordValidationRules trait,
 * which applies Password::default() plus the 'confirmed' requirement.
 */
class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $emailField = AuthFeatures::make()->emailField();

        return [
            'token'      => ['required', 'string'],
            $emailField  => ['required', 'string', 'email'],
            'password'   => $this->passwordRules(),
        ];
    }
}
