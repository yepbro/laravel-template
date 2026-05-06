<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\PhoneOtpManager;
use App\Auth\Support\PhoneNormalizer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Handles POST phone/verify -- validates an OTP and marks the phone verified.
 *
 * Response contract:
 *   - No phone         : 422 errors.phone (JSON) | redirect back withErrors (web)
 *   - Already verified : 204 (JSON) | redirect home (web)
 *   - Valid OTP        : marks verified, 204 (JSON) | redirect home (web)
 *   - Invalid/expired  : 422 errors.code (JSON) | redirect back withErrors (web)
 */
class PhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneOtpManager $otpManager) {}

    public function __invoke(VerifyPhoneRequest $request): Response|JsonResponse|RedirectResponse
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

        if (! $this->otpManager->verify($phone, 'verification', $request->string('code')->toString())) {
            return $this->invalidCodeResponse($request);
        }

        $user->markPhoneAsVerified();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect(AuthFeatures::make()->home());
    }

    private function noPhoneResponse(VerifyPhoneRequest $request): JsonResponse|RedirectResponse
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

    private function alreadyVerifiedResponse(VerifyPhoneRequest $request): Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect(AuthFeatures::make()->home());
    }

    private function invalidCodeResponse(VerifyPhoneRequest $request): JsonResponse|RedirectResponse
    {
        $message = 'The verification code is invalid or has expired.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => ['code' => [$message]],
            ], 422);
        }

        return back()->withErrors(['code' => $message]);
    }
}
