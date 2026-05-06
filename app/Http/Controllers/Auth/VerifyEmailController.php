<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles GET email/verify/{id}/{hash} -- fulfills the signed verification link.
 *
 * The signed and throttle:6,1 middleware are applied at the route level.
 * EmailVerificationRequest::authorize() checks id/hash match but cannot
 * distinguish a phone-only account (getEmailForVerification() returns '')
 * from a legitimate link, so we guard explicitly before calling fulfill().
 *
 * fulfill() is idempotent: it only marks and dispatches Verified when the
 * user is not already verified, so already-verified users are safely
 * redirected home without a duplicate event.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): Response|JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->hasEmail()) {
            return $this->noEmailResponse($request);
        }

        $request->fulfill();

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
