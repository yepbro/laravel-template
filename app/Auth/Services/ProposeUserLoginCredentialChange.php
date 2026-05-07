<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Enums\LoginCredentialChangeType;
use App\Models\User;
use App\Models\UserLoginChangeRequest;
use Illuminate\Support\Str;

/**
 * Creates a pending login identifier change with a single-use plaintext token
 * (store only its HMAC hash). Revokes other pending rows for the same user.
 */
final class ProposeUserLoginCredentialChange
{
    public function __construct(
        private int $expiresHours = 72,
    ) {}

    /**
     * @return non-empty-string
     */
    public function proposeEmailChange(User $user, string $normalizedNewEmail): string
    {
        return $this->propose($user, LoginCredentialChangeType::Email, $normalizedNewEmail);
    }

    /**
     * @return non-empty-string
     */
    public function proposePhoneChange(User $user, string $normalizedNewPhone): string
    {
        return $this->propose($user, LoginCredentialChangeType::Phone, $normalizedNewPhone);
    }

    /**
     * @return non-empty-string
     */
    private function propose(User $user, LoginCredentialChangeType $type, string $newValue): string
    {
        $this->revokePendingForUser($user);

        $plainToken = Str::random(64);
        if ($plainToken === '') {
            throw new \RuntimeException('Login credential token generation failed.');
        }

        UserLoginChangeRequest::query()->create([
            'user_id'    => $user->id,
            'type'       => $type,
            'new_value'  => $newValue,
            'token_hash' => self::hashToken($plainToken),
            'expires_at' => now()->addHours($this->expiresHours),
        ]);

        return $plainToken;
    }

    private function revokePendingForUser(User $user): void
    {
        UserLoginChangeRequest::query()->where('user_id', $user->id)->delete();
    }

    public static function hashToken(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, (string) config('app.key'));
    }
}
