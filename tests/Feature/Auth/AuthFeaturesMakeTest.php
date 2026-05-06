<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\AuthFeatures;
use Tests\TestCase;

/**
 * Laravel-backed tests for AuthFeatures::make().
 *
 * Verifies that the static factory correctly reads from config('auth_features').
 * Pure constructor tests live in tests/Unit/Auth/AuthFeaturesTest.php and do
 * not require the framework.
 */
class AuthFeaturesMakeTest extends TestCase
{
    public function test_make_returns_instance_populated_from_config(): void
    {
        $features = AuthFeatures::make();

        $this->assertSame(config('auth_features.guard'), $features->guard());
        $this->assertSame(config('auth_features.passwords'), $features->passwordBroker());
        $this->assertSame(config('auth_features.home'), $features->home());
        $this->assertSame(config('auth_features.username'), $features->username());
        $this->assertSame(config('auth_features.email'), $features->emailField());
        $this->assertSame(config('auth_features.registration_mode'), $features->registrationMode());
    }

    public function test_make_reflects_feature_flags_from_config(): void
    {
        $features = AuthFeatures::make();

        $this->assertSame(
            (bool) config('auth_features.features.email_verification'),
            $features->emailVerificationEnabled(),
        );
        $this->assertSame(
            (bool) config('auth_features.features.passkeys'),
            $features->passkeysEnabled(),
        );
        $this->assertSame(
            (bool) config('auth_features.features.registration'),
            $features->registrationEnabled(),
        );
    }

    public function test_make_reflects_runtime_config_override(): void
    {
        config(['auth_features.guard' => 'api']);

        $this->assertSame('api', AuthFeatures::make()->guard());
    }

    public function test_make_reflects_password_reset_redirect_from_config(): void
    {
        config(['auth_features.password_reset_redirect' => 'register']);

        $this->assertSame('register', AuthFeatures::make()->passwordResetRedirect());
    }
}
