<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Channels\FakePhoneOtpChannel;
use App\Auth\Services\PhoneOtpManager;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Service-level feature tests for PhoneOtpManager.
 *
 * These tests exercise PhoneOtpManager directly -- without routing through HTTP --
 * to pin single-use semantics and verify that verify() is atomic.
 */
class PhoneOtpManagerTest extends TestCase
{
    use RefreshDatabase;

    private PhoneOtpManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new PhoneOtpManager(new FakePhoneOtpChannel());
    }

    // == Atomicity ================================================================

    /**
     * Proves that verify() wraps OTP consumption in a database transaction.
     *
     * Without a transaction (current non-atomic code), TransactionBeginning is
     * never fired by verify(), so $started stays false and this test FAILS (RED).
     * After wrapping verify() in DB::transaction(), the event fires and the test
     * PASSES (GREEN).
     *
     * True concurrent-read safety is guaranteed by lockForUpdate() inside the
     * transaction; the event listener here verifies the transaction boundary exists.
     */
    public function test_verify_wraps_consume_in_database_transaction(): void
    {
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $started = false;
        $this->app['events']->listen(TransactionBeginning::class, function () use (&$started): void {
            $started = true;
        });

        $this->manager->verify($phone, 'verification', $code);

        $this->assertTrue($started, 'verify() must wrap OTP consumption in a DB transaction for atomic single-use enforcement.');
    }

    // == Single-use semantics =====================================================

    public function test_verify_returns_true_for_correct_code(): void
    {
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $this->assertTrue($this->manager->verify($phone, 'verification', $code));
    }

    public function test_verify_marks_row_consumed_at_on_success(): void
    {
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $this->manager->verify($phone, 'verification', $code);

        $row = DB::table('phone_otps')->where('phone', $phone)->first();
        $this->assertNotNull($row?->consumed_at, 'verify() must set consumed_at on the OTP row before returning true.');
    }

    public function test_verify_second_call_with_same_code_returns_false(): void
    {
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $first  = $this->manager->verify($phone, 'verification', $code);
        $second = $this->manager->verify($phone, 'verification', $code);

        $this->assertTrue($first, 'First verify call must return true for a correct code.');
        $this->assertFalse($second, 'Second verify call with the same code must return false (single-use).');
    }

    public function test_verify_returns_false_when_row_already_consumed(): void
    {
        $phone = '+15551234567';
        $this->manager->send($phone, 'verification');

        // Manually mark the row consumed to simulate a prior successful verification.
        DB::table('phone_otps')->where('phone', $phone)->update(['consumed_at' => now()]);

        $result = $this->manager->verify($phone, 'verification', '000000');

        $this->assertFalse($result, 'verify() must return false when consumed_at is already set.');
    }

    // == Wrong / expired codes ====================================================

    public function test_verify_returns_false_for_wrong_code(): void
    {
        $phone = '+15551234567';
        $this->manager->send($phone, 'verification');

        $this->assertFalse($this->manager->verify($phone, 'verification', '000000'));
    }

    public function test_verify_returns_false_for_expired_code(): void
    {
        config(['auth_features.phone_otp.expires_minutes' => 10]);
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $this->travel(15)->minutes();

        $this->assertFalse($this->manager->verify($phone, 'verification', $code));
    }

    public function test_verify_returns_false_after_max_attempts_exhausted(): void
    {
        config(['auth_features.phone_otp.max_attempts' => 2]);
        $phone = '+15551234567';
        $code  = $this->manager->send($phone, 'verification');

        $this->manager->verify($phone, 'verification', '000000');
        $this->manager->verify($phone, 'verification', '000000');

        // Even correct code is rejected after max attempts.
        $this->assertFalse($this->manager->verify($phone, 'verification', $code));
    }
}
