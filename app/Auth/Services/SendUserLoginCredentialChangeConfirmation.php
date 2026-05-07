<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Enums\LoginCredentialChangeType;
use App\Models\User;
use App\Notifications\Auth\LoginCredentialChangeConfirm;
use App\Notifications\Auth\LoginCredentialChangePhoneConfirm;
use App\Notifications\Auth\LoginCredentialChangeRequested;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Dispatches queued notifications / channel deliveries for a pending login change.
 */
final class SendUserLoginCredentialChangeConfirmation
{
    /**
     * @param  non-empty-string  $plainToken
     */
    public function sendForEmailChange(User $user, string $plainToken, string $normalizedNewEmail): void
    {
        $confirmUrl = $this->signedConfirmUrl($plainToken);

        if (self::nonEmptyString($user->email)) {
            $user->notify(new LoginCredentialChangeRequested(LoginCredentialChangeType::Email, $normalizedNewEmail));
        }

        Notification::route('mail', $normalizedNewEmail)
            ->notify(new LoginCredentialChangeConfirm($confirmUrl));

        $old = strtolower(trim((string) $user->email));
        $new = strtolower($normalizedNewEmail);

        if ($old !== '' && $old !== $new) {
            Notification::route('mail', $normalizedNewEmail)
                ->notify(new LoginCredentialChangeRequested(LoginCredentialChangeType::Email, $normalizedNewEmail));
        }
    }

    /**
     * @param  non-empty-string  $plainToken
     */
    public function sendForPhoneChange(User $user, string $plainToken, string $normalizedNewPhone): void
    {
        $signedUrl = $this->signedConfirmUrl($plainToken);

        if (self::nonEmptyString($user->email)) {
            $user->notify(new LoginCredentialChangeRequested(LoginCredentialChangeType::Phone, $normalizedNewPhone));
        }

        $user->notify(new LoginCredentialChangePhoneConfirm($normalizedNewPhone, $signedUrl));
    }

    /**
     * @return non-empty-string
     */
    private function signedConfirmUrl(string $plainToken): string
    {
        $url = URL::temporarySignedRoute(
            'user.login-credentials.confirm',
            now()->addHours(72),
            ['token' => $plainToken],
        );

        if ($url === '') {
            throw new \RuntimeException('Signed confirmation URL must not be empty.');
        }

        return $url;
    }

    private static function nonEmptyString(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
