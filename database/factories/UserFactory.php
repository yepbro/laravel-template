<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     * Backward-compatible: leaves email populated, only clears email_verified_at.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Add a phone number. Generates a random E.164-style number when omitted.
     */
    public function withPhone(?string $phone = null): static
    {
        return $this->state(fn(array $attributes) => [
            'phone' => $phone ?? '+1555' . fake()->unique()->numerify('#######'),
        ]);
    }

    /**
     * Mark the phone number as verified. Generates a phone number if one is not already set.
     */
    public function phoneVerified(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone' => $attributes['phone'] ?? '+1555' . fake()->unique()->numerify('#######'),
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Mark the phone number as unverified.
     */
    public function phoneUnverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    /**
     * User identified by email only - no phone number.
     */
    public function emailOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
     * User identified by phone only - email is null.
     */
    public function phoneOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'email' => null,
            'email_verified_at' => null,
            'phone' => $attributes['phone'] ?? '+1555' . fake()->unique()->numerify('#######'),
        ]);
    }

    /**
     * User identified by both email and phone.
     */
    public function emailAndPhone(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone' => $attributes['phone'] ?? '+1555' . fake()->unique()->numerify('#######'),
        ]);
    }
}
