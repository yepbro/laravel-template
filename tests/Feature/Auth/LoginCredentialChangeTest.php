<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Enums\LoginCredentialChangeType;
use App\Http\Controllers\Auth\LoginCredentialChangeController;
use App\Models\User;
use App\Models\UserLoginChangeRequest;
use App\Notifications\Auth\LoginCredentialChanged;
use App\Notifications\Auth\LoginCredentialChangeConfirm;
use App\Notifications\Auth\LoginCredentialChangePhoneConfirm;
use App\Notifications\Auth\LoginCredentialChangeRequested;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LoginCredentialChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_change_requires_authentication(): void
    {
        Notification::fake();

        $this->postJson('/user/login-credentials/email', [
            'email'            => 'new@example.com',
            'current_password' => 'password',
        ])->assertUnauthorized();
    }

    public function test_email_change_fails_when_current_password_wrong(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)->postJson('/user/login-credentials/email', [
            'email'            => 'new@example.com',
            'current_password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_email_change_fails_when_email_not_unique(): void
    {
        Notification::fake();

        User::factory()->emailOnly()->create(['email' => 'taken@example.com']);
        $user = User::factory()->emailOnly()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)->postJson('/user/login-credentials/email', [
            'email'            => 'taken@example.com',
            'current_password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_email_change_succeeds_and_creates_pending_request(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->postJson('/user/login-credentials/email', [
            'email'            => 'new@example.com',
            'current_password' => 'password',
        ])->assertNoContent();

        $this->assertDatabaseHas('user_login_change_requests', [
            'user_id'   => $user->id,
            'type'      => LoginCredentialChangeType::Email->value,
            'new_value' => 'new@example.com',
        ]);

        Notification::assertSentTo($user, LoginCredentialChangeRequested::class);
        Notification::assertSentOnDemand(LoginCredentialChangeConfirm::class);
        Notification::assertSentOnDemandTimes(LoginCredentialChangeConfirm::class, 1);
    }

    public function test_confirm_updates_email_and_clears_verified_at(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create([
            'email'             => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $plain = 'known-plain-token-abc-12345678901234567890123456789012';
        UserLoginChangeRequest::factory()
            ->for($user)
            ->withPlainToken($plain)
            ->create([
                'type'       => LoginCredentialChangeType::Email,
                'new_value'  => 'confirmed@example.com',
                'expires_at' => now()->addHour(),
            ]);

        $url = URL::temporarySignedRoute(
            'user.login-credentials.confirm',
            now()->addHour(),
            ['token' => $plain],
        );

        $this->getJson($url)->assertOk()->assertJsonPath('message', __('Login credential updated.'));

        $user->refresh();
        $this->assertSame('confirmed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, LoginCredentialChanged::class);
    }

    public function test_confirm_dispatches_verify_email_when_feature_enabled(): void
    {
        config(['auth_features.features.email_verification' => true]);
        Notification::fake();

        $user = User::factory()->emailOnly()->create([
            'email'             => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $plain = 'plain-for-verify-123456789012345678901234567890ab';
        UserLoginChangeRequest::factory()
            ->for($user)
            ->withPlainToken($plain)
            ->create([
                'type'       => LoginCredentialChangeType::Email,
                'new_value'  => 'verified-flow@example.com',
                'expires_at' => now()->addHour(),
            ]);

        $url = URL::temporarySignedRoute(
            'user.login-credentials.confirm',
            now()->addHour(),
            ['token' => $plain],
        );

        $this->getJson($url)->assertOk();

        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_confirm_fails_when_token_expired(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create(['email' => 'old@example.com']);

        $plain = 'expired-token-123456789012345678901234567890ab';
        UserLoginChangeRequest::factory()
            ->for($user)
            ->withPlainToken($plain)
            ->create([
                'type'       => LoginCredentialChangeType::Email,
                'new_value'  => 'nope@example.com',
                'expires_at' => now()->subMinute(),
            ]);

        $url = URL::temporarySignedRoute(
            'user.login-credentials.confirm',
            now()->addHour(),
            ['token' => $plain],
        );

        $this->getJson($url)->assertStatus(422)->assertJsonValidationErrors(['token']);

        $user->refresh();
        $this->assertSame('old@example.com', $user->email);
    }

    public function test_newer_proposal_revokes_previous_pending_request(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->postJson('/user/login-credentials/email', [
            'email'            => 'first@example.com',
            'current_password' => 'password',
        ])->assertNoContent();

        $this->actingAs($user)->postJson('/user/login-credentials/email', [
            'email'            => 'second@example.com',
            'current_password' => 'password',
        ])->assertNoContent();

        $this->assertDatabaseCount('user_login_change_requests', 1);
        $this->assertDatabaseHas('user_login_change_requests', [
            'user_id'   => $user->id,
            'new_value' => 'second@example.com',
        ]);
    }

    public function test_phone_route_is_absent_when_registration_mode_is_email_only(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('user.login-credentials.phone'));
    }

    public function test_phone_change_succeeds_when_phone_route_is_available(): void
    {
        $guard = config('auth_features.guard', 'web');
        Route::post('/_auth-test/user/login-credentials/phone', [LoginCredentialChangeController::class, 'requestPhoneChange'])
            ->middleware(['web', "auth:{$guard}", 'throttle:6,1']);

        Notification::fake();

        $user = User::factory()->emailAndPhone()->create([
            'email' => 'u@example.com',
            'phone' => '+15551111111',
        ]);

        $this->actingAs($user)->postJson('/_auth-test/user/login-credentials/phone', [
            'phone'            => '+15552222222',
            'current_password' => 'password',
        ])->assertNoContent();

        $this->assertDatabaseHas('user_login_change_requests', [
            'user_id'   => $user->id,
            'type'      => LoginCredentialChangeType::Phone->value,
            'new_value' => '+15552222222',
        ]);

        Notification::assertSentTo($user, LoginCredentialChangePhoneConfirm::class);
    }
}
