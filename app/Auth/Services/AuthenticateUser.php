<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Project-owned authentication service.
 *
 * Accepts a pre-normalized identifier (email or phone) and a plain-text
 * password. Does not use Auth::attempt() to avoid issues with nullable fields
 * when authenticating by phone.
 */
class AuthenticateUser
{
    /**
     * Find the user by normalized identifier and verify the password.
     *
     * Returns the User on success, null on any failure (unknown user or bad password).
     */
    public function attempt(string $normalizedIdentifier, string $password): ?User
    {
        $user = $this->findByIdentifier($normalizedIdentifier);

        if ($user === null) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    private function findByIdentifier(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return User::query()->where('email', $identifier)->first();
        }

        return User::query()->where('phone', $identifier)->first();
    }
}
