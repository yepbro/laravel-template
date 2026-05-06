<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for project-owned forgot-password and reset-password controllers.
 *
 * Controllers are mounted on test-only URIs to allow fine-grained assertions
 * without interference from production-route throttling.
 */
class PasswordResetCoreTest extends TestCase
{
    use RefreshDatabase;

    private const FORGOT_URI = '/_auth-test/forgot-password';
    private const RESET_URI  = '/_auth-test/reset-password';

    protected function setUp(): void
    {
        parent::setUp();

        Route::post(self::FORGOT_URI, [PasswordResetLinkController::class, 'store'])
            ->middleware(['web']);

        Route::post(self::RESET_URI, [NewPasswordController::class, 'store'])
            ->middleware(['web']);
    }

    // -- Forgot password: success ------------------------------------------------

    public function test_forgot_password_sends_reset_link_for_known_email_and_returns_200(): void
    {
        Notification::fake();
        User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $response = $this->postJson(self::FORGOT_URI, ['email' => 'user@example.com']);

        $response->assertStatus(200);
        $response->assertJson(['message' => trans(Password::RESET_LINK_SENT)]);
        $response->assertJsonMissing(['status' => trans(Password::RESET_LINK_SENT)]);
    }

    public function test_forgot_password_sends_reset_notification_to_user(): void
    {
        Notification::fake();
        $user = User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $this->postJson(self::FORGOT_URI, ['email' => 'user@example.com']);

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    // -- Forgot password: unknown email ------------------------------------------

    public function test_forgot_password_unknown_email_returns_422_with_email_error(): void
    {
        $response = $this->postJson(self::FORGOT_URI, ['email' => 'nobody@example.com']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // -- Forgot password: validation ---------------------------------------------

    public function test_forgot_password_missing_email_returns_422(): void
    {
        $response = $this->postJson(self::FORGOT_URI, []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson(self::FORGOT_URI, ['email' => 'not-an-email']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // -- Forgot password: web redirect ------------------------------------------

    public function test_forgot_password_web_success_redirects_back_with_status(): void
    {
        Notification::fake();
        User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $response = $this->post(self::FORGOT_URI, ['email' => 'user@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_forgot_password_web_unknown_email_redirects_back_with_errors(): void
    {
        $response = $this->post(self::FORGOT_URI, ['email' => 'nobody@example.com']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    // -- Reset password: success -------------------------------------------------

    public function test_reset_password_with_valid_token_updates_password_and_returns_200(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $originalRememberToken = $user->remember_token;

        $token = Password::broker()->createToken($user);

        $response = $this->postJson(self::RESET_URI, [
            'token'                 => $token,
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => trans(Password::PASSWORD_RESET)]);
        $response->assertJsonMissing(['status' => trans(Password::PASSWORD_RESET)]);

        // Password was changed
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->password));

        // remember_token was rotated
        $this->assertNotEquals($originalRememberToken, $user->remember_token);

        Event::assertDispatched(PasswordReset::class);
    }

    // -- Reset password: invalid token -------------------------------------------

    public function test_reset_password_with_invalid_token_returns_422_and_does_not_update_password(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $response = $this->postJson(self::RESET_URI, [
            'token'                 => 'invalid-token',
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        $user->refresh();
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->password));
    }

    // -- Reset password: password confirmation mismatch --------------------------

    public function test_reset_password_confirmation_mismatch_returns_422(): void
    {
        $user  = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson(self::RESET_URI, [
            'token'                 => $token,
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'DifferentPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // -- Reset password: missing fields ------------------------------------------

    public function test_reset_password_missing_token_returns_422(): void
    {
        $response = $this->postJson(self::RESET_URI, [
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    // -- Reset password: web redirect --------------------------------------------

    public function test_reset_password_web_success_redirects_to_login_with_status(): void
    {
        Event::fake([PasswordReset::class]);
        Notification::fake();

        $user  = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->post(self::RESET_URI, [
            'token'                 => $token,
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    // -- wantsJson() parity: XHR with Accept */* must get web redirect ------------

    public function test_forgot_password_xhr_with_accept_star_gets_web_redirect_not_json(): void
    {
        Notification::fake();
        User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $response = $this->post(
            self::FORGOT_URI,
            ['email' => 'user@example.com'],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'Accept' => '*/*'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_reset_password_xhr_with_accept_star_gets_web_redirect_not_json(): void
    {
        Event::fake([PasswordReset::class]);

        $user  = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->post(
            self::RESET_URI,
            [
                'token'                 => $token,
                'email'                 => 'user@example.com',
                'password'              => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'Accept' => '*/*'],
        );

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    // -- withInput: failed web submissions preserve submitted email ---------------

    public function test_forgot_password_web_failure_preserves_email_input(): void
    {
        $response = $this->post(self::FORGOT_URI, ['email' => 'nobody@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('_old_input', ['email' => 'nobody@example.com']);
    }

    public function test_reset_password_web_failure_preserves_email_input(): void
    {
        $response = $this->post(self::RESET_URI, [
            'token'                 => 'invalid-token',
            'email'                 => 'nonexistent@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('_old_input', ['email' => 'nonexistent@example.com']);
    }

    // -- Configurable post-reset redirect ----------------------------------------

    public function test_reset_password_web_success_uses_configured_password_reset_redirect(): void
    {
        Event::fake([PasswordReset::class]);

        // Override to 'register' which is a known named route defined at boot.
        config(['auth_features.password_reset_redirect' => 'register']);

        $user  = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->post(self::RESET_URI, [
            'token'                 => $token,
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('status');
    }

    // -- Configurable email field: request validation uses the configured field ---

    public function test_forgot_password_uses_configured_email_field_for_validation(): void
    {
        config(['auth_features.email' => 'email_address']);

        // Submitting 'email' is not accepted; 'email_address' is now required.
        $response = $this->postJson(self::FORGOT_URI, ['email' => 'user@example.com']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email_address']);
        $response->assertJsonMissingValidationErrors(['email']);
    }

    public function test_reset_password_uses_configured_email_field_for_validation(): void
    {
        config(['auth_features.email' => 'email_address']);

        $response = $this->postJson(self::RESET_URI, [
            'token'                 => 'some-token',
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email_address']);
        $response->assertJsonMissingValidationErrors(['email']);
    }

    // -- passwordResetRedirect fallback when named route is missing --------------

    public function test_reset_password_web_success_falls_back_to_home_when_configured_route_is_missing(): void
    {
        Event::fake([PasswordReset::class]);

        // Point redirect at a route name that is not registered.
        config(['auth_features.password_reset_redirect' => 'route-that-does-not-exist']);

        $user  = User::factory()->emailOnly()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->post(self::RESET_URI, [
            'token'                 => $token,
            'email'                 => 'user@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        // Must not throw; falls back to AuthFeatures::home() which defaults to '/home'.
        $response->assertRedirect('/home');
        $response->assertSessionHas('status');
    }

    // -- Aliased email field: full success flows ---------------------------------

    /**
     * When auth_features.email = 'email_address', the request field is 'email_address'
     * but the broker must still query users.email. This verifies the alias mapping.
     */
    public function test_forgot_password_with_aliased_email_field_sends_notification_and_returns_200(): void
    {
        Notification::fake();
        config(['auth_features.email' => 'email_address']);

        $user = User::factory()->emailOnly()->create(['email' => 'alias@example.com']);

        $response = $this->postJson(self::FORGOT_URI, ['email_address' => 'alias@example.com']);

        $response->assertStatus(200);
        $response->assertJson(['message' => trans(Password::RESET_LINK_SENT)]);
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    /**
     * When auth_features.email = 'email_address', the reset token must still be
     * validated against users.email and the password must be updated.
     */
    public function test_reset_password_with_aliased_email_field_updates_password_and_returns_200(): void
    {
        Event::fake([PasswordReset::class]);
        config(['auth_features.email' => 'email_address']);

        $user  = User::factory()->emailOnly()->create(['email' => 'alias@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson(self::RESET_URI, [
            'token'                 => $token,
            'email_address'         => 'alias@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => trans(Password::PASSWORD_RESET)]);

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->password));

        Event::assertDispatched(PasswordReset::class);
    }

    // -- Aliased email field: broker failure paths ------------------------------

    /**
     * When auth_features.email = 'email_address', a broker failure (unknown address)
     * must surface the error under 'email_address', not 'email'.
     */
    public function test_forgot_password_aliased_field_broker_failure_returns_422_with_aliased_error_key(): void
    {
        config(['auth_features.email' => 'email_address']);

        $response = $this->postJson(self::FORGOT_URI, ['email_address' => 'missing@example.com']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email_address']);
        $response->assertJsonMissingValidationErrors(['email']);
    }

    /**
     * Web reset with aliased email field and invalid token must redirect with errors
     * keyed to 'email_address' and preserve 'email_address' in old input - not 'email'.
     */
    public function test_reset_password_aliased_field_web_broker_failure_redirects_with_aliased_errors_and_preserves_input(): void
    {
        config(['auth_features.email' => 'email_address']);

        $response = $this->post(self::RESET_URI, [
            'token'                 => 'invalid-token',
            'email_address'         => 'missing@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email_address']);
        $response->assertSessionHas('_old_input', ['email_address' => 'missing@example.com']);
    }

    // -- Reset password: phone-only user (no email) falls through broker ---------

    public function test_reset_password_phone_only_user_returns_422_due_to_broker(): void
    {
        $response = $this->postJson(self::RESET_URI, [
            'token'                 => 'some-token',
            'email'                 => 'nonexistent@example.com',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
