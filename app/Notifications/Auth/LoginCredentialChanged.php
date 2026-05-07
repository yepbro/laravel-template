<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Auth\Enums\LoginCredentialChangeType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCredentialChanged extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        private LoginCredentialChangeType $changeType,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        if ($this->hasNonEmptyEmail($notifiable)) {
            return ['mail'];
        }

        if ($this->hasNonEmptyPhone($notifiable)) {
            return ['login-credential-sms'];
        }

        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $line = $this->changeType === LoginCredentialChangeType::Email
            ? __('Your login email was updated successfully.')
            : __('Your login phone number was updated successfully.');

        return (new MailMessage())
            ->subject(__('Login credential updated'))
            ->line($line);
    }

    /**
     * @return array{phone: string, token: string, purpose: string}
     */
    public function toLoginCredentialSms(object $notifiable): array
    {
        $phone = isset($notifiable->phone) && is_string($notifiable->phone)
            ? $notifiable->phone
            : '';

        return [
            'phone'   => $phone,
            'token'   => __('Your login phone number was updated.'),
            'purpose' => 'login_credential_changed',
        ];
    }

    private function hasNonEmptyEmail(object $notifiable): bool
    {
        if (! isset($notifiable->email)) {
            return false;
        }

        $email = $notifiable->email;

        return is_string($email) && trim($email) !== '';
    }

    private function hasNonEmptyPhone(object $notifiable): bool
    {
        if (! isset($notifiable->phone)) {
            return false;
        }

        $phone = $notifiable->phone;

        return is_string($phone) && trim($phone) !== '';
    }
}
