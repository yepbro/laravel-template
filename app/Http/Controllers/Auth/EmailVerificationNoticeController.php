<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SpaController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles GET email/verify -- the "please verify your email" notice.
 *
 * Phone-only accounts (no email) are not eligible for email verification
 * and receive a 422 / session error rather than the normal notice.
 * Already-verified users are redirected home. Unverified email users
 * receive the Vue SPA shell (web) or a 200 JSON response (SPA API).
 */
class EmailVerificationNoticeController extends Controller
{
    public function __invoke(Request $request): Response|JsonResponse|RedirectResponse|View
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->hasEmail()) {
            return $this->noEmailResponse($request);
        }

        if ($user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email already verified.']);
            }

            return redirect($features->home());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your email address is not verified.']);
        }

        if (! $features->viewsEnabled()) {
            return redirect($features->home());
        }

        return app(SpaController::class)();
    }

    private function noEmailResponse(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'This account has no email address to verify.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => ['email' => [$message]],
            ], 422);
        }

        return back()->withErrors(['email' => $message]);
    }
}
