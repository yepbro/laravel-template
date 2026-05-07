<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_account_via_json_route(): void
    {
        $this->deleteJson('/user', [
            'current_password' => 'password',
        ])->assertUnauthorized();
    }

    public function test_wrong_current_password_returns_validation_error_without_deleting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/user', [
            'current_password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $user->refresh();
        $this->assertNull($user->deleted_at);
    }

    public function test_correct_password_soft_deletes_user_returns_login_redirect_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/user', [
            'current_password' => 'password',
        ])
            ->assertOk()
            ->assertJson([
                'redirect' => route('login', [], absolute: true),
            ]);

        $this->assertSoftDeleted($user);

        $this->deleteJson('/user', [
            'current_password' => 'password',
        ])->assertUnauthorized();
    }

    public function test_soft_deleted_users_cannot_log_in_via_json_credentials(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/user', [
            'current_password' => 'password',
        ])->assertOk();

        $this->postJson('/login', [
            'login'    => $user->email,
            'password' => 'password',
        ])->assertUnprocessable();
    }
}
