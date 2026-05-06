<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\PhoneOtpManager;
use App\Auth\Support\PhoneNormalizer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles POST phone/verification-notification -- sends a phone OTP.
 *
 * Response contract:
 *   - No phone      : 422 errors.phone (JSON) | redirect back withErrors (web)
 *   - Already verified : 204 (JSON) | redirect home (web)
 *   - Unverified phone : 202 + OTP delivered (JSON) | redirect back with status (web)
 */
class PhoneVerificationNotificationController extends Controller
{
    public function __construct(private readonly PhoneOtpManager $otpManager) {}

    public function __invoke(Request $request): Response|JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->hasPhone()) {
            return $this->noPhoneResponse($request);
        }

        if ($user->hasVerifiedPhone()) {
            return $this->alreadyVerifiedResponse($request);
        }

        $phone = PhoneNormalizer::normalize((string) $user->phone);
        $this->otpManager->send($phone, 'verification');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Verification code sent.'], 202);
        }

        return back()->with('status', 'phone-verification-code-sent');
    }

    private function noPhoneResponse(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'This account has no phone number to verify.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => ['phone' => [$message]],
            ], 422);
        }

        return back()->withErrors(['phone' => $message]);
    }

    private function alreadyVerifiedResponse(Request $request): Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect(AuthFeatures::make()->home());
    }
}
