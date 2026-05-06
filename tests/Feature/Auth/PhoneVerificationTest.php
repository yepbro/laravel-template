<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Channels\FakePhoneOtpChannel;
use App\Auth\Contracts\PhoneOtpChannel;
use App\Auth\Routing\AuthRouteRegistrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for project-owned phone OTP verification.
 *
 * Routes are registered per-test via AuthRouteRegistrar::phoneVerification()
 * because the production config flag defaults to false. The FakePhoneOtpChannel
 * is bound as a singleton before each test so the controller and the assertion
 * helpers share the same in-memory instance.
 */
class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected FakePhoneOtpChannel $fake;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth_features.features.phone_verification' => true]);

        $this->fake = new FakePhoneOtpChannel();
        $this->app->instance(PhoneOtpChannel::class, $this->fake);

        AuthRouteRegistrar::phoneVerification();
    }

    // == Route contract ===========================================================

    public function test_phone_verification_routes_have_correct_contract(): void
    {
        $routes = Route::getRoutes();

        $send = $routes->getByName('phone.verification.send');
        $this->assertNotNull($send, 'phone.verification.send route must be registered.');
        $this->assertSame('phone/verification-notification', $send->uri());
        $this->assertContains('POST', $send->methods());
        $this->assertContains('web', $send->middleware());
        $this->assertContains('auth:web', $send->middleware());
        $this->assertContains('throttle:6,1', $send->middleware());

        $verify = $routes->getByName('phone.verification.verify');
        $this->assertNotNull($verify, 'phone.verification.verify route must be registered.');
        $this->assertSame('phone/verify', $verify->uri());
        $this->assertContains('POST', $verify->methods());
        $this->assertContains('web', $verify->middleware());
        $this->assertContains('auth:web', $verify->middleware());
        $this->assertContains('throttle:6,1', $verify->middleware());
    }

    // == Send endpoint (POST phone/verification-notification) =====================

    public function test_send_returns_202_and_delivers_otp_for_unverified_phone_user_json(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)
            ->postJson(route('phone.verification.send'))
            ->assertStatus(202);

        $this->assertCount(1, $this->fake->sent());
    }

    public function test_send_redirects_back_with_status_for_web(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)
            ->post(route('phone.verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'phone-verification-code-sent');

        $this->assertCount(1, $this->fake->sent());
    }

    public function test_send_returns_422_with_phone_error_for_no_phone_user_json(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->postJson(route('phone.verification.send'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        $this->assertCount(0, $this->fake->sent());
    }

    public function test_send_redirects_back_with_phone_error_for_no_phone_user_web(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->post(route('phone.verification.send'))
            ->assertRedirect()
            ->assertSessionHasErrors(['phone']);

        $this->assertCount(0, $this->fake->sent());
    }

    public function test_send_returns_204_and_does_not_send_for_already_verified_phone_json(): void
    {
        $user = User::factory()->phoneVerified()->create();

        $this->actingAs($user)
            ->postJson(route('phone.verification.send'))
            ->assertNoContent();

        $this->assertCount(0, $this->fake->sent());
    }

    public function test_send_redirects_home_for_already_verified_phone_web_and_does_not_send(): void
    {
        config(['auth_features.home' => '/home']);
        $user = User::factory()->phoneVerified()->create();

        $this->actingAs($user)
            ->post(route('phone.verification.send'))
            ->assertRedirect('/home');

        $this->assertCount(0, $this->fake->sent());
    }

    // == Verify endpoint (POST phone/verify) ======================================

    public function test_verify_valid_otp_marks_phone_verified_and_returns_204_json(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertNoContent();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_valid_otp_redirects_home_for_web(): void
    {
        config(['auth_features.home' => '/home']);
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->post(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->actingAs($user)
            ->post(route('phone.verification.verify'), ['code' => $code])
            ->assertRedirect('/home');

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_invalid_code_returns_422_with_code_error_and_leaves_unverified(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_expired_code_returns_422_and_leaves_unverified(): void
    {
        config(['auth_features.phone_otp.expires_minutes' => 10]);
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->travel(15)->minutes();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_no_phone_user_returns_422_with_phone_error(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => '123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_already_verified_returns_204_without_consuming_code_json(): void
    {
        $user = User::factory()->phoneVerified()->create();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => '123456'])
            ->assertNoContent();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_verify_already_verified_redirects_home_web(): void
    {
        config(['auth_features.home' => '/home']);
        $user = User::factory()->phoneVerified()->create();

        $this->actingAs($user)
            ->post(route('phone.verification.verify'), ['code' => '123456'])
            ->assertRedirect('/home');
    }

    public function test_otp_length_follows_config(): void
    {
        config(['auth_features.phone_otp.length' => 4]);
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->assertNotNull($code);
        $this->assertSame(4, strlen((string) $code));
    }

    public function test_otp_expires_after_configured_minutes(): void
    {
        config(['auth_features.phone_otp.expires_minutes' => 5]);
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->travel(4)->minutes();

        // Still valid just before expiry
        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertNoContent();
    }

    public function test_otp_is_single_use_replay_fails(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertNoContent();

        // Reset phone_verified_at to allow another attempt
        $user->forceFill(['phone_verified_at' => null])->save();

        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_max_attempts_prevents_unlimited_guessing(): void
    {
        config(['auth_features.phone_otp.max_attempts' => 3]);
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->actingAs($user)->postJson(route('phone.verification.send'));
        $code = $this->fake->lastCode();

        // Exhaust all allowed attempts with wrong codes
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)
                ->postJson(route('phone.verification.verify'), ['code' => '000000']);
        }

        // Even the correct code is rejected after max attempts
        $this->actingAs($user)
            ->postJson(route('phone.verification.verify'), ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }
}
