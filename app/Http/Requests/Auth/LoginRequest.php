<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and normalizes login credentials.
 *
 * Accepts a 'login' field (email or phone). Falls back to 'email' when 'login'
 * is absent for backward compatibility.
 *
 * Normalization in prepareForValidation():
 *   - Email (contains '@'): trim, then lowercase when lowercase_usernames enabled.
 *   - Phone: normalize via PhoneNormalizer (strips spaces, hyphens, parentheses).
 *
 * The normalized value is always written back to 'login' so validation rules and
 * controller code read from a single consistent field.
 *
 * credentialKey() returns 'email' when the caller sent 'email' with no 'login'
 * (backward-compatible fallback), and 'login' otherwise. The fallback flag is set
 * before any early return so it is reliable even for empty or non-string values.
 * Controllers use this to key validation errors on the field the caller sent.
 */
class LoginRequest extends FormRequest
{
    /**
     * True when the original request had no 'login' field and fell back to 'email'.
     * Set before any early return in prepareForValidation() so even an empty or
     * non-string 'email' value causes credentialKey() to return 'email'.
     */
    private bool $emailFallback = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('login') ?? $this->input('email');

        // Set fallback flag before any early return so credentialKey() returns
        // 'email' even when the submitted value is empty or non-string.
        if (! $this->has('login') && $this->has('email')) {
            $this->emailFallback = true;
        }

        if (! is_string($raw) || $raw === '') {
            return;
        }

        $features = AuthFeatures::make();

        if (str_contains($raw, '@')) {
            $normalized = trim($raw);

            if ($features->lowercaseUsernames()) {
                $normalized = strtolower($normalized);
            }
        } else {
            $normalized = PhoneNormalizer::normalize($raw);
        }

        $this->merge(['login' => $normalized]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        // When the caller submitted 'email' with no 'login', validate 'email'
        // so that required/string errors are keyed on the field they actually sent.
        // The controller still reads from 'login' (merged in prepareForValidation).
        $identifierField = $this->emailFallback ? 'email' : 'login';

        return [
            $identifierField => ['required', 'string'],
            'password'       => ['required', 'string'],
        ];
    }

    /**
     * Returns the request field name to use when keying validation errors.
     *
     * Returns 'email' when the caller sent 'email' without 'login' (backward-
     * compatible fallback), so error messages land on the field they actually
     * submitted. Returns 'login' in all other cases.
     */
    public function credentialKey(): string
    {
        return $this->emailFallback ? 'email' : 'login';
    }
}
