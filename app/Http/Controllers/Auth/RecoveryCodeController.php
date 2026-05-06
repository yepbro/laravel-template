<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\TwoFactor\RecoveryCodeManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Read and regenerate two-factor recovery codes.
 *
 * index() - returns the current plaintext codes as a JSON array.
 * store() - regenerates all codes (requires password confirmation middleware).
 */
class RecoveryCodeController extends Controller
{
    public function __construct(
        private readonly RecoveryCodeManager $recoveryCodes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication has not been enabled.'],
            ]);
        }

        return response()->json($this->recoveryCodes->retrieve($user));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication has not been enabled.'],
            ]);
        }

        $codes = $this->recoveryCodes->generate();
        $this->recoveryCodes->store($user, $codes);

        if ($request->wantsJson()) {
            return response()->json($codes);
        }

        return redirect()->back();
    }
}
