<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Auth\AuthFeatures;
use App\Models\User;
use App\Notifications\Auth\WelcomeNewUser;
use Illuminate\Auth\Events\Registered;

/**
 * Sends a welcome email for new accounts when email verification is disabled,
 * so {@see \Illuminate\Auth\Notifications\VerifyEmail} remains the only first-touch mail in that mode.
 */
final class SendQueuedWelcomeNotification
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if (! $user->hasEmail()) {
            return;
        }

        if (AuthFeatures::make()->emailVerificationEnabled()) {
            return;
        }

        $user->notify(new WelcomeNewUser());
    }
}
