<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PhoneNormalizer;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates profile information updates for the authenticated user.
 *
 * Email and phone validation rules adapt to the configured registration_mode:
 *   - 'email' mode: email is required, phone is nullable.
 *   - 'phone' mode: phone is required, email is nullable.
 *   - 'both'  mode: both are nullable, but at least one must be present
 *     (enforced by the withValidator after hook).
 *
 * Phone is normalized (strip spaces/dashes/parens, preserve leading '+')
 * before the unique check so that "+1 (555) 111-2222" and "+15551112222"
 * are treated as the same value.
 *
 * Email is lowercased before validation when lowercase_usernames is enabled.
 */
class UpdateProfileInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $features = AuthFeatures::make();

        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNormalizer::normalize($this->string('phone')->toString())]);
        }

        if ($this->filled('email') && $features->lowercaseUsernames()) {
            $this->merge(['email' => strtolower($this->string('email')->toString())]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var User $user */
        $user     = $this->user();
        $features = AuthFeatures::make();
        $mode     = $features->registrationMode();

        $emailRules = match ($mode) {
            'email'  => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            default  => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        };

        $phoneRules = match ($mode) {
            'phone'  => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            default  => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
        };

        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => $phoneRules,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $email = $this->input('email');
            $phone = $this->input('phone');

            if (empty($email) && empty($phone)) {
                $validator->errors()->add(
                    'email',
                    'At least one of email or phone must be provided.',
                );
            }
        });
    }
}
