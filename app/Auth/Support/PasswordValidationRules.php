<?php

declare(strict_types=1);

namespace App\Auth\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Project-owned password validation rules.
 *
 * Applies Password::default() plus the 'confirmed' requirement.
 */
trait PasswordValidationRules
{
    /**
     * @return list<mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
