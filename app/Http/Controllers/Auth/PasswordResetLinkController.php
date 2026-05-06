<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

/**
 * Handles the forgot-password (send reset link) flow.
 *
 * Behavior:
 *   - On success: JSON 200 with status string, or web redirect back with status.
 *   - On failure: JSON 422 with validation error on 'email', or web redirect
 *     back with errors. The broker's response is translated via the passwords
 *     language file so no user-existence information leaks beyond what the
 *     broker already exposes.
 *
 * Email field contract:
 *   auth_features.email names the REQUEST field (e.g. 'email_address').
 *   The password broker always receives credentials under the 'email' key
 *   because the users table provider column is users.email. Errors and
 *   withInput are keyed to the configured request field so the caller sees
 *   consistent field names throughout the flow.
 */
class PasswordResetLinkController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse|RedirectResponse
    {
        $features   = AuthFeatures::make();
        $emailField = $features->emailField();

        // Always pass credentials under 'email' - the broker looks up users.email.
        $status = Password::broker($features->passwordBroker())->sendResetLink(
            ['email' => $request->input($emailField)],
        );

        if ($status === Password::RESET_LINK_SENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => trans($status)], 200);
            }

            return back()->with('status', trans($status));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans($status),
                'errors'  => [$emailField => [trans($status)]],
            ], 422);
        }

        return back()->withErrors([$emailField => trans($status)])->withInput($request->only($emailField));
    }
}
