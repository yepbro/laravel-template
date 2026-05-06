<?php

declare(strict_types=1);

namespace App\Auth\Channels;

use App\Auth\Contracts\PhoneOtpChannel;

/**
 * In-memory phone OTP channel for use in tests and local development.
 *
 * Bind this as a singleton via $app->instance(PhoneOtpChannel::class, new FakePhoneOtpChannel())
 * in test setUp methods. Each test gets a fresh instance because the singleton is
 * registered against a new application instance per test.
 *
 * Never use this in production with a real SMS provider -- swap the binding in
 * AppServiceProvider to a concrete channel class once a provider is chosen.
 */
final class FakePhoneOtpChannel implements PhoneOtpChannel
{
    /** @var list<array{phone: string, code: string, purpose: string}> */
    private array $sent = [];

    public function send(string $phone, string $code, string $purpose): void
    {
        $this->sent[] = ['phone' => $phone, 'code' => $code, 'purpose' => $purpose];
    }

    /**
     * Returns all recorded send calls.
     *
     * @return list<array{phone: string, code: string, purpose: string}>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    /**
     * Returns the last OTP code delivered for the given purpose (and optional phone).
     * Returns null if no matching entry exists.
     */
    public function lastCode(?string $phone = null, string $purpose = 'verification'): ?string
    {
        $matches = array_filter(
            $this->sent,
            static fn(array $entry): bool
                => $entry['purpose'] === $purpose && ($phone === null || $entry['phone'] === $phone),
        );

        if ($matches === []) {
            return null;
        }

        return end($matches)['code'];
    }

    /**
     * Clear all recorded sends. Call this in tearDown if a single FakePhoneOtpChannel
     * instance is shared across multiple tests in the same class.
     */
    public function reset(): void
    {
        $this->sent = [];
    }
}
