<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Auth\Enums\LoginCredentialChangeType;
use App\Auth\Services\ProposeUserLoginCredentialChange;
use App\Models\User;
use App\Models\UserLoginChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLoginChangeRequest>
 */
class UserLoginChangeRequestFactory extends Factory
{
    protected $model = UserLoginChangeRequest::class;

    public function definition(): array
    {
        $plain = $this->faker->sha256();

        return [
            'user_id'    => User::factory(),
            'type'       => LoginCredentialChangeType::Email,
            'new_value'  => $this->faker->unique()->safeEmail(),
            'token_hash' => self::hashToken($plain),
            'expires_at' => now()->addDay(),
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return ProposeUserLoginCredentialChange::hashToken($plainToken);
    }

    public function withPlainToken(string $plainToken): static
    {
        return $this->state(fn(array $attributes): array => [
            'token_hash' => self::hashToken($plainToken),
        ]);
    }
}
