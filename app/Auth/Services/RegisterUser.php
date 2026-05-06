<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Project-owned user registration service.
 *
 * Accepts clean, pre-validated data (normalization and validation live in
 * RegisterRequest) and persists a new User.
 * contracts.
 */
class RegisterUser
{
    public function register(
        string $name,
        ?string $email,
        ?string $phone,
        string $password,
    ): User {
        if ($email === null && $phone === null) {
            throw new \InvalidArgumentException('At least one of email or phone must be provided.');
        }

        return User::create([
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => Hash::make($password),
        ]);
    }
}
