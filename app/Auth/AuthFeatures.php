<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Project-owned helper for querying auth feature flags and configuration values.
 *
 * Reads from config/auth_features.php via the static make() factory. The
 * constructor accepts the raw config array so tests can drive the helper
 * without the Laravel container.
 */
class AuthFeatures
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    /**
     * Build an instance from the application config.
     */
    public static function make(): self
    {
        /** @var array<string, mixed> $config */
        $config = config('auth_features', []);

        return new self($config);
    }

    // -- Core identity -----------------------------------------------------------

    public function guard(): string
    {
        return $this->stringConfig('guard', 'web');
    }

    public function passwordBroker(): string
    {
        return $this->stringConfig('passwords', 'users');
    }

    public function home(): string
    {
        return $this->stringConfig('home', '/home');
    }

    public function passwordResetRedirect(): string
    {
        return $this->stringConfig('password_reset_redirect', 'login');
    }

    public function username(): string
    {
        return $this->stringConfig('username', 'email');
    }

    public function emailField(): string
    {
        return $this->stringConfig('email', 'email');
    }

    public function lowercaseUsernames(): bool
    {
        return (bool) ($this->config['lowercase_usernames'] ?? true);
    }

    public function viewsEnabled(): bool
    {
        return (bool) ($this->config['views'] ?? true);
    }

    // -- Feature flags -----------------------------------------------------------

    public function registrationEnabled(): bool
    {
        return $this->feature('registration', true);
    }

    public function resetPasswordsEnabled(): bool
    {
        return $this->feature('reset_passwords', true);
    }

    public function emailVerificationEnabled(): bool
    {
        return $this->feature('email_verification', false);
    }

    public function updateProfileInformationEnabled(): bool
    {
        return $this->feature('update_profile_information', true);
    }

    public function updatePasswordsEnabled(): bool
    {
        return $this->feature('update_passwords', true);
    }

    public function twoFactorEnabled(): bool
    {
        return $this->feature('two_factor_authentication', true);
    }

    public function twoFactorRequiresConfirmation(): bool
    {
        return $this->feature('two_factor_requires_confirmation', true);
    }

    public function twoFactorRequiresPasswordConfirmation(): bool
    {
        return $this->feature('two_factor_requires_password_confirmation', true);
    }

    public function phoneVerificationEnabled(): bool
    {
        return $this->feature('phone_verification', false);
    }

    public function passkeysEnabled(): bool
    {
        return $this->feature('passkeys', false);
    }

    // -- Registration mode -------------------------------------------------------

    /**
     * Returns the registration credential mode: 'email', 'phone', or 'both'.
     */
    public function registrationMode(): string
    {
        return $this->stringConfig('registration_mode', 'email');
    }

    /**
     * Whether users may register with an email address.
     */
    public function allowsEmailRegistration(): bool
    {
        return in_array($this->registrationMode(), ['email', 'both'], true);
    }

    /**
     * Whether users may register with a phone number.
     */
    public function allowsPhoneRegistration(): bool
    {
        return in_array($this->registrationMode(), ['phone', 'both'], true);
    }

    /**
     * Whether email is the sole accepted registration credential (not both / phone).
     */
    public function requiresEmailForRegistration(): bool
    {
        return $this->registrationMode() === 'email';
    }

    /**
     * Whether phone is the sole accepted registration credential (not both / email).
     */
    public function requiresPhoneForRegistration(): bool
    {
        return $this->registrationMode() === 'phone';
    }

    // -- Internal ----------------------------------------------------------------

    private function feature(string $key, bool $default): bool
    {
        /** @var array<string, mixed> $features */
        $features = $this->config['features'] ?? [];

        return (bool) ($features[$key] ?? $default);
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->config[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
