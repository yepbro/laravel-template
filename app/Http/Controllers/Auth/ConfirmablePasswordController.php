<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles password confirmation for sensitive actions.
 *
 * show()  - GET: returns the confirm-password view (web) or redirects home
 *           (web, views disabled).
 * store() - POST: validates the current password, marks the session as
 *           confirmed, returns 201 JSON or redirects to the intended URL.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Show the password confirmation form.
     *
     * Web + views enabled: 200 view.
     * Web + views disabled: redirect home.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $features = AuthFeatures::make();

        if (! $features->viewsEnabled()) {
            return redirect($features->home());
        }

        return response()->view('auth.confirm-password');
    }

    /**
     * Confirm the currently authenticated user's password.
     *
     * JSON: 201 on success, 422 validation error on wrong password.
     * Web: redirect to intended URL or home on success.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $features = AuthFeatures::make();

        /** @var \App\Models\User $user */
        $user = $request->user($features->guard());

        if (! Hash::check($request->string('password')->toString(), $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        $request->session()->passwordConfirmed();

        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        return redirect()->intended($features->home());
    }
}
