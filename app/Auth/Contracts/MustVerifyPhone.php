<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

/**
 * Contract for models that support phone number verification.
 *
 * Implemented by User to allow controllers and services to interact with
 * phone verification state without depending on the concrete User class.
 */
interface MustVerifyPhone
{
    /**
     * Whether the model has a phone number on record.
     */
    public function hasPhone(): bool;

    /**
     * Whether the phone number has been verified.
     */
    public function hasVerifiedPhone(): bool;

    /**
     * Mark the phone number as verified. Returns false if there is no phone
     * or the phone is already verified; returns true on success.
     */
    public function markPhoneAsVerified(): bool;

    /**
     * Clear phone verification. Returns false if the phone was not verified.
     */
    public function markPhoneAsUnverified(): bool;
}
