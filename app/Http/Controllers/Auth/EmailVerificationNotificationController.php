<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles POST email/verification-notification -- resends the verification link.
 *
 * Response contract:
 *   - Already verified  : redirect home (web) | 204 (JSON)
 *   - Phone-only user   : redirect back with errors (web) | 422 with email error (JSON)
 *   - Unverified + email: send notification, redirect back with status (web) | 202 (JSON)
 */
class EmailVerificationNotificationController extends Controller
{
    public function __invoke(Request $request): Response|JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->alreadyVerifiedResponse($request);
        }

        if (! $user->hasEmail()) {
            return $this->noEmailResponse($request);
        }

        $user->sendEmailVerificationNotification();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Verification link sent.'], 202);
        }

        return back()->with('status', 'verification-link-sent');
    }

    private function alreadyVerifiedResponse(Request $request): Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect(AuthFeatures::make()->home());
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
