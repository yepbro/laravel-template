<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Services\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileInformationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Handles authenticated profile information updates (name, email, phone).
 *
 * Response contract:
 *   JSON - empty body 200.
 *   Web  - redirect back with session 'status' = 'profile-information-updated'.
 */
class ProfileInformationController extends Controller
{
    public function update(UpdateProfileInformationRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        (new UpdateUserProfileInformation())->update(
            $user,
            $request->string('name')->toString(),
            $request->filled('email') ? $request->string('email')->toString() : null,
            $request->filled('phone') ? $request->string('phone')->toString() : null,
        );

        if ($request->wantsJson()) {
            return new JsonResponse('', 200);
        }

        return back()->with('status', 'profile-information-updated');
    }
}
