<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\TwoFactor\RecoveryCodeManager;
use App\Auth\TwoFactor\TwoFactorManager;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SpaController;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Handles the two-factor authentication challenge during login.
 *
 * Session keys used for challenge state:
 *   _two_factor_login_id       - primary key of the user being challenged
 *   _two_factor_login_remember - whether to set a remember cookie
 *
 * create() - GET: serves the Vue SPA shell (web) or 200 (JSON) when session has
 *            challenge state; redirects to login / returns 422 otherwise.
 * store()  - POST: verifies TOTP code or recovery code, completes login.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorManager $twoFactor,
        private readonly RecoveryCodeManager $recoveryCodes,
    ) {}

    public function create(Request $request): Response|JsonResponse|RedirectResponse|View
    {
        if (! $this->hasChallengeSession($request)) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'two_factor' => ['No pending two-factor challenge.'],
                ]);
            }

            return redirect()->route('login');
        }

        if ($request->expectsJson()) {
            return response()->json([], 200);
        }

        return app(SpaController::class)();
    }

    public function store(TwoFactorChallengeRequest $request): JsonResponse|RedirectResponse|Response
    {
        $features = AuthFeatures::make();

        if (! $this->hasChallengeSession($request)) {
            throw ValidationException::withMessages([
                'code' => ['No pending two-factor challenge.'],
            ]);
        }

        if (! $request->hasCode() && ! $request->hasRecoveryCode()) {
            throw ValidationException::withMessages([
                'code' => [__('validation.required', ['attribute' => 'code'])],
            ]);
        }

        $userId = $request->session()->get('_two_factor_login_id');
        $user   = User::query()->find($userId);

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'code' => ['Invalid session state.'],
            ]);
        }

        if ($request->hasCode()) {
            $this->verifyTotpOrFail($user, $request->string('code')->toString());
        } else {
            $this->verifyRecoveryCodeOrFail($user, $request->string('recovery_code')->toString());
        }

        $remember = (bool) $request->session()->get('_two_factor_login_remember', false);

        $request->session()->forget(['_two_factor_login_id', '_two_factor_login_remember']);

        $guard = $features->guard();
        Auth::guard($guard)->login($user, $remember);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect($features->home());
    }

    private function hasChallengeSession(Request $request): bool
    {
        return $request->session()->has('_two_factor_login_id');
    }

    private function verifyTotpOrFail(User $user, string $code): void
    {
        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        }

        $secret = Crypt::decryptString((string) $user->two_factor_secret);

        if (! $this->twoFactor->verifyCode($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        }
    }

    private function verifyRecoveryCodeOrFail(User $user, string $code): void
    {
        if (! $this->recoveryCodes->consume($user, $code)) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('auth.failed')],
            ]);
        }
    }
}
