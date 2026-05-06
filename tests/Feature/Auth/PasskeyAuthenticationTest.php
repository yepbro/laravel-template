<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasskeyConfirmationController;
use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Controllers\Auth\PasskeyRegistrationController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Tests\TestCase;

/**
 * Feature tests for project-owned passkey controllers.
 *
 * All endpoints are mounted at /_auth-test/... for isolation. Full WebAuthn ceremony
 * crypto is skipped by mocking package actions (VerifyPasskey, StorePasskey,
 * DeletePasskey); the package owns cryptographic validation.
 */
class PasskeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_OPTIONS_URI    = '/_auth-test/passkeys/login/options';
    private const LOGIN_URI            = '/_auth-test/passkeys/login';
    private const CONFIRM_OPTIONS_URI  = '/_auth-test/passkeys/confirm/options';
    private const CONFIRM_URI          = '/_auth-test/passkeys/confirm';
    private const REGISTER_OPTIONS_URI = '/_auth-test/user/passkeys/options';
    private const REGISTER_STORE_URI   = '/_auth-test/user/passkeys';
    private const LIST_URI             = '/_auth-test/user/passkeys';

    protected function setUp(): void
    {
        parent::setUp();

        $guard    = config('auth_features.guard', 'web');
        $throttle = 'throttle:6,1';

        Route::get(self::LOGIN_OPTIONS_URI, [PasskeyLoginController::class, 'index'])
            ->middleware(['web', "guest:{$guard}", $throttle]);

        Route::post(self::LOGIN_URI, [PasskeyLoginController::class, 'store'])
            ->middleware(['web', "guest:{$guard}", $throttle]);

        Route::get(self::CONFIRM_OPTIONS_URI, [PasskeyConfirmationController::class, 'index'])
            ->middleware(['web', "auth:{$guard}", $throttle]);

        Route::post(self::CONFIRM_URI, [PasskeyConfirmationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}", $throttle]);

        Route::get(self::REGISTER_OPTIONS_URI, [PasskeyRegistrationController::class, 'index'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm', $throttle]);

        Route::post(self::REGISTER_STORE_URI, [PasskeyRegistrationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm', $throttle]);

        Route::delete('/_auth-test/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm']);

        Route::get('/_auth-test/user/passkeys', [PasskeyRegistrationController::class, 'list'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm']);
    }

    // -- Helpers ---------------------------------------------------------------

    /** @return array<string, mixed> */
    private function passwordConfirmedSession(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    /**
     * Minimal assertion credential (login / confirm).
     *
     * clientDataJSON decodes to {"type":"webauthn.get","challenge":"...","origin":"..."},
     * authenticatorData is exactly 37 bytes (rpIdHash+flags+counter, all zeros),
     * signature is 3 zero bytes. These satisfy the webauthn-lib Symfony Serializer
     * without requiring a real browser ceremony. Actual crypto is mocked via
     * VerifyPasskey.
     *
     * @return array<string, mixed>
     */
    private function fakeVerificationCredential(): array
    {
        // clientDataJSON = {"type":"webauthn.get","challenge":"AAAAAAAAAAAAAAAAAAAAAA","origin":"http://localhost"}
        $clientDataB64 = 'eyJ0eXBlIjoid2ViYXV0aG4uZ2V0IiwiY2hhbGxlbmdlIjoiQUFBQUFBQUFBQUFBQUFBQUFBQUFBQSIsIm9yaWdpbiI6Imh0dHA6XC9cL2xvY2FsaG9zdCJ9';

        return [
            'id'     => 'AAAAAAAAAAAAAAAAAAAAAA',
            'rawId'  => 'AAAAAAAAAAAAAAAAAAAAAA',
            'type'   => 'public-key',
            'response' => [
                'clientDataJSON'    => $clientDataB64,
                // 50 A chars = 37 bytes of zeros: rpIdHash(32) + flags(1) + counter(4)
                'authenticatorData' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
                'signature'         => 'AAAA',
            ],
        ];
    }

    /**
     * Minimal attestation credential (registration).
     *
     * attestationObject is valid CBOR: {fmt:"none",attStmt:{},authData:<37 zero bytes>}.
     *
     * @return array<string, mixed>
     */
    private function fakeRegistrationCredential(): array
    {
        // clientDataJSON = {"type":"webauthn.create","challenge":"...","origin":"..."}
        $clientDataB64 = 'eyJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIiwiY2hhbGxlbmdlIjoiQUFBQUFBQUFBQUFBQUFBQUFBQUFBQSIsIm9yaWdpbiI6Imh0dHA6XC9cL2xvY2FsaG9zdCJ9';

        // CBOR map: {fmt:"none",attStmt:{},authData:<37 zero bytes>}
        $attestationB64 = 'o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YVglAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

        return [
            'id'     => 'AAAAAAAAAAAAAAAAAAAAAA',
            'rawId'  => 'AAAAAAAAAAAAAAAAAAAAAA',
            'type'   => 'public-key',
            'response' => [
                'clientDataJSON'    => $clientDataB64,
                'attestationObject' => $attestationB64,
            ],
        ];
    }

    /**
     * Create a Passkey record owned by the given user.
     */
    private function createPasskey(User $user, string $name = 'Test Key'): Passkey
    {
        // credential_id must be a valid base64url string because GenerateVerificationOptions
        // calls Base64UrlSafe::decodeNoPadding() on it when building allowCredentials.
        $credentialId = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

        return $user->passkeys()->create([
            'name'          => $name,
            'credential_id' => $credentialId,
            'credential'    => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
        ]);
    }

    /**
     * Serialise real verification options into the session payload format.
     */
    private function serialisedVerificationOptions(?User $user = null): string
    {
        $generate = app(GenerateVerificationOptions::class);
        $options  = $generate($user instanceof User ? $user : null);

        return WebAuthn::toJson($options);
    }

    /**
     * Serialise real registration options for the given user.
     */
    private function serialisedRegistrationOptions(User $user): string
    {
        $options = app(GenerateRegistrationOptions::class)($user);

        return WebAuthn::toJson($options);
    }

    // -- Package auto-routes absent -------------------------------------------

    public function test_package_passkey_routes_are_not_auto_registered(): void
    {
        $packageRouteNames = [
            'passkey.login-options',
            'passkey.login',
            'passkey.confirm-options',
            'passkey.confirm',
            'passkey.registration-options',
            'passkey.store',
            'passkey.destroy',
        ];

        foreach ($packageRouteNames as $name) {
            $this->assertNull(
                Route::getRoutes()->getByName($name),
                "Package route '{$name}' should not be auto-registered; Passkeys::ignoreRoutes() must fire before boot.",
            );
        }
    }

    public function test_passkeys_should_register_routes_returns_false(): void
    {
        $this->assertFalse(Passkeys::shouldRegisterRoutes());
    }

    // -- Migration / model ----------------------------------------------------

    public function test_passkeys_table_exists_and_user_relation_works(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $this->assertDatabaseHas('passkeys', [
            'user_id' => $user->getKey(),
            'name'    => 'Test Key',
        ]);

        $this->assertTrue($user->passkeys()->where('id', $passkey->id)->exists());
        $this->assertInstanceOf(Passkey::class, $user->passkeys()->first());
    }

    public function test_user_implements_passkey_user_interface(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(PasskeyUser::class, $user);
    }

    // -- Passkey username for phone-only users --------------------------------

    public function test_email_user_passkey_username_is_email(): void
    {
        $user = User::factory()->emailOnly()->create(['email' => 'alice@example.com']);

        $this->assertSame('alice@example.com', $user->getPasskeyUsername());
    }

    public function test_phone_only_user_passkey_username_falls_back_to_phone(): void
    {
        $user = User::factory()->phoneOnly()->create(['phone' => '+15551234567']);

        $this->assertNull($user->email);
        $this->assertSame('+15551234567', $user->getPasskeyUsername());
    }

    public function test_passkey_username_never_empty_for_any_user(): void
    {
        $emailUser = User::factory()->emailOnly()->create();
        $phoneUser = User::factory()->phoneOnly()->create();

        $this->assertNotEmpty($emailUser->getPasskeyUsername());
        $this->assertNotEmpty($phoneUser->getPasskeyUsername());
    }

    public function test_blank_email_username_falls_back_to_phone(): void
    {
        $user = User::factory()->create([
            'email' => '   ',
            'phone' => '+15559990001',
        ]);

        $this->assertSame('+15559990001', $user->getPasskeyUsername());
    }

    public function test_blank_email_and_phone_username_falls_back_to_auth_identifier(): void
    {
        $user = User::factory()->create([
            'email' => '',
            'phone' => '   ',
        ]);

        $username = $user->getPasskeyUsername();

        $this->assertNotEmpty($username);
        $this->assertSame((string) $user->getAuthIdentifier(), $username);
    }

    public function test_whitespace_only_username_fields_treated_as_blank(): void
    {
        $user = User::factory()->create([
            'email' => '  ',
            'phone' => "\t",
        ]);

        $username = $user->getPasskeyUsername();

        $this->assertNotEmpty($username);
        $this->assertStringNotContainsString(' ', $username);
    }

    // -- Passkey display name -------------------------------------------------

    public function test_display_name_prefers_name_over_email(): void
    {
        $user = User::factory()->create([
            'name'  => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame('Alice', $user->getPasskeyDisplayName());
    }

    public function test_blank_name_display_name_falls_back_to_email(): void
    {
        $user = User::factory()->create([
            'name'  => '   ',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame('alice@example.com', $user->getPasskeyDisplayName());
    }

    public function test_blank_name_and_email_display_name_falls_back_to_phone(): void
    {
        $user = User::factory()->create([
            'name'  => '',
            'email' => '',
            'phone' => '+15559990002',
        ]);

        $this->assertSame('+15559990002', $user->getPasskeyDisplayName());
    }

    public function test_blank_name_email_phone_display_name_falls_back_to_auth_identifier(): void
    {
        $user = User::factory()->create([
            'name'  => '  ',
            'email' => '',
            'phone' => '   ',
        ]);

        $displayName = $user->getPasskeyDisplayName();

        $this->assertNotEmpty($displayName);
        $this->assertSame((string) $user->getAuthIdentifier(), $displayName);
    }

    public function test_whitespace_only_display_name_fields_treated_as_blank(): void
    {
        $user = User::factory()->create([
            'name'  => "\t",
            'email' => '   ',
            'phone' => null,
        ]);

        $displayName = $user->getPasskeyDisplayName();

        $this->assertNotEmpty($displayName);
    }

    // -- Login options --------------------------------------------------------

    public function test_login_options_returns_json_for_guest(): void
    {
        $response = $this->getJson(self::LOGIN_OPTIONS_URI);

        $response->assertOk();
        $response->assertJsonStructure(['options']);
    }

    public function test_login_options_stores_challenge_in_session(): void
    {
        $response = $this->getJson(self::LOGIN_OPTIONS_URI);

        $response->assertOk();
        $response->assertSessionHas('passkey.verification_options');
    }

    public function test_login_options_redirects_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(self::LOGIN_OPTIONS_URI);

        $response->assertRedirect();
    }

    // -- Login submit ---------------------------------------------------------

    public function test_login_submit_returns_422_for_missing_credential(): void
    {
        $response = $this->postJson(self::LOGIN_URI, []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrorFor('credential');
    }

    public function test_login_submit_returns_422_when_session_has_no_options(): void
    {
        // Provide a structurally valid credential but no session options;
        // verificationOptions() will throw ValidationException.
        $response = $this->postJson(self::LOGIN_URI, [
            'credential' => $this->fakeVerificationCredential(),
        ]);

        $response->assertUnprocessable();
    }

    public function test_login_submit_logs_in_user_via_verify_passkey_action(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $serialized = $this->serialisedVerificationOptions();

        $mock = $this->mock(VerifyPasskey::class);
        $mock->shouldReceive('__invoke')->once()->andReturn($passkey);

        $response = $this->withSession(['passkey.verification_options' => $serialized])
            ->postJson(self::LOGIN_URI, [
                'credential' => $this->fakeVerificationCredential(),
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertOk();
        $response->assertJsonStructure(['redirect']);
    }

    public function test_login_submit_honours_remember_flag(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $serialized = $this->serialisedVerificationOptions();

        $mock = $this->mock(VerifyPasskey::class);
        $mock->shouldReceive('__invoke')->once()->andReturn($passkey);

        $this->withSession(['passkey.verification_options' => $serialized])
            ->postJson(self::LOGIN_URI, [
                'credential' => $this->fakeVerificationCredential(),
                'remember'   => true,
            ]);

        $this->assertAuthenticatedAs($user);
    }

    // -- Confirm options ------------------------------------------------------

    public function test_confirm_options_requires_authentication(): void
    {
        $response = $this->getJson(self::CONFIRM_OPTIONS_URI);

        $response->assertUnauthorized();
    }

    public function test_confirm_options_returns_user_scoped_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(self::CONFIRM_OPTIONS_URI);

        $response->assertOk();
        $response->assertJsonStructure(['options']);
    }

    public function test_confirm_options_stores_challenge_in_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(self::CONFIRM_OPTIONS_URI);

        $response->assertSessionHas('passkey.verification_options');
    }

    // -- Confirm submit -------------------------------------------------------

    public function test_confirm_submit_requires_authentication(): void
    {
        $response = $this->postJson(self::CONFIRM_URI, []);

        $response->assertUnauthorized();
    }

    public function test_confirm_submit_sets_password_confirmed_at_in_session(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $serialized = $this->serialisedVerificationOptions($user);

        $mock = $this->mock(VerifyPasskey::class);
        $mock->shouldReceive('__invoke')->once()->andReturn($passkey);

        $response = $this->actingAs($user)
            ->withSession(['passkey.verification_options' => $serialized])
            ->postJson(self::CONFIRM_URI, [
                'credential' => $this->fakeVerificationCredential(),
            ]);

        $response->assertOk();
        $response->assertSessionHas('auth.password_confirmed_at');
    }

    // -- Register options -----------------------------------------------------

    public function test_register_options_requires_authentication(): void
    {
        $response = $this->getJson(self::REGISTER_OPTIONS_URI);

        $response->assertUnauthorized();
    }

    public function test_register_options_requires_password_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(self::REGISTER_OPTIONS_URI);

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_register_options_returns_json_when_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::REGISTER_OPTIONS_URI);

        $response->assertOk();
        $response->assertJsonStructure(['options']);
    }

    public function test_register_options_stores_challenge_in_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::REGISTER_OPTIONS_URI);

        $response->assertSessionHas('passkey.registration_options');
    }

    // -- Register store -------------------------------------------------------

    public function test_register_store_requires_password_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(self::REGISTER_STORE_URI, [
            'name' => 'My Key',
        ]);

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_register_store_returns_422_for_missing_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->postJson(self::REGISTER_STORE_URI, ['name' => 'My Key']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrorFor('credential');
    }

    public function test_register_store_creates_passkey_via_store_action(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user, 'New Key');

        $serialized = $this->serialisedRegistrationOptions($user);

        $mock = $this->mock(StorePasskey::class);
        $mock->shouldReceive('__invoke')->once()->andReturn($passkey);

        $response = $this->actingAs($user)
            ->withSession([
                ...$this->passwordConfirmedSession(),
                'passkey.registration_options' => $serialized,
            ])
            ->postJson(self::REGISTER_STORE_URI, [
                'name'       => 'New Key',
                'credential' => $this->fakeRegistrationCredential(),
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'passkey-registered']);
    }

    // -- Delete passkey -------------------------------------------------------

    public function test_delete_requires_authentication(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $response = $this->deleteJson("/_auth-test/user/passkeys/{$passkey->id}");

        $response->assertUnauthorized();
    }

    public function test_delete_rejects_non_owner_with_403(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $passkey = $this->createPasskey($owner);

        $response = $this->actingAs($other)
            ->withSession($this->passwordConfirmedSession())
            ->deleteJson("/_auth-test/user/passkeys/{$passkey->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_delete_owner_passkey_succeeds(): void
    {
        $user    = User::factory()->create();
        $passkey = $this->createPasskey($user);

        $mock = $this->mock(DeletePasskey::class);
        $mock->shouldReceive('__invoke')->once();

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->deleteJson("/_auth-test/user/passkeys/{$passkey->id}");

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'passkey-deleted']);
    }

    // -- List passkeys ---------------------------------------------------------

    public function test_list_requires_authentication(): void
    {
        $response = $this->getJson(self::LIST_URI);

        $response->assertUnauthorized();
    }

    public function test_list_requires_password_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(self::LIST_URI);

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_list_returns_only_current_users_passkeys(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->createPasskey($owner, 'My Key');
        $this->createPasskey($other, 'Other Key');

        $response = $this->actingAs($owner)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::LIST_URI);

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'My Key']);
        $response->assertJsonMissing(['name' => 'Other Key']);
    }

    public function test_list_omits_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $this->createPasskey($user, 'Safe Key');

        $response = $this->actingAs($user)
            ->withSession($this->passwordConfirmedSession())
            ->getJson(self::LIST_URI);

        $response->assertOk();

        $passkeys = $response->json();
        $this->assertIsArray($passkeys);
        $this->assertNotEmpty($passkeys);

        $first = $passkeys[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('authenticator', $first);
        $this->assertArrayHasKey('last_used_at', $first);
        $this->assertArrayHasKey('created_at', $first);
        $this->assertArrayNotHasKey('credential', $first);
        $this->assertArrayNotHasKey('credential_id', $first);
        $this->assertArrayNotHasKey('user_id', $first);
    }
}
