<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for RegisteredUserController via a test-only route.
 *
 * Tests register a private /_auth-test/register route inside setUp() for
 * isolation from production middleware, allowing fine-grained control over
 * validation and session assertions without throttling.
 */
class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_URI = '/_auth-test/register';

    protected function setUp(): void
    {
        parent::setUp();

        Route::post(self::TEST_URI, RegisteredUserController::class)
            ->middleware(['web']);
    }

    // -- Helpers -----------------------------------------------------------------

    /** @param array<string, string> $overrides */
    private function validEmailPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Test User',
            'email'                 => 'user@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ], $overrides);
    }

    /** @param array<string, string> $overrides */
    private function validPhonePayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Test User',
            'phone'                 => '+15551234567',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ], $overrides);
    }

    /** @param array<string, string> $overrides */
    private function validBothPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Test User',
            'email'                 => 'user@example.com',
            'phone'                 => '+15551234567',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ], $overrides);
    }

    // -- Email mode: successful registration -------------------------------------

    public function test_email_mode_registers_user_and_returns_201_for_json_request(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $response = $this->postJson(self::TEST_URI, $this->validEmailPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_email_mode_logs_user_in_after_registration(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $this->postJson(self::TEST_URI, $this->validEmailPayload());

        $this->assertAuthenticated('web');
    }

    // -- Phone mode: successful registration -------------------------------------

    public function test_phone_mode_registers_user_with_phone_only(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $response = $this->postJson(self::TEST_URI, $this->validPhonePayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['phone' => '+15551234567', 'email' => null]);
    }

    public function test_phone_mode_logs_user_in_after_registration(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $this->postJson(self::TEST_URI, $this->validPhonePayload());

        $this->assertAuthenticated('web');
    }

    // -- Both mode: successful registration --------------------------------------

    public function test_both_mode_registers_user_with_email_and_phone(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $response = $this->postJson(self::TEST_URI, $this->validBothPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'user@example.com', 'phone' => '+15551234567']);
    }

    // -- Email lowercasing -------------------------------------------------------

    public function test_email_is_lowercased_when_lowercase_usernames_enabled(): void
    {
        config([
            'auth_features.registration_mode'   => 'email',
            'auth_features.lowercase_usernames'  => true,
        ]);

        $this->postJson(self::TEST_URI, $this->validEmailPayload(['email' => 'User@EXAMPLE.COM']));

        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'User@EXAMPLE.COM']);
    }

    public function test_email_case_preserved_when_lowercase_usernames_disabled(): void
    {
        config([
            'auth_features.registration_mode'  => 'email',
            'auth_features.lowercase_usernames' => false,
        ]);

        $this->postJson(self::TEST_URI, $this->validEmailPayload(['email' => 'Mixed@EXAMPLE.com']));

        $this->assertDatabaseHas('users', ['email' => 'Mixed@EXAMPLE.com']);
    }

    // -- Phone normalization -----------------------------------------------------

    public function test_phone_is_normalized_before_storage(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $this->postJson(self::TEST_URI, $this->validPhonePayload([
            'phone' => '+1 (555) 123-4567',
        ]));

        $this->assertDatabaseHas('users', ['phone' => '+15551234567']);
        $this->assertDatabaseMissing('users', ['phone' => '+1 (555) 123-4567']);
    }

    // -- Validation: duplicate credentials ---------------------------------------

    public function test_duplicate_email_returns_422(): void
    {
        config(['auth_features.registration_mode' => 'email']);
        User::factory()->emailOnly()->create(['email' => 'user@example.com']);

        $response = $this->postJson(self::TEST_URI, $this->validEmailPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_duplicate_phone_returns_422(): void
    {
        config(['auth_features.registration_mode' => 'phone']);
        User::factory()->withPhone('+15551234567')->create();

        $response = $this->postJson(self::TEST_URI, $this->validPhonePayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    // -- Validation: password confirmation ---------------------------------------

    public function test_password_confirmation_mismatch_returns_422(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $response = $this->postJson(self::TEST_URI, $this->validEmailPayload([
            'password'              => 'Password1!',
            'password_confirmation' => 'different',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // -- Validation: required fields per mode -----------------------------------

    public function test_email_mode_requires_email(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $payload = $this->validEmailPayload();
        unset($payload['email']);

        $response = $this->postJson(self::TEST_URI, $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_phone_mode_requires_phone(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $payload = $this->validPhonePayload();
        unset($payload['phone']);

        $response = $this->postJson(self::TEST_URI, $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_both_mode_requires_email_and_phone(): void
    {
        config(['auth_features.registration_mode' => 'both']);

        $payload = [
            'name'                  => 'Test',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ];

        $response = $this->postJson(self::TEST_URI, $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'phone']);
    }

    // -- Web response ------------------------------------------------------------

    public function test_non_json_request_redirects_to_home(): void
    {
        config([
            'auth_features.registration_mode' => 'email',
            'auth_features.home'              => '/home',
        ]);

        $response = $this->post(self::TEST_URI, $this->validEmailPayload());

        $response->assertRedirect('/home');
    }

    // -- Field isolation: off-mode fields are dropped ----------------------------

    public function test_email_mode_drops_submitted_phone_field(): void
    {
        config(['auth_features.registration_mode' => 'email']);

        $this->postJson(self::TEST_URI, array_merge(
            $this->validEmailPayload(),
            ['phone' => '+15559999999'],
        ));

        $this->assertDatabaseHas('users', ['email' => 'user@example.com', 'phone' => null]);
    }

    public function test_phone_mode_drops_submitted_email_field(): void
    {
        config(['auth_features.registration_mode' => 'phone']);

        $this->postJson(self::TEST_URI, array_merge(
            $this->validPhonePayload(),
            ['email' => 'extra@example.com'],
        ));

        $this->assertDatabaseHas('users', ['phone' => '+15551234567', 'email' => null]);
    }

    // -- Field isolation: duplicate off-mode field does not 422 ------------------

    public function test_email_mode_does_not_422_on_duplicate_phone(): void
    {
        config(['auth_features.registration_mode' => 'email']);
        User::factory()->withPhone('+15559999999')->create();

        $response = $this->postJson(self::TEST_URI, array_merge(
            $this->validEmailPayload(),
            ['phone' => '+15559999999'],
        ));

        $response->assertStatus(201);
    }

    public function test_phone_mode_does_not_422_on_duplicate_email(): void
    {
        config(['auth_features.registration_mode' => 'phone']);
        User::factory()->emailOnly()->create(['email' => 'extra@example.com']);

        $response = $this->postJson(self::TEST_URI, array_merge(
            $this->validPhonePayload(),
            ['email' => 'extra@example.com'],
        ));

        $response->assertStatus(201);
    }

    // -- Invalid mode ------------------------------------------------------------

    public function test_invalid_registration_mode_returns_422_and_no_user_created(): void
    {
        config(['auth_features.registration_mode' => 'fax']);

        $response = $this->postJson(self::TEST_URI, $this->validBothPayload());

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    // -- Events ------------------------------------------------------------------

    public function test_registered_event_is_dispatched_on_registration(): void
    {
        config(['auth_features.registration_mode' => 'email']);
        Event::fake([Registered::class]);

        $this->postJson(self::TEST_URI, $this->validEmailPayload());

        Event::assertDispatched(Registered::class, function (Registered $event): bool {
            return $event->user->email === 'user@example.com';
        });
    }

    // -- Session -----------------------------------------------------------------

    public function test_session_is_usable_after_registration(): void
    {
        // session()->regenerate() is called in the controller; we verify the
        // session remains functional by asserting the user is authenticated.
        // Direct session-ID comparison is omitted -- the test driver shares one
        // session store across the request, making before/after ID comparison
        // unreliable. Authentication post-regeneration is the observable outcome.
        config(['auth_features.registration_mode' => 'email']);

        $this->postJson(self::TEST_URI, $this->validEmailPayload());

        $this->assertAuthenticated('web');
    }

    // -- XHR branch --------------------------------------------------------------

    public function test_xhr_request_with_x_requested_with_header_returns_201(): void
    {
        // Verifies the expectsJson() branch via a plain form POST carrying
        // X-Requested-With: XMLHttpRequest and Accept: */* -- the headers a
        // real browser XHR sends when no Accept is set explicitly. Laravel's
        // test client defaults to Accept: text/html so we override it here to
        // match the real-world XHR condition rather than postJson()'s
        // Accept: application/json path.
        config(['auth_features.registration_mode' => 'email']);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => '*/*',
        ])->post(self::TEST_URI, $this->validEmailPayload());

        $response->assertStatus(201);
    }
}
