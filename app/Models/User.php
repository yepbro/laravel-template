<?php

declare(strict_types=1);

namespace App\Models;

use App\Auth\AuthFeatures;
use App\Auth\Contracts\MustVerifyPhone;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, MustVerifyPhone, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use MustVerifyEmailTrait;
    use Notifiable;
    use PasskeyAuthenticatable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'phone_verified_at'       => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password'                => 'hashed',
        ];
    }

    /**
     * Whether the user has completed 2FA setup.
     *
     * When confirmation is required (the default), the user must have both
     * a stored secret and a non-null two_factor_confirmed_at timestamp.
     * When confirmation is disabled, having a secret is sufficient.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        if ($this->two_factor_secret === null) {
            return false;
        }

        if (AuthFeatures::make()->twoFactorRequiresConfirmation()) {
            return $this->two_factor_confirmed_at instanceof Carbon;
        }

        return true;
    }

    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email !== null && $this->email_verified_at !== null;
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone !== null && $this->phone_verified_at !== null;
    }

    public function markPhoneAsVerified(): bool
    {
        if (! $this->hasPhone()) {
            return false;
        }

        if ($this->hasVerifiedPhone()) {
            return false;
        }

        $this->forceFill(['phone_verified_at' => $this->freshTimestamp()])->save();

        return true;
    }

    public function markPhoneAsUnverified(): bool
    {
        if (! $this->hasVerifiedPhone()) {
            return false;
        }

        $this->forceFill(['phone_verified_at' => null])->save();

        return true;
    }

    /**
     * Return an empty string for phone-only users so that MustVerifyEmail
     * contract methods involving the email field do not produce null type errors.
     */
    public function getEmailForVerification(): string
    {
        return $this->email ?? '';
    }

    /**
     * Override the default passkey username so phone-only users (no email)
     * get a non-empty identifier shown in authenticator UIs.
     *
     * Fallback order: email, phone, auth identifier.
     * Whitespace-only values are treated as absent.
     */
    public function getPasskeyUsername(): string
    {
        $email = trim($this->stringAttribute('email'));
        if ($email !== '') {
            return $email;
        }

        $phone = trim($this->stringAttribute('phone'));
        if ($phone !== '') {
            return $phone;
        }

        return $this->authIdentifierString();
    }

    /**
     * Override the default passkey display name so sparse users never get
     * an empty string in authenticator UIs.
     *
     * Fallback order: name, email, phone, auth identifier.
     * Whitespace-only values are treated as absent.
     */
    public function getPasskeyDisplayName(): string
    {
        $name = trim($this->stringAttribute('name'));
        if ($name !== '') {
            return $name;
        }

        $email = trim($this->stringAttribute('email'));
        if ($email !== '') {
            return $email;
        }

        $phone = trim($this->stringAttribute('phone'));
        if ($phone !== '') {
            return $phone;
        }

        return $this->authIdentifierString();
    }

    /**
     * Only send the verification notification when the feature is enabled,
     * the user has an email address, and the email is not already verified.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (! AuthFeatures::make()->emailVerificationEnabled()) {
            return;
        }

        if (! $this->hasEmail()) {
            return;
        }

        if ($this->hasVerifiedEmail()) {
            return;
        }

        $this->notify(new VerifyEmail());
    }

    private function stringAttribute(string $key): string
    {
        $value = $this->getAttribute($key);

        return is_string($value) ? $value : '';
    }

    private function authIdentifierString(): string
    {
        $identifier = $this->getAuthIdentifier();

        return is_scalar($identifier) ? (string) $identifier : '';
    }
}
