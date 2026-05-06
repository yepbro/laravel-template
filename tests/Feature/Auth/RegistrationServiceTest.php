<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Services\RegisterUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for the RegisterUser service.
 *
 * These tests exercise the service in isolation from the HTTP layer:
 * no FormRequest pipeline, no controller. Normalization (email lowercasing,
 * phone stripping) is assumed to have already occurred before the service
 * is called -- the service only creates the User from clean, validated data.
 */
class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegisterUser $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RegisterUser();
    }

    // -- Creation modes ----------------------------------------------------------

    public function test_register_creates_user_with_email_only(): void
    {
        $user = $this->service->register(
            name: 'Jane Doe',
            email: 'jane@example.com',
            phone: null,
            password: 'password',
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertNull($user->phone);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'phone' => null]);
    }

    public function test_register_creates_user_with_phone_only(): void
    {
        $user = $this->service->register(
            name: 'John Doe',
            email: null,
            phone: '+15551234567',
            password: 'password',
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertNull($user->email);
        $this->assertSame('+15551234567', $user->phone);
        $this->assertDatabaseHas('users', ['email' => null, 'phone' => '+15551234567']);
    }

    public function test_register_creates_user_with_both_email_and_phone(): void
    {
        $user = $this->service->register(
            name: 'Alice Smith',
            email: 'alice@example.com',
            phone: '+15559876543',
            password: 'password',
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('alice@example.com', $user->email);
        $this->assertSame('+15559876543', $user->phone);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'phone' => '+15559876543']);
    }

    // -- Password hashing --------------------------------------------------------

    public function test_register_stores_hashed_password(): void
    {
        $user = $this->service->register(
            name: 'Bob',
            email: 'bob@example.com',
            phone: null,
            password: 'plain-secret',
        );

        $this->assertTrue(Hash::check('plain-secret', $user->password));
        $this->assertNotSame('plain-secret', $user->password);
    }

    // -- Return value ------------------------------------------------------------

    public function test_register_returns_persisted_user_instance(): void
    {
        $user = $this->service->register(
            name: 'Eve',
            email: 'eve@example.com',
            phone: null,
            password: 'password',
        );

        $this->assertNotNull($user->id);
        $this->assertTrue($user->exists);
    }

    // -- Guard: both credentials null --------------------------------------------

    public function test_register_throws_when_both_email_and_phone_are_null(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->register(
            name: 'Ghost',
            email: null,
            phone: null,
            password: 'password',
        );
    }
}
