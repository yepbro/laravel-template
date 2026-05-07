<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Services\DeleteUserAccount;
use App\Http\Requests\Auth\DeleteAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DeleteAccountController
{
    /**
     * Soft-delete the authenticated user and invalidate the current session (JSON SPA flow).
     */
    public function __invoke(
        DeleteAccountRequest $request,
        DeleteUserAccount $deleteUserAccount,
    ): JsonResponse {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $deleteUserAccount->handle($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'redirect' => route('login', [], absolute: true),
        ]);
    }
}
