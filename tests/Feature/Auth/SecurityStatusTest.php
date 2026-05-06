<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Feature tests for GET /user/security-status.
 *
 * The route is registered in routes/web.php and is available in all test runs
 * without any custom setUp route registration.
 *
 * Response shape: {
 *   password_confirmed: bool,
 *   two_factor_enabled: bool,
 *   two_factor_confirmed: bool,
 * }
 */
class SecurityStatusTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/user/security-status';

    // -- Auth guard ------------------------------------------------------------

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }

    // -- Response structure ----------------------------------------------------

    public function test_response_contains_all_required_keys(): void
    {
        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonStructure([
            'password_confirmed',
            'two_factor_enabled',
            'two_factor_confirmed',
        ]);
    }

    // -- password_confirmed ----------------------------------------------------

    public function test_password_confirmed_is_false_when_session_has_no_timestamp(): void
    {
        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['password_confirmed' => false]);
    }

    public function test_password_confirmed_is_true_when_session_has_recent_timestamp(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson(self::URI)
            ->assertOk()
            ->assertJson(['password_confirmed' => true]);
    }

    public function test_password_confirmed_is_false_when_confirmation_expired(): void
    {
        $user    = User::factory()->emailOnly()->create();
        $timeout = (int) config('auth.password_timeout', 10800);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time() - $timeout - 1])
            ->getJson(self::URI)
            ->assertOk()
            ->assertJson(['password_confirmed' => false]);
    }

    // -- two_factor_enabled / two_factor_confirmed without 2FA -----------------

    public function test_two_factor_flags_are_false_for_user_without_2fa(): void
    {
        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([
            'two_factor_enabled'   => false,
            'two_factor_confirmed' => false,
        ]);
    }

    // -- two_factor_enabled / two_factor_confirmed with pending 2FA setup ------

    public function test_two_factor_flags_are_false_when_secret_exists_but_not_confirmed(): void
    {
        $user = User::factory()->emailOnly()->create();
        $user->forceFill([
            'two_factor_secret'       => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => null,
        ])->save();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([
            'two_factor_enabled'   => false,
            'two_factor_confirmed' => false,
        ]);
    }

    // -- two_factor_enabled / two_factor_confirmed with confirmed 2FA ----------

    public function test_two_factor_flags_are_true_for_user_with_fully_enabled_2fa(): void
    {
        $user = User::factory()->emailOnly()->create();
        $user->forceFill([
            'two_factor_secret'       => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([
            'two_factor_enabled'   => true,
            'two_factor_confirmed' => true,
        ]);
    }
}
