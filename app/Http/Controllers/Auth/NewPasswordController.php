<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/**
 * Handles the reset-password (set new password via token) flow.
 *
 * Behavior:
 *   - On success: JSON 200 with status string, or web redirect to login with status.
 *   - On failure (invalid token or unknown email): JSON 422 with error on 'email',
 *     surfacing broker errors on the email field.
 *   - Password mismatch / policy failure: caught by ResetPasswordRequest before
 *     the broker is called.
 *
 * Email field contract:
 *   auth_features.email names the REQUEST field (e.g. 'email_address').
 *   The password broker always receives credentials under the 'email' key
 *   because the users table provider column is users.email. Errors and
 *   withInput are keyed to the configured request field so the caller sees
 *   consistent field names throughout the flow.
 */
class NewPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request): JsonResponse|RedirectResponse
    {
        $features   = AuthFeatures::make();
        $emailField = $features->emailField();

        // Always pass 'email' to the broker - it must match the users table column.
        $status = Password::broker($features->passwordBroker())->reset(
            [
                'token'                 => $request->input('token'),
                'email'                 => $request->input($emailField),
                'password'              => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $password): void {
                (new ResetUserPassword())->reset($user, $password);
            },
        );
        $statusKey = is_string($status) ? $status : Password::INVALID_TOKEN;

        if ($statusKey === Password::PASSWORD_RESET) {
            if ($request->wantsJson()) {
                return response()->json(['message' => trans($statusKey)], 200);
            }

            // Guard against missing named route - fall back to home rather than throwing.
            $routeName  = $features->passwordResetRedirect();
            $targetUrl  = Route::has($routeName) ? route($routeName) : $features->home();

            return redirect($targetUrl)->with('status', trans($statusKey));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans($statusKey),
                'errors'  => [$emailField => [trans($statusKey)]],
            ], 422);
        }

        return back()->withErrors([$emailField => trans($statusKey)])->withInput($request->only($emailField));
    }
}
