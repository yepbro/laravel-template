<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PasswordValidationRules;
use App\Auth\Support\PhoneNormalizer;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates and normalizes incoming registration data.
 *
 * Required fields vary by registration mode from config/auth_features.php:
 *   - 'email' : name, email, password (+confirmation); phone ignored.
 *   - 'phone' : name, phone, password (+confirmation); email ignored.
 *   - 'both'  : name, email, phone, password (+confirmation).
 *
 * Normalization happens in prepareForValidation() so that uniqueness checks
 * run against the stored form of each credential.
 */
class RegisterRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $features = AuthFeatures::make();
        $mode = $features->registrationMode();

        $merge = [];

        // Off-mode fields are forced to null so they bypass uniqueness checks
        // and are never persisted -- a submitted duplicate off-mode value must
        // not produce a 422.
        if ($mode === 'phone') {
            $merge['email'] = null;
        } else {
            $email = $this->input('email');
            if (is_string($email) && $email !== '') {
                $merge['email'] = $features->lowercaseUsernames() ? strtolower($email) : $email;
            } else {
                $merge['email'] = null;
            }
        }

        if ($mode === 'email') {
            $merge['phone'] = null;
        } else {
            $phone = $this->input('phone');
            if (is_string($phone) && $phone !== '') {
                $merge['phone'] = PhoneNormalizer::normalize($phone);
            } else {
                $merge['phone'] = null;
            }
        }

        $this->merge($merge);
    }

    public function withValidator(Validator $validator): void
    {
        $mode = AuthFeatures::make()->registrationMode();

        if (! in_array($mode, ['email', 'phone', 'both'], true)) {
            $validator->after(function (Validator $v) use ($mode): void {
                $v->errors()->add('email', "Registration mode '{$mode}' is not valid.");
            });
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $mode = AuthFeatures::make()->registrationMode();

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => $this->emailRules($mode),
            'phone'    => $this->phoneRules($mode),
            'password' => $this->passwordRules(),
        ];
    }

    /** @return list<mixed> */
    private function emailRules(string $mode): array
    {
        if ($mode === 'email' || $mode === 'both') {
            return ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')];
        }

        return ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->whereNotNull('email')];
    }

    /** @return list<mixed> */
    private function phoneRules(string $mode): array
    {
        if ($mode === 'phone' || $mode === 'both') {
            return ['required', 'string', 'max:30', Rule::unique(User::class, 'phone')];
        }

        return ['nullable', 'string', 'max:30', Rule::unique(User::class, 'phone')->whereNotNull('phone')];
    }
}
