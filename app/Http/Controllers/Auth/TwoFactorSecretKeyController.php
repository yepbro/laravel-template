<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Returns the authenticated user's plaintext TOTP secret key.
 *
 * Returns 422 when the user has not yet enabled 2FA (no secret stored).
 */
class TwoFactorSecretKeyController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication has not been enabled.'],
            ]);
        }

        $secret = Crypt::decryptString((string) $user->two_factor_secret);

        return response()->json(['secretKey' => $secret]);
    }
}
