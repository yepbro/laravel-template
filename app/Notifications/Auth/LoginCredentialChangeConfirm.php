<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCredentialChangeConfirm extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        private string $confirmUrl,
    ) {}

    public function confirmationUrl(): string
    {
        return $this->confirmUrl;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Confirm login email change'))
            ->line(__('Please confirm this change to your login email by clicking the button below.'))
            ->action(__('Confirm change'), $this->confirmUrl)
            ->line(__('This link will expire soon. If you did not request this change, you can ignore this message.'));
    }
}
