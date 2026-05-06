<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\TwoFactor\RecoveryCodeManager;
use App\Auth\TwoFactor\TwoFactorManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Enables and disables two-factor authentication for the authenticated user.
 *
 * store()   - enables 2FA: generates secret + recovery codes.
 * destroy() - disables 2FA: clears all 2FA fields.
 */
class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private readonly TwoFactorManager $twoFactor,
        private readonly RecoveryCodeManager $recoveryCodes,
    ) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        $secret = $this->twoFactor->generateSecretKey();
        $codes  = $this->recoveryCodes->generate();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
        ])->save();

        $this->recoveryCodes->store($user, $codes);

        if ($features->twoFactorRequiresConfirmation()) {
            $user->forceFill(['two_factor_confirmed_at' => null])->save();
        } else {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        if ($request->wantsJson()) {
            return response()->json([], 200);
        }

        return redirect()->back()->with('status', 'two-factor-authentication-enabled');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        if ($request->wantsJson()) {
            return response()->json([], 200);
        }

        return redirect()->back()->with('status', 'two-factor-authentication-disabled');
    }
}
