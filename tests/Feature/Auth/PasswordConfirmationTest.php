<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ConfirmedPasswordStatusController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for project-owned password confirmation controllers.
 *
 * Controllers are mounted at test-only URIs for isolation.
 */
class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private const CONFIRM_URI = '/_auth-test/user/confirm-password';
    private const STATUS_URI  = '/_auth-test/user/confirmed-password-status';

    protected function setUp(): void
    {
        parent::setUp();

        $guard = config('auth_features.guard', 'web');

        Route::get(self::CONFIRM_URI, [ConfirmablePasswordController::class, 'show'])
            ->middleware(['web', "auth:{$guard}"]);

        Route::post(self::CONFIRM_URI, [ConfirmablePasswordController::class, 'store'])
            ->middleware(['web', "auth:{$guard}"]);

        Route::get(self::STATUS_URI, ConfirmedPasswordStatusController::class)
            ->middleware(['web', "auth:{$guard}"]);
    }

    // -- GET show: auth guard ---------------------------------------------------

    public function test_confirm_password_show_requires_auth(): void
    {
        $response = $this->get(self::CONFIRM_URI);

        $response->assertRedirect(route('login'));
    }

    // -- GET show: views enabled ------------------------------------------------

    public function test_confirm_password_show_returns_view_when_views_enabled(): void
    {
        config(['auth_features.views' => true]);

        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->get(self::CONFIRM_URI);

        $response->assertStatus(200);
        $response->assertViewIs('auth.confirm-password');
    }

    // -- GET show: views disabled -----------------------------------------------

    public function test_confirm_password_show_redirects_home_when_views_disabled(): void
    {
        config(['auth_features.views' => false]);

        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->get(self::CONFIRM_URI);

        $response->assertRedirect(config('auth_features.home', '/home'));
    }

    // -- POST store: correct password JSON -------------------------------------

    public function test_confirm_password_json_returns_201_and_sets_session_on_correct_password(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->postJson(self::CONFIRM_URI, [
            'password' => 'password',
        ]);

        $response->assertStatus(201);
        $response->assertSessionHas('auth.password_confirmed_at');
    }

    // -- POST store: correct password web redirect -----------------------------

    public function test_confirm_password_web_redirects_intended_on_correct_password(): void
    {
        $user = User::factory()->emailOnly()->create();

        // Simulate a previous intended redirect being stored in session.
        $response = $this->actingAs($user)
            ->withSession(['url.intended' => '/some-protected-page'])
            ->post(self::CONFIRM_URI, ['password' => 'password']);

        $response->assertRedirect('/some-protected-page');
    }

    public function test_confirm_password_web_redirects_home_when_no_intended_url(): void
    {
        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->post(self::CONFIRM_URI, ['password' => 'password']);

        $response->assertRedirect(config('auth_features.home', '/home'));
    }

    // -- POST store: wrong password --------------------------------------------

    public function test_confirm_password_returns_422_with_password_error_on_wrong_password(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->postJson(self::CONFIRM_URI, [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_confirm_password_does_not_set_session_on_wrong_password(): void
    {
        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user)->postJson(self::CONFIRM_URI, [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionMissing('auth.password_confirmed_at');
    }

    // -- GET status: not confirmed ---------------------------------------------

    public function test_confirmed_password_status_returns_false_when_not_confirmed(): void
    {
        $user     = User::factory()->emailOnly()->create();
        $response = $this->actingAs($user)->getJson(self::STATUS_URI);

        $response->assertStatus(200);
        $response->assertJson(['confirmed' => false]);
    }

    // -- GET status: recently confirmed ----------------------------------------

    public function test_confirmed_password_status_returns_true_when_recently_confirmed(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson(self::STATUS_URI)
            ->assertStatus(200)
            ->assertJson(['confirmed' => true]);
    }

    // -- GET status: expired ---------------------------------------------------

    public function test_confirmed_password_status_returns_false_when_confirmation_expired(): void
    {
        $user    = User::factory()->emailOnly()->create();
        $timeout = (int) config('auth.password_timeout', 10800);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time() - $timeout - 1])
            ->getJson(self::STATUS_URI)
            ->assertStatus(200)
            ->assertJson(['confirmed' => false]);
    }

    // -- Non-default guard -----------------------------------------------------

    public function test_confirm_password_non_default_guard_is_honored(): void
    {
        config([
            'auth.guards.auth_test'  => ['driver' => 'session', 'provider' => 'users'],
            'auth_features.guard'    => 'auth_test',
        ]);

        Route::post('/_auth-test/user/confirm-password-guard', [ConfirmablePasswordController::class, 'store'])
            ->middleware(['web', 'auth:auth_test']);

        $user = User::factory()->emailOnly()->create();

        $response = $this->actingAs($user, 'auth_test')
            ->postJson('/_auth-test/user/confirm-password-guard', [
                'password' => 'password',
            ]);

        $response->assertStatus(201);
        $this->assertGuest('web');
    }
}
