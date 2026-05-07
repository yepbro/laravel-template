<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use Illuminate\Http\JsonResponse;

/**
 * Exposes non-secret auth feature toggles for SPA forms (registration, account).
 */
class AuthFeatureSnapshotController
{
    public function __invoke(): JsonResponse
    {
        $features = AuthFeatures::make();

        return new JsonResponse([
            'registration_mode'             => $features->registrationMode(),
            'allows_email_registration'    => $features->allowsEmailRegistration(),
            'allows_phone_registration'      => $features->allowsPhoneRegistration(),
            'email_verification_enabled'    => $features->emailVerificationEnabled(),
            'phone_verification_enabled'    => $features->phoneVerificationEnabled(),
        ]);
    }
}
