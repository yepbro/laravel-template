<?php

declare(strict_types=1);

namespace App\Auth\Support;

/**
 * Simple, deterministic phone number normalizer.
 *
 * Rules:
 *   - Trim leading/trailing whitespace.
 *   - Strip internal spaces, hyphens, and parentheses.
 *   - Preserve a leading '+' (international prefix).
 *
 * Input:  "+1 (555) 123-4567"
 * Output: "+15551234567"
 *
 * No external dependency, no libphonenumber -- deliberately minimal so it
 * never becomes a maintenance burden before a real phone-auth stage ships.
 */
final class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        $phone = (string) preg_replace('/[\s\-()]/', '', $phone);

        return $phone;
    }
}
