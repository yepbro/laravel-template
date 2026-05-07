<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Auth\Enums\LoginCredentialChangeType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCredentialChangeRequested extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        private LoginCredentialChangeType $changeType,
        private string $newValue,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $line = $this->changeType === LoginCredentialChangeType::Email
            ? __('A request was submitted to change your login email. The new address will be :value. The change takes effect only after confirmation.', ['value' => $this->newValue])
            : __('A request was submitted to change your login phone number. The new number will be :value. The change takes effect only after confirmation.', ['value' => $this->newValue]);

        return (new MailMessage())
            ->subject(__('Login credential change requested'))
            ->line($line)
            ->line(__('If you did not request this change, secure your account immediately.'));
    }
}
