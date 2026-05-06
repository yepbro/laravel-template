<?php

declare(strict_types=1);

namespace App\Auth\TwoFactor;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates, stores, and verifies two-factor recovery codes.
 *
 * Codes are stored as an encrypted JSON array in two_factor_recovery_codes.
 * Consuming a code removes it from the list (it is NOT replaced with a new
 * code -- the user starts with 8 and each use reduces the count by one).
 * The user must explicitly regenerate to obtain a fresh set.
 */
class RecoveryCodeManager
{
    private const COUNT  = 8;
    private const LENGTH = 10;

    /**
     * Generate a fresh set of random plaintext recovery codes.
     *
     * @return list<string>
     */
    public function generate(): array
    {
        return array_map(fn() => Str::random(self::LENGTH), range(1, self::COUNT));
    }

    /**
     * Encrypt and persist the given codes on the user record.
     *
     * @param list<string> $codes
     */
    public function store(User $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString((string) json_encode($codes)),
        ])->save();
    }

    /**
     * Decrypt and return the stored recovery codes, or an empty array when
     * none have been generated yet.
     *
     * @return list<string>
     */
    public function retrieve(User $user): array
    {
        if ($user->two_factor_recovery_codes === null) {
            return [];
        }

        /** @var list<string> $codes */
        $codes = json_decode(
            Crypt::decryptString((string) $user->two_factor_recovery_codes),
            true,
        );

        return $codes;
    }

    /**
     * Verify and consume a recovery code atomically.
     *
     * Uses a SELECT FOR UPDATE row lock inside a database transaction to prevent
     * two concurrent challenge requests from both succeeding with the same code.
     * The locked row is the authoritative source; any stale attributes on $user
     * are intentionally bypassed.
     */
    public function consume(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (! $lockedUser instanceof User) {
                return false;
            }

            $codes = $this->retrieve($lockedUser);
            $index = array_search($code, $codes, true);

            if ($index === false) {
                return false;
            }

            array_splice($codes, (int) $index, 1);
            $this->store($lockedUser, $codes);

            return true;
        });
    }
}
