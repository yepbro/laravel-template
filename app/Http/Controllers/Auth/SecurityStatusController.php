<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Returns a combined security status for the authenticated user.
 *
 * Response shape:
 *   {
 *     "password_confirmed": bool,      // session password confirmation is active
 *     "two_factor_enabled": bool,      // 2FA is fully set up and ready for use
 *     "two_factor_confirmed": bool,    // TOTP was confirmed via the confirmation step
 *   }
 *
 * This is a project-owned endpoint at GET /user/security-status.
 */
class SecurityStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $passwordTimeout = config('auth.password_timeout', 10800);
        $confirmedValue  = $request->session()->get('auth.password_confirmed_at', 0);
        $timeout         = is_numeric($passwordTimeout) ? (int) $passwordTimeout : 10800;
        $confirmedAt     = is_numeric($confirmedValue) ? (int) $confirmedValue : 0;
        $passwordConfirmed = $confirmedAt > 0 && (time() - $confirmedAt) < $timeout;

        /** @var User $user */
        $user = $request->user();

        $twoFactorEnabled   = $user->hasEnabledTwoFactorAuthentication();
        $twoFactorConfirmed = $user->two_factor_confirmed_at instanceof Carbon;

        return response()->json([
            'password_confirmed'   => $passwordConfirmed,
            'two_factor_enabled'   => $twoFactorEnabled,
            'two_factor_confirmed' => $twoFactorConfirmed,
        ]);
    }
}
