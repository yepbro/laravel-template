<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RequestLoginCredentialEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email') && AuthFeatures::make()->lowercaseUsernames()) {
            $this->merge(['email' => strtolower($this->string('email')->toString())]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $guard = AuthFeatures::make()->guard();

        return [
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string', "current_password:{$guard}"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var User $user */
            $user = $this->user();

            if ($user->email !== null && strtolower((string) $user->email) === strtolower($this->string('email')->toString())) {
                $validator->errors()->add('email', __('The new email must be different from your current email.'));
            }
        });
    }
}
