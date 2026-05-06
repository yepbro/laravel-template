<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the email field for the forgot-password (send reset link) flow.
 */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $emailField = AuthFeatures::make()->emailField();

        return [
            $emailField => ['required', 'string', 'email'],
        ];
    }
}
