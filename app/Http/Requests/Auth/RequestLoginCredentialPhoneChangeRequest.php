<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Support\PhoneNormalizer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RequestLoginCredentialPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNormalizer::normalize($this->string('phone')->toString())]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $guard = AuthFeatures::make()->guard();

        return [
            'phone'            => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
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
            $user  = $this->user();
            $phone = $this->string('phone')->toString();

            if ($user->phone !== null && $user->phone === $phone) {
                $validator->errors()->add('phone', __('The new phone number must be different from your current number.'));
            }
        });
    }
}
