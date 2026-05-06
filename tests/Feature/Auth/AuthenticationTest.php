<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\TwoFactor\RecoveryCodeManager;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmedTwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Feature tests for AuthenticatedSessionController (login/logout) via test-only routes.
 *
 * Tests mount the project-owned controller at /_auth-test/login and
 * /_auth-test/logout for isolation (avoids middleware differences from the
 * production throttle:login route during fine-grained rate-limiter assertions).
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URI  = '/_auth-test/login';
    private const LOGOUT_URI = '/_auth-test/logout';

    /** Default plain-text password matching the UserFactory default hash. */
    private const PASSWORD = 'password';

    private const ENABLE_URI  = '/_auth-test/user/two-factor-authentication';
    private const CONFIRM_URI = '/_auth-test/user/confirmed-two-factor-authentication';

    protected function setUp(): void
    {
        parent::setUp();

        Route::post(self::LOGIN_URI, [AuthenticatedSessionController::class, 'store'])
            ->middleware(['web']);

        $guard = config('auth_features.guard', 'web');
        Route::post(self::LOGOUT_URI, [AuthenticatedSessionController::class, 'destroy'])
            ->middleware(['web', "auth:{$guard}"]);

        Route::post(self::ENABLE_URI, [TwoFactorAuthenticationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm']);

        Route::post(self::CONFIRM_URI, [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}"]);

        $this->clearRateLimitFor('test@example.com');
        $this->clearRateLimitFor('+15551234567');
    }

    // -- Helpers for 2FA tests ---------------------------------------------------

    /** @return array<string, mixed> */
    private function passwordConfirmedSession(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    private function enableAndConfirmTwoFactor(User $user): string
    {
        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();
        $plainSecret = Crypt::decryptString((string) $user->two_factor_secret);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $this->actingAs($user)
            ->postJson(self::CONFIRM_URI, ['code' => $validCode]);

        $user->refresh();

        // Clear the actingAs auth state so subsequent login tests start as guests.
        Auth::logout();

        return $plainSecret;
    }

    // -- Helpers -----------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function loginPayload(array $overrides = []): array
    {
        return array_merge([
            'login'    => 'test@example.com',
            'password' => self::PASSWORD,
        ], $overrides);
    }

    private function clearRateLimitFor(string $identifier): void
    {
        RateLimiter::clear($this->rateLimiterKey($identifier));
    }

    private function rateLimiterKey(string $identifier): string
    {
        return Str::transliterate(strtolower($identifier) . '|127.0.0.1');
    }

    // -- Email login -------------------------------------------------------------

    public function test_email_login_success_returns_200_json_and_authenticates(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Phone login -------------------------------------------------------------

    public function test_phone_login_success_returns_200_json_and_authenticates(): void
    {
        User::factory()->phoneOnly()->create(['phone' => '+15551234567']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'login' => '+15551234567',
        ]));

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Email-field fallback ---------------------------------

    public function test_email_field_fallback_works_when_login_field_is_missing(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, [
            'email'    => 'test@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Email lowercasing -------------------------------------------------------

    public function test_email_identifier_is_lowercased_before_lookup(): void
    {
        config(['auth_features.lowercase_usernames' => true]);
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'login' => 'TEST@EXAMPLE.COM',
        ]));

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    public function test_email_case_preserved_when_lowercase_usernames_disabled(): void
    {
        config(['auth_features.lowercase_usernames' => false]);
        User::factory()->emailOnly()->create(['email' => 'Test@Example.Com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'login' => 'Test@Example.Com',
        ]));

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Phone normalization -----------------------------------------------------

    public function test_phone_identifier_is_normalized_before_lookup(): void
    {
        User::factory()->phoneOnly()->create(['phone' => '+15551234567']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'login' => '+1 (555) 123-4567',
        ]));

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Remember me -------------------------------------------------------------

    public function test_remember_me_accepted_and_user_authenticated(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'remember' => true,
        ]));

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Failure: invalid password -----------------------------------------------

    public function test_invalid_password_returns_422_and_not_authenticated(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'password' => 'wrong-password',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
        $this->assertGuest('web');
    }

    // -- Failure: unknown identifier ---------------------------------------------

    public function test_unknown_identifier_returns_422_and_not_authenticated(): void
    {
        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload([
            'login' => 'nobody@example.com',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
        $this->assertGuest('web');
    }

    // -- Email fallback: error key ----------------------------

    public function test_email_fallback_invalid_credentials_returns_error_on_email_key(): void
    {
        $response = $this->postJson(self::LOGIN_URI, [
            'email'    => 'nobody@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertGuest('web');
    }

    public function test_email_fallback_throttle_returns_429_with_error_on_email_key(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::LOGIN_URI, [
                'email'    => 'test@example.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson(self::LOGIN_URI, [
            'email'    => 'test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429);
        $response->assertJsonValidationErrors(['email']);
        $this->assertGuest('web');
    }

    // -- Throttle: too many attempts ---------------------------------------------

    public function test_repeated_failures_throttle_after_five_attempts(): void
    {
        // No user exists -- 5 failures with unknown identifier charges the bucket to 5.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::LOGIN_URI, $this->loginPayload(['password' => 'wrong']));
        }

        // Create the user now so correct credentials would succeed if not throttled.
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(429);
        $response->assertJsonValidationErrors(['login']);
        $this->assertGuest('web');
    }

    // -- Throttle: successful login clears bucket --------------------------------

    public function test_successful_login_clears_throttle_after_prior_failures(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        // 4 failed attempts -- bucket at 4, still under threshold of 5.
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(self::LOGIN_URI, $this->loginPayload(['password' => 'wrong']));
        }

        // Successful login must clear the bucket.
        $this->postJson(self::LOGIN_URI, $this->loginPayload())->assertStatus(200);
        $this->assertAuthenticated('web');
        Auth::guard('web')->logout();

        // With bucket cleared, 5 new failures are needed to hit throttle.
        // Each of these must return a credentials error (not throttle).
        for ($i = 0; $i < 5; $i++) {
            $r = $this->postJson(self::LOGIN_URI, $this->loginPayload(['password' => 'wrong']));
            $r->assertStatus(422);
        }

        // 6th failure (bucket=5) triggers throttle; user still not authenticated.
        $throttled = $this->postJson(self::LOGIN_URI, $this->loginPayload(['password' => 'wrong']));
        $throttled->assertStatus(429);
        $this->assertGuest('web');
    }

    // -- Web redirect after login ------------------------------------------------

    public function test_web_login_redirects_to_configured_home(): void
    {
        config(['auth_features.home' => '/home']);
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->post(self::LOGIN_URI, $this->loginPayload());

        $response->assertRedirect('/home');
    }

    // -- XHR branch --------------------------------------------------------------

    public function test_xhr_login_returns_200_via_x_requested_with_header(): void
    {
        // Verifies expectsJson() via X-Requested-With: XMLHttpRequest + Accept: */*,
        // the headers a real browser XHR sends when no explicit Accept is set.
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => '*/*',
        ])->post(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    // -- Logout: JSON ------------------------------------------------------------

    public function test_logout_json_returns_204_and_unauthenticates(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->actingAs($user);

        $response = $this->postJson(self::LOGOUT_URI);

        $response->assertNoContent();
        $this->assertGuest('web');
    }

    // -- Logout: web -------------------------------------------------------------

    public function test_logout_web_redirects_and_unauthenticates(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->actingAs($user);

        $response = $this->post(self::LOGOUT_URI);

        $response->assertRedirect('/');
        $this->assertGuest('web');
    }

    // -- Guard: honors auth_features config --------------------------------------

    public function test_login_authenticates_under_configured_guard(): void
    {
        // Verifies that the controller reads auth_features.guard from config
        // and passes it to Auth::guard()->login(). The configured guard is 'web',
        // so assertAuthenticated uses the same config value rather than a literal.
        config(['auth_features.guard' => 'web']);
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $this->assertAuthenticated(config('auth_features.guard'));
    }

    public function test_login_uses_non_default_guard_from_config(): void
    {
        // This test WOULD fail if the controller hardcoded Auth::guard('web').
        // A runtime-registered guard 'auth_test' is used; 'web' must stay as guest.
        config([
            'auth.guards.auth_test' => ['driver' => 'session', 'provider' => 'users'],
            'auth_features.guard'   => 'auth_test',
        ]);

        // Register a separate login route AFTER config is set so the route picks
        // up the correct guard. We do NOT reuse LOGOUT_URI -- only login matters.
        \Illuminate\Support\Facades\Route::post('/_auth-test/login-guard', [AuthenticatedSessionController::class, 'store'])
            ->middleware(['web']);

        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/_auth-test/login-guard', $this->loginPayload());

        $response->assertStatus(200);
        $this->assertAuthenticated('auth_test');
        $this->assertGuest('web');
    }

    // -- Email fallback: empty email returns error on 'email' key --

    public function test_email_fallback_empty_email_returns_error_on_email_key(): void
    {
        // When caller sends 'email' (no 'login') but the value is empty,
        // credentialKey() must still return 'email' so validation errors land
        // on the field the caller actually submitted, not the internal 'login'.
        $response = $this->postJson(self::LOGIN_URI, [
            'email'    => '',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertGuest('web');
    }

    // -- Two-factor challenge: login intercept -----------------------------------

    public function test_user_without_2fa_logs_in_normally(): void
    {
        User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $response->assertJsonMissing(['two_factor' => true]);
        $this->assertAuthenticated('web');
    }

    public function test_user_with_unconfirmed_2fa_logs_in_normally(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);

        // Enable but do NOT confirm 2FA.
        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        Auth::guard('web')->logout();

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    public function test_user_with_confirmed_2fa_json_login_returns_two_factor_true(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertStatus(200);
        $response->assertJson(['two_factor' => true]);
    }

    public function test_user_with_confirmed_2fa_json_login_does_not_authenticate(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->enableAndConfirmTwoFactor($user);

        $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $this->assertGuest('web');
    }

    public function test_user_with_confirmed_2fa_json_login_stores_challenge_session_state(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $response->assertSessionHas('_two_factor_login_id', $user->getKey());
    }

    public function test_user_with_confirmed_2fa_web_login_redirects_to_challenge_uri(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->post(self::LOGIN_URI, $this->loginPayload());

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest('web');
    }

    public function test_user_with_confirmed_2fa_does_not_consume_recovery_codes_on_login(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'test@example.com']);
        $this->enableAndConfirmTwoFactor($user);

        $codesBefore = app(RecoveryCodeManager::class)->retrieve($user);

        $this->postJson(self::LOGIN_URI, $this->loginPayload());

        $user->refresh();
        $codesAfter = app(RecoveryCodeManager::class)->retrieve($user);

        $this->assertSame($codesBefore, $codesAfter);
    }
}
