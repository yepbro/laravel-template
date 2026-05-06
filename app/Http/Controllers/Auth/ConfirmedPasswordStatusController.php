<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns whether the current session has a valid password confirmation.
 *
 * Response shape: {"confirmed": true|false}
 *
 * "confirmed" is true only when auth.password_confirmed_at is present in
 * the session AND the elapsed time is less than config('auth.password_timeout').
 */
class ConfirmedPasswordStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $passwordTimeout = config('auth.password_timeout', 10800);
        $confirmedValue  = $request->session()->get('auth.password_confirmed_at', 0);
        $timeout         = is_numeric($passwordTimeout) ? (int) $passwordTimeout : 10800;
        $confirmedAt     = is_numeric($confirmedValue) ? (int) $confirmedValue : 0;
        $confirmed   = $confirmedAt > 0 && (time() - $confirmedAt) < $timeout;

        return response()->json(['confirmed' => $confirmed]);
    }
}
