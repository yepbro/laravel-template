<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordController;
use App\Models\User;
use App\Notifications\Auth\PasswordChanged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for project-owned authenticated password update controller.
 *
 * The controller is mounted at a test-only URI for isolation.
 */
class PasswordUpdateCoreTest extends TestCase
{
    use RefreshDatabase;

    private const UPDATE_URI = '/_auth-test/user/password';

    protected function setUp(): void
    {
        parent::setUp();

        $guard = config('auth_features.guard', 'web');

        Route::put(self::UPDATE_URI, [PasswordController::class, 'update'])
            ->middleware(['web', "auth:{$guard}"]);
    }

    // -- Success -----------------------------------------------------------------

    public function test_password_update_succeeds_with_correct_current_password_and_returns_200(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(200);
        // Project-owned response returns an empty body for JSON (no 'status' or 'message' key).
        $this->assertEmpty(json_decode((string) $response->getContent(), true));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1!', $user->password));
    }

    public function test_password_update_sends_password_changed_notification(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertStatus(200);

        Notification::assertSentTo($user, PasswordChanged::class);
    }

    // -- Wrong current password --------------------------------------------------

    public function test_password_update_fails_with_wrong_current_password_and_does_not_update(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'wrong-password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);

        $user->refresh();
        $this->assertFalse(Hash::check('NewPassword1!', $user->password));
    }

    // -- Password confirmation mismatch ------------------------------------------

    public function test_password_update_fails_when_confirmation_mismatches(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'DifferentPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // -- Missing fields ----------------------------------------------------------

    public function test_password_update_requires_current_password_field(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    }

    // -- Auth middleware ---------------------------------------------------------

    public function test_password_update_requires_authentication(): void
    {
        $response = $this->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(401);
    }

    // -- Web redirect ------------------------------------------------------------

    public function test_password_update_web_success_redirects_back_with_status(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->put(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect();
        // The 'password-updated' status string is the project-owned session status key.
        $response->assertSessionHas('status', 'password-updated');
    }

    // -- remember_token: must NOT rotate for authenticated update ----------------

    public function test_password_update_does_not_rotate_remember_token(): void
    {
        $user = User::factory()->emailOnly()->create(['remember_token' => 'original-token']);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertEquals('original-token', $user->remember_token);
    }

    public function test_password_update_does_not_dispatch_password_reset_event(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertStatus(200);

        Event::assertNotDispatched(PasswordReset::class);
    }

    // -- Token invalidation: authenticated update must delete existing reset tokens -

    public function test_password_update_invalidates_existing_reset_token(): void
    {
        $user = User::factory()->emailOnly()->create();

        // Create a password reset token before the authenticated update.
        $broker = Password::broker(config('auth_features.passwords', 'users'));
        $token  = $broker->createToken($user);

        $this->assertTrue($broker->tokenExists($user, $token));

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertStatus(200);

        // The old reset token must no longer be valid.
        $this->assertFalse($broker->tokenExists($user->fresh(), $token));
    }

    // -- wantsJson() parity: XHR with Accept */* must get web redirect ------------

    public function test_password_update_xhr_with_accept_star_gets_web_redirect_not_json(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->put(
            self::UPDATE_URI,
            [
                'current_password'      => 'password',
                'password'              => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'Accept' => '*/*'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');
    }

    // -- Non-default guard -------------------------------------------------------

    public function test_password_update_non_default_guard_succeeds(): void
    {
        // This test proves UpdatePasswordRequest uses the guard from config.
        // It WOULD fail if current_password were hardcoded to ':web' because
        // actingAs uses 'auth_test', leaving 'web' as guest.
        config([
            'auth.guards.auth_test' => ['driver' => 'session', 'provider' => 'users'],
            'auth_features.guard'   => 'auth_test',
        ]);

        Route::put('/_auth-test/user/password-guard', [PasswordController::class, 'update'])
            ->middleware(['web', 'auth:auth_test']);

        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user, 'auth_test')->putJson('/_auth-test/user/password-guard', [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(200);
        $this->assertGuest('web');
    }
}
