<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\TwoFactor\TwoFactorManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Returns the authenticated user's two-factor QR code SVG.
 *
 * Returns 422 when the user has not yet enabled 2FA (no secret stored).
 */
class TwoFactorQrCodeController extends Controller
{
    public function __construct(
        private readonly TwoFactorManager $twoFactor,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication has not been enabled.'],
            ]);
        }

        $secret    = Crypt::decryptString((string) $user->two_factor_secret);
        $appConfig = config('app.name', 'Laravel');
        $appName   = is_string($appConfig) ? $appConfig : 'Laravel';
        $userKey   = $user->getKey();
        $identifier = is_string($user->email) && $user->email !== ''
            ? $user->email
            : (is_scalar($userKey) ? (string) $userKey : '');

        $svg = $this->twoFactor->qrCodeSvg($appName, $identifier, $secret);

        return response()->json(['svg' => $svg]);
    }
}
