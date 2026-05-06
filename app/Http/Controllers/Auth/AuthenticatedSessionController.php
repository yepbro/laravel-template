<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\AuthenticateUser;
use App\Auth\Services\LoginRateLimiter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles project-owned login and logout.
 *
 * When the user has enabled and confirmed 2FA, store() does NOT authenticate
 * the guard immediately. Instead it stores the user ID in the session and
 * returns a two-factor challenge response so the client can complete login
 * via TwoFactorChallengeController.
 */
class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $identifier  = $request->string('login')->toString();
        $rateLimiter = app(LoginRateLimiter::class);
        $key         = $rateLimiter->key($identifier, $request->ip() ?? '');
        $errorKey    = $request->credentialKey();

        if ($rateLimiter->tooManyAttempts($key)) {
            return $this->throttleResponse($request, $rateLimiter->availableIn($key), $errorKey);
        }

        $user = (new AuthenticateUser())->attempt(
            $identifier,
            $request->string('password')->toString(),
        );

        if ($user === null) {
            $rateLimiter->hit($key);

            return $this->invalidCredentialsResponse($request, $errorKey);
        }

        $rateLimiter->clear($key);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put('_two_factor_login_id', $user->getKey());
            $request->session()->put('_two_factor_login_remember', $request->boolean('remember'));

            if ($request->expectsJson()) {
                return response()->json(['two_factor' => true]);
            }

            return redirect('/two-factor-challenge');
        }

        $guard = AuthFeatures::make()->guard();
        Auth::guard($guard)->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([], 200);
        }

        return redirect(AuthFeatures::make()->home());
    }

    public function destroy(Request $request): Response|JsonResponse|RedirectResponse
    {
        $guard = AuthFeatures::make()->guard();
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect('/');
    }

    private function throttleResponse(Request $request, int $availableIn, string $errorKey = 'login'): JsonResponse|RedirectResponse
    {
        $message = trans('auth.throttle', ['seconds' => $availableIn]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => [$errorKey => [$message]],
            ], 429);
        }

        return redirect()->back()->withErrors([$errorKey => $message]);
    }

    private function invalidCredentialsResponse(Request $request, string $errorKey = 'login'): JsonResponse|RedirectResponse
    {
        $message = trans('auth.failed');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => [$errorKey => [$message]],
            ], 422);
        }

        return redirect()->back()->withErrors([$errorKey => $message]);
    }
}
