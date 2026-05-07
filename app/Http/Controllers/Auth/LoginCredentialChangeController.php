<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AuthFeatures;
use App\Auth\Services\ConfirmUserLoginCredentialChange;
use App\Auth\Services\ProposeUserLoginCredentialChange;
use App\Auth\Services\SendUserLoginCredentialChangeConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestLoginCredentialEmailChangeRequest;
use App\Http\Requests\Auth\RequestLoginCredentialPhoneChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginCredentialChangeController extends Controller
{
    public function requestEmailChange(RequestLoginCredentialEmailChangeRequest $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user  = $request->user();
        $email = $request->string('email')->toString();

        $plain = (new ProposeUserLoginCredentialChange())->proposeEmailChange($user, $email);

        (new SendUserLoginCredentialChangeConfirmation())->sendForEmailChange($user, $plain, $email);

        if ($request->wantsJson()) {
            return response()->json([], 204);
        }

        return back()->with('status', 'login-credential-change-requested');
    }

    public function requestPhoneChange(RequestLoginCredentialPhoneChangeRequest $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user  = $request->user();
        $phone = $request->string('phone')->toString();

        $plain = (new ProposeUserLoginCredentialChange())->proposePhoneChange($user, $phone);

        (new SendUserLoginCredentialChangeConfirmation())->sendForPhoneChange($user, $plain, $phone);

        if ($request->wantsJson()) {
            return response()->json([], 204);
        }

        return back()->with('status', 'login-credential-change-requested');
    }

    public function confirm(Request $request, string $token): JsonResponse|RedirectResponse
    {
        try {
            (new ConfirmUserLoginCredentialChange())->confirm($token);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                throw $exception;
            }

            return redirect('/account/login-credentials')
                ->withErrors($exception->errors());
        }

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => __('Login credential updated.')]);
        }

        return redirect(AuthFeatures::make()->home())
            ->with('status', 'login-credential-updated');
    }
}
