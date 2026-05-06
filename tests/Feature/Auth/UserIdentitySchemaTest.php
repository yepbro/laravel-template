<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests for multi-identity (email + phone) database schema and User model helpers.
 */
class UserIdentitySchemaTest extends TestCase
{
    use RefreshDatabase;

    // -- Schema: email nullable --------------------------------------------------

    public function test_email_can_be_null_for_phone_only_user(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '+15550001111']);

        $this->assertNull($user->fresh()->email);
    }

    public function test_email_remains_unique_when_non_null(): void
    {
        $email = 'shared@example.com';
        User::factory()->create(['email' => $email]);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => $email]);
    }

    // -- Schema: phone -----------------------------------------------------------

    public function test_phone_column_persists(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111']);

        $this->assertSame('+15550001111', $user->fresh()->phone);
    }

    public function test_phone_can_be_null(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->assertNull($user->fresh()->phone);
    }

    public function test_multiple_users_can_have_null_phone(): void
    {
        User::factory()->count(3)->create(['phone' => null]);

        $this->assertSame(3, User::whereNull('phone')->count());
    }

    public function test_duplicate_non_null_phone_violates_uniqueness(): void
    {
        User::factory()->create(['phone' => '+15550001111']);

        $this->expectException(QueryException::class);

        User::factory()->create(['phone' => '+15550001111']);
    }

    // -- Schema: phone_verified_at -----------------------------------------------

    public function test_phone_verified_at_casts_to_carbon_datetime(): void
    {
        $user = User::factory()->create(['phone_verified_at' => now()]);

        $this->assertInstanceOf(Carbon::class, $user->fresh()->phone_verified_at);
    }

    public function test_phone_verified_at_can_be_null(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    // -- Helper methods ----------------------------------------------------------

    public function test_has_email_returns_true_when_email_present(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->assertTrue($user->hasEmail());
    }

    public function test_has_email_returns_false_when_email_null(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '+15550001111']);

        $this->assertFalse($user->hasEmail());
    }

    public function test_has_phone_returns_true_when_phone_present(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111']);

        $this->assertTrue($user->hasPhone());
    }

    public function test_has_phone_returns_false_when_phone_null(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->assertFalse($user->hasPhone());
    }

    public function test_has_verified_email_returns_true_when_email_verified_at_is_set(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_has_verified_email_returns_false_when_email_verified_at_is_null(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_has_verified_email_returns_false_when_email_is_null_even_if_email_verified_at_is_set(): void
    {
        $user = User::factory()->create(['email' => null, 'email_verified_at' => now(), 'phone' => '+15550001111']);

        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_has_verified_phone_returns_true_when_phone_verified_at_is_set(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => now()]);

        $this->assertTrue($user->hasVerifiedPhone());
    }

    public function test_has_verified_phone_returns_false_when_phone_verified_at_is_null(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => null]);

        $this->assertFalse($user->hasVerifiedPhone());
    }

    public function test_has_verified_phone_returns_false_when_phone_is_null_even_if_phone_verified_at_is_set(): void
    {
        $user = User::factory()->create(['phone' => null, 'phone_verified_at' => now()]);

        $this->assertFalse($user->hasVerifiedPhone());
    }

    public function test_mark_phone_as_verified_sets_phone_verified_at(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => null]);

        $result = $user->markPhoneAsVerified();

        $this->assertTrue($result);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_mark_phone_as_verified_returns_false_when_already_verified(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => now()]);

        $result = $user->markPhoneAsVerified();

        $this->assertFalse($result);
    }

    public function test_mark_phone_as_verified_returns_false_and_leaves_phone_verified_at_null_when_phone_is_missing(): void
    {
        $user = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);

        $result = $user->markPhoneAsVerified();

        $this->assertFalse($result);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_mark_phone_as_unverified_clears_phone_verified_at(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => now()]);

        $result = $user->markPhoneAsUnverified();

        $this->assertTrue($result);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_mark_phone_as_unverified_returns_false_when_already_unverified(): void
    {
        $user = User::factory()->create(['phone' => '+15550001111', 'phone_verified_at' => null]);

        $result = $user->markPhoneAsUnverified();

        $this->assertFalse($result);
    }

    // -- Factory states ----------------------------------------------------------

    public function test_unverified_state_nulls_email_verified_at_for_backward_compatibility(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email);
    }

    public function test_with_phone_state_sets_given_phone(): void
    {
        $user = User::factory()->withPhone('+15550009999')->create();

        $this->assertSame('+15550009999', $user->phone);
    }

    public function test_with_phone_state_generates_phone_when_null_arg(): void
    {
        $user = User::factory()->withPhone()->create();

        $this->assertNotNull($user->phone);
    }

    public function test_phone_verified_state_sets_phone_verified_at(): void
    {
        $user = User::factory()->withPhone()->phoneVerified()->create();

        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_phone_verified_state_alone_creates_user_with_phone_and_phone_verified_at(): void
    {
        $user = User::factory()->phoneVerified()->create();

        $this->assertNotNull($user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_phone_unverified_state_nulls_phone_verified_at(): void
    {
        $user = User::factory()->withPhone()->phoneUnverified()->create();

        $this->assertNull($user->phone_verified_at);
    }

    public function test_email_only_state_has_email_and_no_phone(): void
    {
        $user = User::factory()->emailOnly()->create();

        $this->assertNotNull($user->email);
        $this->assertNull($user->phone);
    }

    public function test_phone_only_state_has_phone_and_no_email(): void
    {
        $user = User::factory()->phoneOnly()->create();

        $this->assertNull($user->email);
        $this->assertNotNull($user->phone);
    }

    public function test_email_and_phone_state_has_both(): void
    {
        $user = User::factory()->emailAndPhone()->create();

        $this->assertNotNull($user->email);
        $this->assertNotNull($user->phone);
    }

    public function test_email_only_then_phone_verified_creates_user_with_phone_and_phone_verified_at(): void
    {
        // phoneVerified() applied after emailOnly() generates a phone (order-sensitive behavior)
        $user = User::factory()->emailOnly()->phoneVerified()->create();

        $this->assertNotNull($user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_with_phone_then_phone_only_preserves_provided_phone(): void
    {
        $user = User::factory()->withPhone('+15559990000')->phoneOnly()->create();

        $this->assertSame('+15559990000', $user->phone);
        $this->assertNull($user->email);
    }

    public function test_with_phone_then_email_and_phone_preserves_provided_phone(): void
    {
        $user = User::factory()->withPhone('+15559990001')->emailAndPhone()->create();

        $this->assertSame('+15559990001', $user->phone);
        $this->assertNotNull($user->email);
    }
}
