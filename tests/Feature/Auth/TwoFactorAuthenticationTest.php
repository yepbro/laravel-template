<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\TwoFactor\RecoveryCodeManager;
use App\Http\Controllers\Auth\ConfirmedTwoFactorAuthenticationController;
use App\Http\Controllers\Auth\RecoveryCodeController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorQrCodeController;
use App\Http\Controllers\Auth\TwoFactorSecretKeyController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Feature tests for project-owned two-factor authentication.
 *
 * Management endpoints are mounted at /_auth-test/... to keep them isolated
 * from the production routes. Challenge and management routes carry the same
 * middleware as the production routes.
 *
 * Management routes carry password.confirm middleware (matching the production
 * contract). Tests use withSession(['auth.password_confirmed_at' => time()])
 * to satisfy the middleware without a real confirmation roundtrip.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const ENABLE_URI         = '/_auth-test/user/two-factor-authentication';
    private const CONFIRM_URI        = '/_auth-test/user/confirmed-two-factor-authentication';
    private const QR_URI             = '/_auth-test/user/two-factor-qr-code';
    private const SECRET_URI         = '/_auth-test/user/two-factor-secret-key';
    private const RECOVERY_CODES_URI = '/_auth-test/user/two-factor-recovery-codes';
    private const CHALLENGE_URI      = '/_auth-test/two-factor-challenge';

    protected function setUp(): void
    {
        parent::setUp();

        $guard = config('auth_features.guard', 'web');

        // Management routes use the project-owned contract: [auth:{guard}, password.confirm]
        // for all 2FA management endpoints.
        $managementMiddleware = ['web', "auth:{$guard}", 'password.confirm'];

        Route::post(self::ENABLE_URI, [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($managementMiddleware);

        Route::delete(self::ENABLE_URI, [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($managementMiddleware);

        Route::post(self::CONFIRM_URI, [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($managementMiddleware);

        Route::get(self::QR_URI, TwoFactorQrCodeController::class)
            ->middleware($managementMiddleware);

        Route::get(self::SECRET_URI, TwoFactorSecretKeyController::class)
            ->middleware($managementMiddleware);

        Route::get(self::RECOVERY_CODES_URI, [RecoveryCodeController::class, 'index'])
            ->middleware($managementMiddleware);

        Route::post(self::RECOVERY_CODES_URI, [RecoveryCodeController::class, 'store'])
            ->middleware($managementMiddleware);

        // Challenge routes: guest:{guard} on GET; guest + throttle:two-factor on POST.
        // The 'two-factor' rate limiter is keyed by session '_two_factor_login_id' + IP.
        // Each test creates a unique user (IDs increment with RefreshDatabase transactions),
        // so rate limiter buckets are isolated per test without explicit clearing.
        Route::get(self::CHALLENGE_URI, [TwoFactorChallengeController::class, 'create'])
            ->middleware(['web', "guest:{$guard}"]);

        Route::post(self::CHALLENGE_URI, [TwoFactorChallengeController::class, 'store'])
            ->middleware(['web', "guest:{$guard}", 'throttle:two-factor']);
    }

    // -- Helpers -----------------------------------------------------------------

    /**
     * Returns a session array that satisfies the password.confirm middleware.
     *
     * @return array<string, mixed>
     */
    private function passwordConfirmedSession(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    /**
     * Enable 2FA for the given user and return the plaintext secret.
     */
    private function enableTwoFactor(User $user): string
    {
        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();

        // Keep the user authenticated for subsequent management calls in the same test.
        // Tests that need a clean guest state must call Auth::logout() themselves.
        return Crypt::decryptString((string) $user->two_factor_secret);
    }

    /**
     * Enable and confirm 2FA for the given user. Returns the plaintext secret.
     */
    private function enableAndConfirmTwoFactor(User $user): string
    {
        $plainSecret = $this->enableTwoFactor($user);

        $google2fa = new Google2FA();
        $validCode = $google2fa->getCurrentOtp($plainSecret);

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::CONFIRM_URI, ['code' => $validCode]);

        $user->refresh();

        // Clear actingAs state so challenge tests start from a guest position.
        Auth::logout();

        return $plainSecret;
    }

    /**
     * Returns a challenge session representing a pending 2FA login for $user.
     *
     * @return array<string, mixed>
     */
    private function challengeSession(User $user, bool $remember = false): array
    {
        return [
            '_two_factor_login_id'       => $user->getKey(),
            '_two_factor_login_remember' => $remember,
        ];
    }

    // -- Enable: state -----------------------------------------------------------

    public function test_enable_stores_encrypted_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $plainSecret = Crypt::decryptString((string) $user->two_factor_secret);
        $this->assertNotEmpty($plainSecret);
    }

    public function test_enable_stores_encrypted_recovery_codes(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();

        $this->assertNotNull($user->two_factor_recovery_codes);
        $codes = json_decode(
            Crypt::decryptString((string) $user->two_factor_recovery_codes),
            true,
        );
        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);
    }

    public function test_enable_leaves_confirmed_at_null_when_confirmation_required(): void
    {
        config(['auth_features.features.two_factor_requires_confirmation' => true]);
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_enable_marks_confirmed_immediately_when_confirmation_not_required(): void
    {
        config(['auth_features.features.two_factor_requires_confirmation' => false]);
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    // -- Enable: response --------------------------------------------------------

    public function test_enable_returns_200_json(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::ENABLE_URI);

        $response->assertOk();
    }

    public function test_enable_returns_redirect_back_with_status_for_web(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->post(self::ENABLE_URI);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'two-factor-authentication-enabled');
    }

    // -- Enable: middleware ------------------------------------------------------

    public function test_enable_requires_auth(): void
    {
        $response = $this->postJson(self::ENABLE_URI);

        $response->assertUnauthorized();
    }

    // -- Confirm: state ----------------------------------------------------------

    public function test_confirm_with_valid_totp_sets_two_factor_confirmed_at(): void
    {
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::CONFIRM_URI, ['code' => $validCode]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    // -- Confirm: response -------------------------------------------------------

    public function test_confirm_with_valid_totp_returns_200_json(): void
    {
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::CONFIRM_URI, ['code' => $validCode]);

        $response->assertOk();
    }

    public function test_confirm_with_valid_totp_returns_redirect_with_status_for_web(): void
    {
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->post(self::CONFIRM_URI, ['code' => $validCode]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'two-factor-authentication-confirmed');
    }

    // -- Confirm: failure --------------------------------------------------------

    public function test_confirm_with_invalid_code_returns_422(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::CONFIRM_URI, ['code' => '000000']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_confirm_with_invalid_code_does_not_set_confirmed_at(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::CONFIRM_URI, ['code' => '000000']);

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    // -- QR code -----------------------------------------------------------------

    public function test_qr_code_returns_svg_after_enabling(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::QR_URI);

        $response->assertOk();
        $response->assertJsonStructure(['svg']);
        $this->assertStringContainsString('<svg', $response->json('svg'));
    }

    public function test_qr_code_returns_422_when_no_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::QR_URI);

        $response->assertUnprocessable();
    }

    // -- Secret key --------------------------------------------------------------

    public function test_secret_key_returns_decrypted_key_after_enabling(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::SECRET_URI);

        $response->assertOk();
        $response->assertJsonStructure(['secretKey']);
        $this->assertNotEmpty($response->json('secretKey'));
    }

    public function test_secret_key_returns_422_when_no_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::SECRET_URI);

        $response->assertUnprocessable();
    }

    // -- Recovery codes: read ----------------------------------------------------

    public function test_recovery_codes_returns_array_after_enabling(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::RECOVERY_CODES_URI);

        $response->assertOk();
        $codes = $response->json();
        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);
    }

    public function test_recovery_codes_returns_422_when_no_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::RECOVERY_CODES_URI);

        $response->assertUnprocessable();
    }

    // -- Recovery codes: regenerate ----------------------------------------------

    public function test_regenerate_recovery_codes_replaces_old_codes(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);

        $before = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::RECOVERY_CODES_URI)
            ->json();

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::RECOVERY_CODES_URI);

        $after = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::RECOVERY_CODES_URI)
            ->json();

        $this->assertNotEquals($before, $after);
        $this->assertCount(8, $after);
    }

    // -- Disable -----------------------------------------------------------------

    public function test_disable_clears_secret_recovery_codes_and_confirmed_at(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->deleteJson(self::ENABLE_URI);

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_disable_returns_200_json(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->deleteJson(self::ENABLE_URI);

        $response->assertOk();
    }

    // -- hasEnabledTwoFactorAuthentication helper --------------------------------

    public function test_has_enabled_two_factor_returns_false_without_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_has_enabled_two_factor_returns_false_with_unconfirmed_secret(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);
        $user->refresh();

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_has_enabled_two_factor_returns_true_when_confirmed(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);
        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    // -- Two-factor fields are hidden from serialization -------------------------

    public function test_two_factor_secret_is_hidden_from_json(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);
        $user->refresh();

        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
    }

    public function test_two_factor_recovery_codes_is_hidden_from_json(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableTwoFactor($user);
        $user->refresh();

        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
    }

    // -- Challenge: GET ----------------------------------------------------------

    public function test_challenge_get_returns_view_for_web_with_session(): void
    {
        config(['auth_features.views' => true]);
        $user = User::factory()->emailOnly()->create();

        $response = $this->withSession($this->challengeSession($user))
            ->get(self::CHALLENGE_URI);

        $response->assertOk();
    }

    public function test_challenge_get_redirects_to_login_without_session(): void
    {
        $response = $this->get(self::CHALLENGE_URI);

        $response->assertRedirect(route('login'));
    }

    // -- Challenge: POST with TOTP code ------------------------------------------

    public function test_challenge_with_valid_totp_completes_login_json_204(): void
    {
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableAndConfirmTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['code' => $validCode]);

        $response->assertNoContent();
        $this->assertAuthenticated('web');
    }

    public function test_challenge_with_valid_totp_completes_login_web_redirect(): void
    {
        config(['auth_features.home' => '/home']);
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableAndConfirmTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $response = $this->withSession($this->challengeSession($user))
            ->post(self::CHALLENGE_URI, ['code' => $validCode]);

        $response->assertRedirect(config('auth_features.home', '/home'));
        $this->assertAuthenticated('web');
    }

    public function test_challenge_clears_session_state_after_login(): void
    {
        $user        = User::factory()->emailOnly()->create();
        $plainSecret = $this->enableAndConfirmTwoFactor($user);
        $validCode   = (new Google2FA())->getCurrentOtp($plainSecret);

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['code' => $validCode]);

        $response->assertSessionMissing('_two_factor_login_id');
    }

    // -- Challenge: POST with recovery code --------------------------------------

    public function test_challenge_with_valid_recovery_code_completes_login(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $recoveryCode = app(RecoveryCodeManager::class)->retrieve($user)[0];

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['recovery_code' => $recoveryCode]);

        $response->assertNoContent();
        $this->assertAuthenticated('web');
    }

    public function test_challenge_recovery_code_is_consumed_after_use(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $codes        = app(RecoveryCodeManager::class)->retrieve($user);
        $recoveryCode = $codes[0];

        $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['recovery_code' => $recoveryCode]);

        $user->refresh();
        $updatedCodes = app(RecoveryCodeManager::class)->retrieve($user);

        $this->assertNotContains($recoveryCode, $updatedCodes);
        $this->assertCount(7, $updatedCodes);
    }

    public function test_challenge_recovery_code_cannot_be_reused(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $codes        = app(RecoveryCodeManager::class)->retrieve($user);
        $recoveryCode = $codes[0];

        // First use - succeeds and authenticates the user.
        $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['recovery_code' => $recoveryCode]);

        // Log out to reset auth state before attempting the second use. Without this,
        // guest:web middleware would redirect (302) rather than letting the controller
        // reject the already-consumed code with 422.
        Auth::logout();

        // Second use of the same code should fail: the code was consumed on first use.
        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['recovery_code' => $recoveryCode]);

        $response->assertUnprocessable();
    }

    // -- Challenge: failure ------------------------------------------------------

    public function test_challenge_with_invalid_totp_returns_422(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['code' => '000000']);

        $response->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_challenge_with_invalid_recovery_code_returns_422(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['recovery_code' => 'invalid-code']);

        $response->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_challenge_without_session_returns_422(): void
    {
        $response = $this->postJson(self::CHALLENGE_URI, ['code' => '123456']);

        $response->assertUnprocessable();
    }

    public function test_challenge_with_neither_code_nor_recovery_code_returns_422(): void
    {
        $user = User::factory()->emailOnly()->create();
        $this->enableAndConfirmTwoFactor($user);

        $response = $this->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, []);

        $response->assertUnprocessable();
    }

    // -- Issue 1: regenerate without secret guard --------------------------------

    public function test_regenerate_recovery_codes_returns_422_when_no_secret(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::RECOVERY_CODES_URI);

        $response->assertUnprocessable();
    }

    // -- Issue 2: consume() transaction/lock safety ------------------------------

    public function test_consume_uses_db_transaction_and_lock_for_update(): void
    {
        $user    = User::factory()->emailOnly()->create();
        $manager = app(RecoveryCodeManager::class);
        $codes   = $manager->generate();
        $manager->store($user, $codes);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $manager->consume($user, $codes[0]);
        $log = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $hasLockQuery = false;

        foreach ($log as $entry) {
            if (stripos((string) $entry['query'], 'for update') !== false) {
                $hasLockQuery = true;
                break;
            }
        }

        $this->assertTrue(
            $hasLockQuery,
            'RecoveryCodeManager::consume() must use a SELECT FOR UPDATE row lock to prevent concurrent code reuse.',
        );
    }

    public function test_consume_sequential_replay_returns_false(): void
    {
        $user    = User::factory()->emailOnly()->create();
        $manager = app(RecoveryCodeManager::class);
        $codes   = $manager->generate();
        $manager->store($user, $codes);
        $code = $codes[0];

        $first  = $manager->consume($user, $code);
        $second = $manager->consume($user, $code);

        $this->assertTrue($first, 'First consume must succeed');
        $this->assertFalse($second, 'Second consume of the same code must return false');
    }

    // -- Issue 3: middleware contract tests --------------------------------------

    public function test_confirm_post_requires_password_confirmation(): void
    {
        // Use actingAs with no prior withSession call to guarantee password is not confirmed.
        // password.confirm middleware must return 423 for JSON before reaching the controller.
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->postJson(self::CONFIRM_URI, ['code' => '000000']);

        $response->assertStatus(423);
    }

    public function test_qr_code_get_requires_password_confirmation(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->getJson(self::QR_URI);

        $response->assertStatus(423);
    }

    public function test_secret_key_get_requires_password_confirmation(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->getJson(self::SECRET_URI);

        $response->assertStatus(423);
    }

    public function test_recovery_codes_get_requires_password_confirmation(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->getJson(self::RECOVERY_CODES_URI);

        $response->assertStatus(423);
    }

    public function test_challenge_get_redirects_authenticated_user_even_with_challenge_session(): void
    {
        // guest:web must redirect BEFORE the controller runs. Providing a valid challenge
        // session ensures the controller would otherwise return 200 (view) -- the redirect
        // must come from the middleware, not a missing-session branch.
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->challengeSession($user))
            ->get(self::CHALLENGE_URI);

        $response->assertStatus(302);
    }

    public function test_challenge_post_redirects_authenticated_user_even_with_challenge_session(): void
    {
        // guest:web returns 302 even for JSON requests -- RedirectIfAuthenticated does not
        // check expectsJson(). Providing a challenge session ensures the controller would
        // otherwise process the code (returning 422 for invalid input) without the middleware.
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)
            ->withSession($this->challengeSession($user))
            ->postJson(self::CHALLENGE_URI, ['code' => '000000']);

        $response->assertStatus(302);
    }
}
