<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Handles project-owned user registration.
 */
class RegisteredUserController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $email     = $validated['email'] ?? null;
        $phone     = $validated['phone'] ?? null;

        $user = (new RegisterUser())->register(
            name: $request->string('name')->toString(),
            email: is_string($email) ? $email : null,
            phone: is_string($phone) ? $phone : null,
            password: $request->string('password')->toString(),
        );

        event(new Registered($user));

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([], 201);
        }

        return redirect(AuthFeatures::make()->home());
    }
}
