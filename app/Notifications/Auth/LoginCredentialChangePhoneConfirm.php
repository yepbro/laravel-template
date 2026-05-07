<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * Sends a signed confirmation URL via the project's SMS OTP channel (fake provider in testing).
 */
class LoginCredentialChangePhoneConfirm extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  non-empty-string  $signedConfirmationUrl  Full Laravel signed GET URL finishing the credential change.
     */
    public function __construct(
        private string $phone,
        private string $signedConfirmationUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['login-credential-sms'];
    }

    /**
     * @return array{phone: string, token: string, purpose: string}
     */
    public function toLoginCredentialSms(object $notifiable): array
    {
        return [
            'phone'   => $this->phone,
            'token'   => $this->signedConfirmationUrl,
            'purpose' => 'login_credential_change',
        ];
    }
}
