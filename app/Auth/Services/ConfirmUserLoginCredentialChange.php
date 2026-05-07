<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\AuthFeatures;
use App\Auth\Enums\LoginCredentialChangeType;
use App\Models\User;
use App\Models\UserLoginChangeRequest;
use App\Notifications\Auth\LoginCredentialChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies a confirmed login identifier change.
 *
 * Email: when email verification is disabled the signed confirmation URL is
 * treated as proof of ownership and `email_verified_at` is set immediately.
 * When verification is enabled the field is cleared and a standard VerifyEmail
 * notification is dispatched so the user completes the verification flow.
 *
 * Phone: `phone_verified_at` is always cleared (phone OTP re-verification is
 * handled separately via the phone verification flow).
 */
final class ConfirmUserLoginCredentialChange
{
    public function confirm(string $plainToken): void
    {
        $hash = ProposeUserLoginCredentialChange::hashToken($plainToken);

        $notifyUserId  = null;
        $notifyType    = null;

        DB::transaction(function () use ($hash, &$notifyUserId, &$notifyType): void {
            /** @var UserLoginChangeRequest|null $row */
            $row = UserLoginChangeRequest::query()
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw ValidationException::withMessages([
                    'token' => [__('This confirmation link is invalid or has expired.')],
                ]);
            }

            if ($row->isExpired()) {
                $row->delete();
                throw ValidationException::withMessages([
                    'token' => [__('This confirmation link is invalid or has expired.')],
                ]);
            }

            /** @var User|null $user */
            $user = User::query()->whereKey($row->user_id)->lockForUpdate()->first();

            if ($user === null) {
                $row->delete();
                throw ValidationException::withMessages([
                    'token' => [__('This confirmation link is invalid or has expired.')],
                ]);
            }

            if ($row->type === LoginCredentialChangeType::Email) {
                $verificationEnabled = AuthFeatures::make()->emailVerificationEnabled();

                $user->forceFill([
                    'email'             => $row->new_value,
                    'email_verified_at' => $verificationEnabled ? null : now(),
                ])->save();

                if ($verificationEnabled && $user->hasEmail()) {
                    $user->sendEmailVerificationNotification();
                }
            } elseif ($row->type === LoginCredentialChangeType::Phone) {
                $user->forceFill([
                    'phone'             => $row->new_value,
                    'phone_verified_at' => null,
                ])->save();
            }

            UserLoginChangeRequest::query()->where('user_id', $user->id)->delete();

            $notifyUserId = $user->getKey();
            $notifyType   = $row->type;
        });

        $user = $notifyUserId !== null ? User::query()->find($notifyUserId) : null;

        if ($user instanceof User && $notifyType !== null) {
            $user->notify(new LoginCredentialChanged($notifyType));
        }
    }
}
