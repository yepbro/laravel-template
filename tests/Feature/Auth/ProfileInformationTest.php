<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ProfileInformationController;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for project-owned profile information update controller.
 *
 * The controller is mounted at a test-only URI for isolation.
 */
class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    private const UPDATE_URI = '/_auth-test/user/profile-information';

    protected function setUp(): void
    {
        parent::setUp();

        $guard = config('auth_features.guard', 'web');

        Route::put(self::UPDATE_URI, [ProfileInformationController::class, 'update'])
            ->middleware(['web', "auth:{$guard}"]);
    }

    // -- Auth guard ------------------------------------------------------------

    public function test_profile_update_requires_auth(): void
    {
        $response = $this->putJson(self::UPDATE_URI, [
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(401);
    }

    // -- JSON success ----------------------------------------------------------

    public function test_profile_update_json_returns_200_and_updates_name_and_email(): void
    {
        $user = User::factory()->emailOnly()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertEmpty(json_decode((string) $response->getContent(), true));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    // -- Web success -----------------------------------------------------------

    public function test_profile_update_web_redirects_back_with_status(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->put(self::UPDATE_URI, [
            'name'  => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'profile-information-updated');
    }

    // -- Duplicate identifiers -------------------------------------------------

    public function test_profile_update_fails_on_duplicate_email(): void
    {
        User::factory()->emailOnly()->create(['email' => 'taken@example.com']);
        $user = User::factory()->emailOnly()->create(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'Name',
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_fails_on_duplicate_phone(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        User::factory()->phoneVerified()->create(['phone' => '+15551234567']);
        $user = User::factory()->emailAndPhone()->create(['email' => 'user@example.com', 'phone' => '+15559999999']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'Name',
            'email' => 'user@example.com',
            'phone' => '+15551234567',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    // -- Email verification behavior on email change ---------------------------

    public function test_email_change_resets_email_verified_at(): void
    {
        $user = User::factory()->emailOnly()->create([
            'email'             => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => 'new@example.com',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_change_dispatches_verification_notification_when_feature_enabled(): void
    {
        config(['auth_features.features.email_verification' => true]);
        Notification::fake();

        $user = User::factory()->emailOnly()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => 'new@example.com',
        ])->assertStatus(200);

        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_email_change_does_not_send_verification_when_feature_disabled(): void
    {
        config(['auth_features.features.email_verification' => false]);
        Notification::fake();

        $user = User::factory()->emailOnly()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => 'new@example.com',
        ])->assertStatus(200);

        Notification::assertNothingSent();
    }

    // -- Unchanged email preserves verification timestamp ----------------------

    public function test_unchanged_email_preserves_email_verified_at(): void
    {
        $verifiedAt = now()->subDay();
        $user       = User::factory()->emailOnly()->create([
            'email'             => 'same@example.com',
            'email_verified_at' => $verifiedAt,
        ]);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'Updated Name',
            'email' => 'same@example.com',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals($verifiedAt->timestamp, $user->email_verified_at->timestamp);
    }

    // -- Phone verification behavior on phone change ---------------------------

    public function test_phone_change_resets_phone_verified_at(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $user = User::factory()->emailAndPhone()->create([
            'phone'             => '+15551111111',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => '+15552222222',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_unchanged_phone_preserves_phone_verified_at(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $verifiedAt = now()->subDay();
        $user       = User::factory()->emailAndPhone()->create([
            'phone'             => '+15551111111',
            'phone_verified_at' => $verifiedAt,
        ]);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'Updated Name',
            'email' => $user->email,
            'phone' => '+15551111111',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertEquals($verifiedAt->timestamp, $user->phone_verified_at->timestamp);
    }

    // -- Registration mode: phone-only user ------------------------------------

    public function test_phone_only_user_can_update_without_email(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $user = User::factory()->phoneOnly()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'New Name',
            'phone' => $user->phone,
        ]);

        $response->assertStatus(200);
        $this->assertSame('New Name', $user->fresh()->name);
    }

    // -- Registration mode: email-only user ------------------------------------

    public function test_email_only_user_can_update_without_phone(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $user = User::factory()->emailOnly()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'New Name',
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $this->assertSame('New Name', $user->fresh()->name);
    }

    // -- Last identifier cannot be removed ------------------------------------

    public function test_cannot_remove_both_email_and_phone(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $user = User::factory()->emailAndPhone()->create();

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => 'Name',
            'email' => null,
            'phone' => null,
        ]);

        $response->assertStatus(422);
    }

    // -- Phone normalization ---------------------------------------------------

    public function test_phone_is_normalized_before_unique_check_and_persistence(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $user = User::factory()->emailAndPhone()->create(['phone' => '+15559999999']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => '+1 (555) 111-2222',
        ]);

        $response->assertStatus(200);
        $this->assertSame('+15551112222', $user->fresh()->phone);
    }

    public function test_phone_normalization_catches_duplicate_before_persistence(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        // Another user already owns the normalized version of the phone number.
        User::factory()->phoneVerified()->create(['phone' => '+15551112222']);

        $user = User::factory()->emailAndPhone()->create(['phone' => '+15559999999']);

        $response = $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => '+1 (555) 111-2222', // normalizes to +15551112222
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    // -- Email lowercasing -----------------------------------------------------

    public function test_email_is_lowercased_when_lowercase_usernames_enabled(): void
    {
        config(['auth_features.lowercase_usernames' => true]);

        $user = User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $this->actingAs($user)->putJson(self::UPDATE_URI, [
            'name'  => $user->name,
            'email' => 'USER@EXAMPLE.COM',
        ])->assertStatus(200);

        $this->assertSame('user@example.com', $user->fresh()->email);
    }
}
