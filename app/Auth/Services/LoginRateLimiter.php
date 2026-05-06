<?php

declare(strict_types=1);

namespace App\Auth\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

/**
 * Project-owned login rate limiter.
 *
 * Throttle key: lowercase(identifier)|IP.
 * Threshold: 5 attempts per 60-second window.
 */
class LoginRateLimiter
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function key(string $identifier, string $ip): string
    {
        return Str::transliterate(Str::lower($identifier) . '|' . $ip);
    }

    public function tooManyAttempts(string $key): bool
    {
        return $this->limiter->tooManyAttempts($key, 5);
    }

    public function hit(string $key): void
    {
        $this->limiter->hit($key, 60);
    }

    public function clear(string $key): void
    {
        $this->limiter->clear($key);
    }

    public function availableIn(string $key): int
    {
        return $this->limiter->availableIn($key);
    }
}
