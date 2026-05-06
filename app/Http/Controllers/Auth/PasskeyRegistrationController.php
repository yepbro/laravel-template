<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse as PasskeyRegistrationResponseContract;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Http\Requests\PasskeyRegistrationRequest;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;
use Illuminate\Support\Collection;

class PasskeyRegistrationController extends Controller
{
    /**
     * List the authenticated user's registered passkeys.
     *
     * Only safe, non-credential fields are returned. The raw credential JSON
     * and credential_id are intentionally omitted.
     */
    public function list(): JsonResponse
    {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        /** @var Collection<int, Passkey> $passkeys */
        $passkeys = $user->passkeys()->get();

        $data = $passkeys->map(fn(Passkey $passkey): array => [
            'id'            => $passkey->id,
            'name'          => $passkey->name,
            'authenticator' => $passkey->authenticator,
            'last_used_at'  => $passkey->last_used_at?->toIso8601String(),
            'created_at'    => $passkey->created_at?->toIso8601String(),
        ]);

        return response()->json($data);
    }

    /**
     * Return passkey creation options for the authenticated user.
     *
     * Excludes already-registered credentials so the same authenticator
     * cannot be registered a second time.
     */
    public function index(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        $options = $generate($user);

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.registration_options', $serialized);

        return response()->json([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Validate and store a new passkey for the authenticated user.
     */
    public function store(
        PasskeyRegistrationRequest $request,
        StorePasskey $storePasskey,
    ): PasskeyRegistrationResponseContract {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $request->credential(),
            $request->registrationOptions(),
        );

        return app(PasskeyRegistrationResponseContract::class)->withPasskey($passkey);
    }

    /**
     * Delete a passkey owned by the authenticated user.
     *
     * Returns 403 if the passkey belongs to a different user.
     */
    public function destroy(Passkey $passkey, DeletePasskey $deletePasskey): PasskeyDeletedResponseContract
    {
        $user = Auth::guard(config('auth_features.guard', 'web'))->user()
            ?? throw new AuthenticationException();

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        abort_unless($passkey->user_id === $user->getKey(), 403);

        $deletePasskey($user, $passkey);

        return app(PasskeyDeletedResponseContract::class);
    }
}
