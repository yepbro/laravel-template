<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\AuthFeatures;
use App\Models\User;

/**
 * Applies a validated profile information update to the given user.
 *
 * When the email address changes, email_verified_at is cleared and a new
 * verification notification is dispatched if the email_verification feature
 * is enabled.
 *
 * When the phone number changes, phone_verified_at is cleared. No OTP is
 * automatically sent; verification is the caller's responsibility.
 *
 * Unchanged identifiers keep their existing verification timestamps.
 */
class UpdateUserProfileInformation
{
    public function update(User $user, string $name, ?string $email, ?string $phone): void
    {
        $features = AuthFeatures::make();

        $emailChanged = $email !== $user->email;
        $phoneChanged = $phone !== $user->phone;

        $attributes = [
            'name'  => $name,
            'email' => $email,
            'phone' => $phone,
        ];

        if ($emailChanged) {
            $attributes['email_verified_at'] = null;
        }

        if ($phoneChanged) {
            $attributes['phone_verified_at'] = null;
        }

        $user->forceFill($attributes)->save();

        if ($emailChanged && $features->emailVerificationEnabled() && $user->hasEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
