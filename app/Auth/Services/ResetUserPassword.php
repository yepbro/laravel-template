<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Project-owned password reset action.
 *
 * Hashes the new password, rotates the remember token, persists the
 * user, and dispatches the PasswordReset event so any listeners
 * (e.g. session invalidation) fire correctly.
 */
class ResetUserPassword
{
    public function reset(User $user, string $password): void
    {
        $user->forceFill([
            'password'       => $password,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }
}
