<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

/**
 * Contract for the phone OTP delivery channel.
 *
 * Implement this to add a real SMS provider. The FakePhoneOtpChannel
 * implementation is used in tests and as the default container binding
 * until a real provider is configured.
 */
interface PhoneOtpChannel
{
    /**
     * Deliver the one-time code to the given phone number.
     *
     * @param string $phone   Normalized E.164-style phone number.
     * @param string $code    Plaintext OTP code (do not log or persist raw).
     * @param string $purpose Logical purpose, e.g. 'verification' or 'recovery'.
     */
    public function send(string $phone, string $code, string $purpose): void;
}
