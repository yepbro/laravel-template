<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyLoginController extends Controller
{
    /**
     * Return passkey verification options for passwordless login.
     *
     * Stores the serialized challenge in the session so the submit action
     * can verify the credential against the same challenge.
     */
    public function index(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.verification_options', $serialized);

        return response()->json([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Verify the passkey credential and log the user in.
     */
    public function store(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): PasskeyLoginResponseContract {
        $passkey = $verify(
            $request->credential(),
            $request->verificationOptions(),
        );

        $guard = Auth::guard(config('auth_features.guard', 'web'));

        if (! $guard instanceof StatefulGuard) {
            throw new RuntimeException('Passkeys requires a stateful authentication guard.');
        }

        if (! Passkeys::allowsLogin($request, $passkey)) {
            throw InvalidPasskeyException::make('Unable to sign in with this account.');
        }

        $guard->login($passkey->user, $request->remember());

        $request->session()->regenerate();

        return app(PasskeyLoginResponseContract::class);
    }
}
