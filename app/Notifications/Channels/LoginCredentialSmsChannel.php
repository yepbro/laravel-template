<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Auth\Contracts\PhoneOtpChannel;
use Illuminate\Notifications\Notification;

/**
 * Delivers login-credential messages using {@see PhoneOtpChannel} (fake or real SMS).
 */
final class LoginCredentialSmsChannel
{
    public function __construct(private PhoneOtpChannel $otpChannel) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toLoginCredentialSms')) {
            return;
        }

        /** @var array{phone: string, token: string, purpose: string} $payload */
        $payload = $notification->toLoginCredentialSms($notifiable);

        $this->otpChannel->send(
            $payload['phone'],
            $payload['token'],
            $payload['purpose'],
        );
    }
}
