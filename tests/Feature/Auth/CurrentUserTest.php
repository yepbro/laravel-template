<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_cannot_fetch_current_user_json(): void
    {
        $this->getJson('/user')->assertUnauthorized();
    }

    public function test_authenticated_user_receives_minimal_json_payload(): void
    {
        $user = User::factory()->create([
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/user');

        $response->assertOk();
        $response->assertJsonPath('id', $user->getKey());
        $response->assertJsonPath('name', 'Jane Doe');
        $response->assertJsonPath('email', 'jane@example.com');
        $response->assertJsonPath('phone', $user->phone);
        $response->assertJsonStructure([
            'email_verified_at',
            'phone_verified_at',
        ]);
    }
}
