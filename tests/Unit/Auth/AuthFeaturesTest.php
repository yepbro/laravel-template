<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\AuthFeatures;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AuthFeatures -- the project-owned auth feature configuration helper.
 *
 * AuthFeatures accepts its config array via the constructor so it can be tested
 * without the Laravel framework. The static make() factory reads config('auth_features')
 * in production. All tests here exercise the helper in isolation.
 */
class AuthFeaturesTest extends TestCase
{
    /** @param array<string, mixed> $overrides */
    private function make(array $overrides = []): AuthFeatures
    {
        return new AuthFeatures(array_merge($this->baseConfig(), $overrides));
    }

    /** @return array<string, mixed> */
    private function baseConfig(): array
    {
        return [
            'guard'               => 'web',
            'passwords'           => 'users',
            'username'            => 'email',
            'email'               => 'email',
            'lowercase_usernames' => true,
            'home'                => '/home',
            'views'               => true,
            'registration_mode'   => 'email',
            'features'            => [
                'registration'                          => true,
                'reset_passwords'                       => true,
                'email_verification'                    => false,
                'phone_verification'                    => false,
                'update_profile_information'            => true,
                'update_passwords'                      => true,
                'two_factor_authentication'             => true,
                'two_factor_requires_confirmation'      => true,
                'two_factor_requires_password_confirmation' => true,
                'passkeys'                              => false,
            ],
        ];
    }

    // -- Core identity -----------------------------------------------------------

    public function test_guard_returns_configured_value(): void
    {
        $this->assertSame('web', $this->make()->guard());
        $this->assertSame('api', $this->make(['guard' => 'api'])->guard());
    }

    public function test_password_broker_returns_configured_value(): void
    {
        $this->assertSame('users', $this->make()->passwordBroker());
        $this->assertSame('admins', $this->make(['passwords' => 'admins'])->passwordBroker());
    }

    public function test_home_returns_configured_value(): void
    {
        $this->assertSame('/home', $this->make()->home());
        $this->assertSame('/dashboard', $this->make(['home' => '/dashboard'])->home());
    }

    public function test_username_returns_configured_value(): void
    {
        $this->assertSame('email', $this->make()->username());
        $this->assertSame('phone', $this->make(['username' => 'phone'])->username());
    }

    public function test_email_field_returns_configured_value(): void
    {
        $this->assertSame('email', $this->make()->emailField());
        $this->assertSame('email_address', $this->make(['email' => 'email_address'])->emailField());
    }

    public function test_lowercase_usernames_returns_configured_bool(): void
    {
        $this->assertTrue($this->make()->lowercaseUsernames());
        $this->assertFalse($this->make(['lowercase_usernames' => false])->lowercaseUsernames());
    }

    public function test_views_enabled_returns_configured_bool(): void
    {
        $this->assertTrue($this->make()->viewsEnabled());
        $this->assertFalse($this->make(['views' => false])->viewsEnabled());
    }

    public function test_password_reset_redirect_defaults_to_login(): void
    {
        $this->assertSame('login', $this->make()->passwordResetRedirect());
    }

    public function test_password_reset_redirect_returns_configured_value(): void
    {
        $this->assertSame('dashboard', $this->make(['password_reset_redirect' => 'dashboard'])->passwordResetRedirect());
    }

    // -- Feature flags -----------------------------------------------------------

