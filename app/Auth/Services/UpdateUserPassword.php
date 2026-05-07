<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;
use App\Notifications\Auth\PasswordChanged;
use Illuminate\Support\Facades\DB;

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

        $userId = $user->getKey();
        DB::afterCommit(static function () use ($userId): void {
            $fresh = User::query()->find($userId);
            if ($fresh instanceof User) {
                $fresh->notify(new PasswordChanged());
            }
        });
    }
}
