<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\TwoFactor\TwoFactorManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Confirms two-factor authentication setup with a valid TOTP code.
 *
 * Verifies the submitted code against the user's (unconfirmed) secret and sets
 * two_factor_confirmed_at on success, enabling 2FA for login challenges.
 */
class ConfirmedTwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private readonly TwoFactorManager $twoFactor,
    ) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        $request->validate(['code' => ['required', 'string']]);

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => [__('validation.required', ['attribute' => 'code'])],
            ]);
        }

        $secret = Crypt::decryptString((string) $user->two_factor_secret);

        if (! $this->twoFactor->verifyCode($secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        if ($request->wantsJson()) {
            return response()->json([], 200);
        }

        return redirect()->back()->with('status', 'two-factor-authentication-confirmed');
    }
}