    public function test_registration_enabled_matches_features_config(): void
    {
        $this->assertTrue($this->make()->registrationEnabled());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['registration' => false])])->registrationEnabled(),
        );
    }

    public function test_reset_passwords_enabled_matches_features_config(): void
    {
        $this->assertTrue($this->make()->resetPasswordsEnabled());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['reset_passwords' => false])])->resetPasswordsEnabled(),
        );
    }

    public function test_email_verification_disabled_by_default(): void
    {
        $this->assertFalse($this->make()->emailVerificationEnabled());
    }

    public function test_email_verification_enabled_when_configured(): void
    {
        $features = $this->make(['features' => array_merge($this->baseConfig()['features'], ['email_verification' => true])]);
        $this->assertTrue($features->emailVerificationEnabled());
    }

    public function test_update_profile_information_enabled_matches_features_config(): void
    {
        $this->assertTrue($this->make()->updateProfileInformationEnabled());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['update_profile_information' => false])])->updateProfileInformationEnabled(),
        );
    }

    public function test_update_passwords_enabled_matches_features_config(): void
    {
        $this->assertTrue($this->make()->updatePasswordsEnabled());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['update_passwords' => false])])->updatePasswordsEnabled(),
        );
    }

    public function test_two_factor_enabled_matches_features_config(): void
    {
        $this->assertTrue($this->make()->twoFactorEnabled());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['two_factor_authentication' => false])])->twoFactorEnabled(),
        );
    }

    public function test_two_factor_requires_confirmation_matches_features_config(): void
    {
        $this->assertTrue($this->make()->twoFactorRequiresConfirmation());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['two_factor_requires_confirmation' => false])])->twoFactorRequiresConfirmation(),
        );
    }

    public function test_two_factor_requires_password_confirmation_matches_features_config(): void
    {
        $this->assertTrue($this->make()->twoFactorRequiresPasswordConfirmation());
        $this->assertFalse(
            $this->make(['features' => array_merge($this->baseConfig()['features'], ['two_factor_requires_password_confirmation' => false])])->twoFactorRequiresPasswordConfirmation(),
        );
    }

    public function test_phone_verification_disabled_by_default(): void
    {
        $this->assertFalse($this->make()->phoneVerificationEnabled());
    }

    public function test_phone_verification_enabled_when_configured(): void
    {
        $features = $this->make(['features' => array_merge($this->baseConfig()['features'], ['phone_verification' => true])]);
        $this->assertTrue($features->phoneVerificationEnabled());
    }

    public function test_passkeys_disabled_by_default(): void
    {
        $this->assertFalse($this->make()->passkeysEnabled());
    }

    public function test_passkeys_enabled_when_configured(): void
    {
        $features = $this->make(['features' => array_merge($this->baseConfig()['features'], ['passkeys' => true])]);
        $this->assertTrue($features->passkeysEnabled());
    }

    // -- Registration mode -------------------------------------------------------

    public function test_registration_mode_defaults_to_email(): void
    {
        $this->assertSame('email', $this->make()->registrationMode());
    }

    public function test_registration_mode_returns_configured_value(): void
    {
        $this->assertSame('phone', $this->make(['registration_mode' => 'phone'])->registrationMode());
        $this->assertSame('both', $this->make(['registration_mode' => 'both'])->registrationMode());
    }

    public function test_allows_email_registration_in_email_mode(): void
    {
        $this->assertTrue($this->make(['registration_mode' => 'email'])->allowsEmailRegistration());
        $this->assertTrue($this->make(['registration_mode' => 'both'])->allowsEmailRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'phone'])->allowsEmailRegistration());
    }

    public function test_allows_phone_registration_in_phone_mode(): void
    {
        $this->assertTrue($this->make(['registration_mode' => 'phone'])->allowsPhoneRegistration());
        $this->assertTrue($this->make(['registration_mode' => 'both'])->allowsPhoneRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'email'])->allowsPhoneRegistration());
    }

    public function test_requires_email_for_registration_only_in_strict_email_mode(): void
    {
        $this->assertTrue($this->make(['registration_mode' => 'email'])->requiresEmailForRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'both'])->requiresEmailForRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'phone'])->requiresEmailForRegistration());
    }

    public function test_requires_phone_for_registration_only_in_strict_phone_mode(): void
    {
        $this->assertTrue($this->make(['registration_mode' => 'phone'])->requiresPhoneForRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'both'])->requiresPhoneForRegistration());
        $this->assertFalse($this->make(['registration_mode' => 'email'])->requiresPhoneForRegistration());
    }

    // -- Defaults when config keys are absent ------------------------------------

    public function test_sensible_defaults_with_empty_config(): void
    {
        $features = new AuthFeatures([]);

        $this->assertSame('web', $features->guard());
        $this->assertSame('users', $features->passwordBroker());
        $this->assertSame('/home', $features->home());
        $this->assertSame('email', $features->username());
        $this->assertSame('email', $features->emailField());
        $this->assertTrue($features->lowercaseUsernames());
        $this->assertTrue($features->viewsEnabled());
        $this->assertTrue($features->registrationEnabled());
        $this->assertTrue($features->resetPasswordsEnabled());
        $this->assertFalse($features->emailVerificationEnabled());
        $this->assertFalse($features->phoneVerificationEnabled());
        $this->assertTrue($features->updateProfileInformationEnabled());
        $this->assertTrue($features->updatePasswordsEnabled());
        $this->assertTrue($features->twoFactorEnabled());
        $this->assertTrue($features->twoFactorRequiresConfirmation());
        $this->assertTrue($features->twoFactorRequiresPasswordConfirmation());
        $this->assertFalse($features->passkeysEnabled());
        $this->assertSame('email', $features->registrationMode());
        $this->assertTrue($features->allowsEmailRegistration());
        $this->assertFalse($features->allowsPhoneRegistration());
        $this->assertTrue($features->requiresEmailForRegistration());
        $this->assertFalse($features->requiresPhoneForRegistration());
        $this->assertSame('login', $features->passwordResetRedirect());
    }
}
