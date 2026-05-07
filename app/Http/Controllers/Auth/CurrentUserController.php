<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController
{
    /**
     * Return minimal profile fields for the authenticated SPA shell.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $emailVerifiedAt = $user->email_verified_at;
        $phoneVerifiedAt = $user->phone_verified_at;

        return new JsonResponse([
            'id'                => $user->getKey(),
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'email_verified_at' => self::formatVerificationTimestamp($emailVerifiedAt),
            'phone_verified_at' => self::formatVerificationTimestamp($phoneVerifiedAt),
        ]);
    }

    private static function formatVerificationTimestamp(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DATE_ATOM);
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
