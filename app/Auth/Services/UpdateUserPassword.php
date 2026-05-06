<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;

/**
 * Authenticated password update action.
 *
 * Only changes the password. Does not rotate remember_token and does not
 * dispatch the PasswordReset event - those side effects belong to the
 * unauthenticated token-based reset flow (ResetUserPassword).
 */
class UpdateUserPassword
{
    public function update(User $user, string $password): void
    {
        $user->password = $password;
        $user->save();
    }
}
