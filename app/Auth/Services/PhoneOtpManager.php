<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Contracts\PhoneOtpChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Manages phone OTP generation, delivery, and single-use verification.
 *
 * Storage: the phone_otps table holds one record per generated code. Only
 * hashed codes are stored; the plaintext code is passed to the channel and
 * then discarded. A record is valid only while:
 *   - consumed_at is null (not yet used)
 *   - expires_at > now()
 *   - attempts < max_attempts
 *
 * Purpose strings (e.g. 'verification', 'recovery') keep OTP flows isolated
 * so the same infrastructure can serve multiple auth stages.
 */
class PhoneOtpManager
{
    public function __construct(private readonly PhoneOtpChannel $channel) {}

    /**
     * Generate a numeric OTP, store its hash, deliver it via the channel,
     * and return the plaintext code.
     */
    public function send(string $phone, string $purpose): string
    {
        $length  = (int) config('auth_features.phone_otp.length', 6);
        $expires = (int) config('auth_features.phone_otp.expires_minutes', 10);

        $code = $this->generateCode($length);
        $now  = now();

        DB::table('phone_otps')->insert([
            'phone'      => $phone,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'expires_at' => $now->copy()->addMinutes($expires),
            'attempts'   => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->channel->send($phone, $code, $purpose);

        return $code;
    }

    /**
     * Verify a submitted code against the most recent valid OTP record for
     * the given phone and purpose. Increments the attempt counter on every
     * call (including successful ones) so brute-force is bounded. Returns
     * true and marks the record consumed on a match; false otherwise.
     *
     * The entire read-check-consume sequence runs inside a database
     * transaction with an exclusive row lock (lockForUpdate). This
     * guarantees that two concurrent requests cannot both read the same
     * unconsumed row: the second request blocks until the first transaction
     * commits, then finds the row either consumed or attempts-exhausted.
     */
    public function verify(string $phone, string $purpose, string $code): bool
    {
        $maxAttempts = (int) config('auth_features.phone_otp.max_attempts', 5);

        return DB::transaction(function () use ($phone, $purpose, $code, $maxAttempts): bool {
            $record = DB::table('phone_otps')
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', $maxAttempts)
                ->orderBy('created_at', 'desc')
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return false;
            }

            $attempts = is_numeric($record->attempts) ? (int) $record->attempts : $maxAttempts;
            $codeHash = is_string($record->code_hash) ? $record->code_hash : '';

            // Increment attempts before checking so every guess consumes an attempt.
            DB::table('phone_otps')
                ->where('id', $record->id)
                ->update([
                    'attempts'   => $attempts + 1,
                    'updated_at' => now(),
                ]);

            if (! Hash::check($code, $codeHash)) {
                return false;
            }

            DB::table('phone_otps')
                ->where('id', $record->id)
                ->update([
                    'consumed_at' => now(),
                    'updated_at'  => now(),
                ]);

            return true;
        });
    }

    /**
     * Generate a zero-padded numeric string of the requested length.
     */
    private function generateCode(int $length): string
    {
        $max  = (int) str_pad('', $length, '9');
        $code = random_int(0, $max);

        return str_pad((string) $code, $length, '0', STR_PAD_LEFT);
    }
}
