<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyConfirmationController extends Controller
{
    /**
     * Return passkey verification options scoped to the authenticated user.
     *
     * Scoping to the current user's credentials prevents the session challenge
     * from being used by a different user's passkey.
     */
    public function index(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $options = $generate($user);

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.verification_options', $serialized);

        return response()->json([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Verify the passkey credential and mark the password as confirmed.
     */
    public function store(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): PasskeyConfirmationResponseContract {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $verify(
            $request->credential(),
            $request->verificationOptions(),
            $user,
        );

        /** @var SessionStore $session */
        $session = $request->session();

        $session->passwordConfirmed();

        return app(PasskeyConfirmationResponseContract::class);
    }
}
