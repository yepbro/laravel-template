<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\UpdateUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use App\Notifications\Auth\PasswordChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * Handles authenticated password updates (change password while logged in).
 *
 * Requires authentication via the configured guard. Validation is handled
 * by UpdatePasswordRequest, which verifies the current password and applies
 * project password policy to the new value.
 *
 * Response contract:
 *   JSON - empty body 200 (no token rotation, no PasswordReset event).
 *   Web  - redirect back with session 'status' = 'password-updated'.
 */
class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        (new UpdateUserPassword())->update($user, $request->string('password')->toString());

        // Invalidate any existing password reset tokens so they cannot be used
        // after the user has already changed their password via the authenticated flow.
        Password::broker(AuthFeatures::make()->passwordBroker())->deleteToken($user);

        DB::afterCommit(function () use ($user): void {
            $user->refresh();

            if ($user->hasEmail()) {
                $user->notify(new PasswordChanged());
            }
        });

        if ($request->wantsJson()) {
            return new JsonResponse('', 200);
        }

        return back()->with('status', 'password-updated');
    }
}
