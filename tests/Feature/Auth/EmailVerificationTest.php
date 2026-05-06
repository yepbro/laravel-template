<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Routing\AuthRouteRegistrar;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Feature tests for project-owned email verification.
 *
 * Routes are registered per-test via AuthRouteRegistrar::emailVerification()
 * because the production config flag defaults to false. Tests that exercise
 * HTTP endpoints set the config flag and register routes in setUp so the
 * canonical route names, URIs, and middleware are exercised exactly.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth_features.features.email_verification' => true]);

        AuthRouteRegistrar::emailVerification();
    }

    // -- Helpers -----------------------------------------------------------------

    private function signedVerifyUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id'   => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    // == sendEmailVerificationNotification behavior ==============================

    public function test_notification_sent_on_registered_event_when_feature_enabled_and_email_unverified(): void
    {
        config(['auth_features.features.email_verification' => true]);
        Notification::fake();

        $user = User::factory()->emailOnly()->unverified()->create();

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_notification_not_sent_on_registered_when_feature_disabled(): void
    {
        config(['auth_features.features.email_verification' => false]);
        Notification::fake();

        $user = User::factory()->emailOnly()->unverified()->create();

        event(new Registered($user));

        Notification::assertNothingSent();
    }

    public function test_notification_not_sent_on_registered_for_phone_only_user(): void
    {
        config(['auth_features.features.email_verification' => true]);
        Notification::fake();

        $user = User::factory()->phoneOnly()->create();

        event(new Registered($user));

        Notification::assertNothingSent();
    }

    public function test_notification_not_sent_on_registered_when_email_already_verified(): void
    {
        config(['auth_features.features.email_verification' => true]);
        Notification::fake();

        // Factory default creates users with email_verified_at set.
        $user = User::factory()->emailOnly()->create();

        event(new Registered($user));

        Notification::assertNothingSent();
    }

    // == Notice controller (GET email/verify) ====================================

    public function test_notice_returns_422_with_email_error_for_phone_only_user_json(): void
    {
        $user = User::factory()->phoneOnly()->create();

        $this->actingAs($user)
            ->getJson(route('verification.notice'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_notice_redirects_with_email_error_for_phone_only_user_web(): void
    {
        $user = User::factory()->phoneOnly()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect()
            ->assertSessionHasErrors(['email']);
    }

    public function test_notice_redirects_unauthenticated_user(): void
    {
        $this->get(route('verification.notice'))->assertRedirect();
    }

    public function test_notice_returns_200_for_authenticated_unverified_user_web(): void
    {
        $user = User::factory()->emailOnly()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    public function test_notice_returns_200_for_json_request(): void
    {
        $user = User::factory()->emailOnly()->unverified()->create();

        $this->actingAs($user)
            ->getJson(route('verification.notice'))
            ->assertOk();
    }

    public function test_notice_redirects_home_for_already_verified_user_web(): void
    {
        config(['auth_features.home' => '/home']);
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect('/home');
    }

    public function test_notice_returns_200_for_already_verified_user_json(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->getJson(route('verification.notice'))
            ->assertOk();
    }

    // == Verify email controller (GET email/verify/{id}/{hash}) ==================

    public function test_verify_email_marks_as_verified_dispatches_event_and_redirects_home(): void
    {
        config(['auth_features.home' => '/home']);
        Event::fake([Verified::class]);

        $user = User::factory()->emailOnly()->unverified()->create();
        $url  = $this->signedVerifyUrl($user);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect('/home');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_verify_email_returns_204_for_json_on_success(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->emailOnly()->unverified()->create();
        $url  = $this->signedVerifyUrl($user);

        $this->actingAs($user)
            ->getJson($url)
            ->assertNoContent();

        Event::assertDispatched(Verified::class);
    }

    public function test_verify_email_redirects_home_for_already_verified_without_duplicate_event(): void
    {
        config(['auth_features.home' => '/home']);
        Event::fake([Verified::class]);

        $user = User::factory()->emailOnly()->create();
        $url  = $this->signedVerifyUrl($user);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect('/home');

        Event::assertNotDispatched(Verified::class);
    }

    public function test_verify_email_rejects_unsigned_url_with_403(): void
    {
        $user = User::factory()->emailOnly()->unverified()->create();

        // route() without signing - the signed middleware should reject it.
        $unsignedUrl = route('verification.verify', [
            'id'   => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)
            ->get($unsignedUrl)
            ->assertStatus(403);
    }

    public function test_verify_email_returns_422_for_phone_only_user_json_and_does_not_set_email_verified_at(): void
    {
        Event::fake([Verified::class]);

        // phoneOnly() creates a user with email = null.
        // getEmailForVerification() returns '' for such users.
        // sha1('') is a valid hash, so authorize() on EmailVerificationRequest passes.
        // Our guard must intercept before fulfill() is called.
        $user = User::factory()->phoneOnly()->create();
        $url  = $this->signedVerifyUrl($user);

        $this->actingAs($user)
            ->getJson($url)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertNull($user->fresh()->email_verified_at);
        Event::assertNotDispatched(Verified::class);
    }

    public function test_verify_email_redirects_with_email_error_for_phone_only_user_web_and_does_not_set_email_verified_at(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->phoneOnly()->create();
        $url  = $this->signedVerifyUrl($user);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect()
            ->assertSessionHasErrors(['email']);

        $this->assertNull($user->fresh()->email_verified_at);
        Event::assertNotDispatched(Verified::class);
    }

    // == Resend controller (POST email/verification-notification) ================

    public function test_resend_sends_notification_and_returns_202_for_json(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->unverified()->create();

        $this->actingAs($user)
            ->postJson(route('verification.send'))
            ->assertStatus(202);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_sends_notification_and_redirects_with_status_for_web(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_returns_204_for_already_verified_user_json(): void
    {
        Notification::fake();

        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->postJson(route('verification.send'))
            ->assertNoContent();

        Notification::assertNothingSent();
    }

    public function test_resend_redirects_home_for_already_verified_user_web(): void
    {
        config(['auth_features.home' => '/home']);
        Notification::fake();

        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect('/home');

        Notification::assertNothingSent();
    }

    public function test_resend_returns_422_for_phone_only_user_json(): void
    {
        Notification::fake();

        $user = User::factory()->phoneOnly()->create();

        $this->actingAs($user)
            ->postJson(route('verification.send'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Notification::assertNothingSent();
    }

    public function test_resend_redirects_with_errors_for_phone_only_user_web(): void
    {
        Notification::fake();

        $user = User::factory()->phoneOnly()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    }

    // == Route contract ==========================================================

    public function test_verification_routes_have_correct_contract_when_feature_enabled(): void
    {
        $routes = Route::getRoutes();

        $notice = $routes->getByName('verification.notice');
        $this->assertNotNull($notice, 'verification.notice route must be registered.');
        $this->assertSame('email/verify', $notice->uri());
        $this->assertContains('GET', $notice->methods());
        $this->assertContains('web', $notice->middleware());
        $this->assertContains('auth:web', $notice->middleware());

        $send = $routes->getByName('verification.send');
        $this->assertNotNull($send, 'verification.send route must be registered.');
        $this->assertSame('email/verification-notification', $send->uri());
        $this->assertContains('POST', $send->methods());
        $this->assertContains('web', $send->middleware());
        $this->assertContains('auth:web', $send->middleware());
        $this->assertContains('throttle:6,1', $send->middleware());

        $verify = $routes->getByName('verification.verify');
        $this->assertNotNull($verify, 'verification.verify route must be registered.');
        $this->assertSame('email/verify/{id}/{hash}', $verify->uri());
        $this->assertContains('GET', $verify->methods());
        $this->assertContains('web', $verify->middleware());
        $this->assertContains('auth:web', $verify->middleware());
        $this->assertContains('signed', $verify->middleware());
        $this->assertContains('throttle:6,1', $verify->middleware());
    }
}
