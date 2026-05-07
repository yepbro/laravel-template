<?php

declare(strict_types=1);

namespace App\Auth\Routing;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ConfirmedPasswordStatusController;
use App\Http\Controllers\Auth\ConfirmedTwoFactorAuthenticationController;
use App\Http\Controllers\Auth\CurrentUserController;
use App\Http\Controllers\Auth\DeleteAccountController;
use App\Http\Controllers\Auth\EmailVerificationNoticeController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasskeyConfirmationController;
use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Controllers\Auth\PasskeyRegistrationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\PhoneVerificationNotificationController;
use App\Http\Controllers\Auth\ProfileInformationController;
use App\Http\Controllers\Auth\RecoveryCodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorQrCodeController;
use App\Http\Controllers\Auth\TwoFactorSecretKeyController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/**
 * Registers project-owned auth routes for a given feature slice.
 *
 * Call the relevant method from routes/web.php when the corresponding
 * feature flag is enabled, or from a test setUp to exercise the canonical
 * route contract without enabling the flag in production config.
 */
class AuthRouteRegistrar
{
    /**
     * Register core authentication routes: login (GET + POST) and logout (POST).
     *
     *   login        GET  login  web, guest:web
     *   login.store  POST login  web, guest:web, throttle:login
     *   logout       POST logout web, auth:web
     *
     * GET login serves the Vue SPA shell (canonical URL).
     */
    public static function authentication(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('login', SpaController::class)
            ->middleware(['web', "guest:{$guard}"])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware(['web', "guest:{$guard}", 'throttle:login'])
            ->name('login.store');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware(['web', "auth:{$guard}"])
            ->name('logout');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register registration routes: GET SPA shell and POST store.
     *
     *   register       GET  register  web, guest:web
     *   register.store POST register  web, guest:web
     *
     * GET register serves the Vue SPA shell (canonical URL).
     */
    public static function registration(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('register', SpaController::class)
            ->middleware(['web', "guest:{$guard}"])
            ->name('register');

        Route::post('register', RegisteredUserController::class)
            ->middleware(['web', "guest:{$guard}"])
            ->name('register.store');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register password reset routes.
     *
     *   password.request GET  forgot-password        web, guest:web
     *   password.email   POST forgot-password        web, guest:web
     *   password.reset   GET  reset-password/{token} web, guest:web
     *   password.update  POST reset-password         web, guest:web
     *
     * GET routes serve the Vue SPA shell on canonical URLs (token and query
     * string stay on the request URL for the client router).
     */
    public static function passwordReset(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('forgot-password', SpaController::class)
            ->middleware(['web', "guest:{$guard}"])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->middleware(['web', "guest:{$guard}"])
            ->name('password.email');

        Route::get('reset-password/{token}', SpaController::class)
            ->middleware(['web', "guest:{$guard}"])
            ->name('password.reset');

        Route::get('reset-password', SpaController::class)
            ->middleware(['web', "guest:{$guard}"]);

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->middleware(['web', "guest:{$guard}"])
            ->name('password.update');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register password confirmation routes.
     *
     *   password.confirm       GET  user/confirm-password          web, auth:web  (SPA shell)
     *   password.confirm.store POST user/confirm-password          web, auth:web
     *   password.confirmation  GET  user/confirmed-password-status web, auth:web
     *
     * GET confirm-password (no prefix) is an alternate SPA entry URL.
     */
    public static function passwordConfirmation(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('user/confirm-password', SpaController::class)
            ->middleware(['web', "auth:{$guard}"])
            ->name('password.confirm');

        Route::get('confirm-password', SpaController::class)
            ->middleware(['web', "auth:{$guard}"]);

        Route::post('user/confirm-password', [ConfirmablePasswordController::class, 'store'])
            ->middleware(['web', "auth:{$guard}"])
            ->name('password.confirm.store');

        Route::get('user/confirmed-password-status', ConfirmedPasswordStatusController::class)
            ->middleware(['web', "auth:{$guard}"])
            ->name('password.confirmation');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register profile and password update routes.
     *
     *   current-user.show              GET user                   web, auth:web (JSON)
     *   user.destroy                  DELETE user                 web, auth:web (JSON SPA)
     *   user-password.update          PUT user/password            web, auth:web
     *   user-profile-information.update PUT user/profile-information web, auth:web
     */
    public static function profileAndPassword(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('user', CurrentUserController::class)
            ->middleware(['web', "auth:{$guard}"])
            ->name('current-user');

        Route::delete('user', DeleteAccountController::class)
            ->middleware(['web', "auth:{$guard}"])
            ->name('user.destroy');

        Route::put('user/password', [PasswordController::class, 'update'])
            ->middleware(['web', "auth:{$guard}"])
            ->name('user-password.update');

        Route::put('user/profile-information', [ProfileInformationController::class, 'update'])
            ->middleware(['web', "auth:{$guard}"])
            ->name('user-profile-information.update');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register two-factor authentication routes.
     *
     * Challenge (pre-auth, guest):
     *   two-factor.login       GET  two-factor-challenge  web, guest:web
     *   two-factor.login.store POST two-factor-challenge  web, guest:web, throttle:two-factor
     *
     * Management (authenticated, password-confirmed):
     *   two-factor.enable                    POST   user/two-factor-authentication           web, auth:web, password.confirm
     *   two-factor.confirm                   POST   user/confirmed-two-factor-authentication web, auth:web, password.confirm
     *   two-factor.disable                   DELETE user/two-factor-authentication           web, auth:web, password.confirm
     *   two-factor.qr-code                   GET    user/two-factor-qr-code                 web, auth:web, password.confirm
     *   two-factor.secret-key                GET    user/two-factor-secret-key               web, auth:web, password.confirm
     *   two-factor.recovery-codes            GET    user/two-factor-recovery-codes           web, auth:web, password.confirm
     *   two-factor.regenerate-recovery-codes POST   user/two-factor-recovery-codes           web, auth:web, password.confirm
     */
    public static function twoFactor(): void
    {
        $guard              = config('auth_features.guard', 'web');
        $managementMiddle   = ['web', "auth:{$guard}", 'password.confirm'];

        Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
            ->middleware(['web', "guest:{$guard}"])
            ->name('two-factor.login');

        Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
            ->middleware(['web', "guest:{$guard}", 'throttle:two-factor'])
            ->name('two-factor.login.store');

        Route::post('user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($managementMiddle)
            ->name('two-factor.enable');

        Route::post('user/confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($managementMiddle)
            ->name('two-factor.confirm');

        Route::delete('user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($managementMiddle)
            ->name('two-factor.disable');

        Route::get('user/two-factor-qr-code', TwoFactorQrCodeController::class)
            ->middleware($managementMiddle)
            ->name('two-factor.qr-code');

        Route::get('user/two-factor-secret-key', TwoFactorSecretKeyController::class)
            ->middleware($managementMiddle)
            ->name('two-factor.secret-key');

        Route::get('user/two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])
            ->middleware($managementMiddle)
            ->name('two-factor.recovery-codes');

        Route::post('user/two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])
            ->middleware($managementMiddle)
            ->name('two-factor.regenerate-recovery-codes');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register the three canonical email verification routes.
     *
     *   verification.notice  GET  email/verify               web, auth:web
     *   verification.send    POST email/verification-notification  web, auth:web, throttle:6,1
     *   verification.verify  GET  email/verify/{id}/{hash}   web, auth:web, signed, throttle:6,1
     */
    public static function emailVerification(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('email/verify', EmailVerificationNoticeController::class)
            ->middleware(['web', "auth:{$guard}"])
            ->name('verification.notice');

        Route::post('email/verification-notification', EmailVerificationNotificationController::class)
            ->middleware(['web', "auth:{$guard}", 'throttle:6,1'])
            ->name('verification.send');

        Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['web', "auth:{$guard}", 'signed', 'throttle:6,1'])
            ->name('verification.verify');

        // Rebuild the name and action index so that route() and URL generation
        // find the routes registered after the initial boot-time load.
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register the two canonical phone OTP verification routes.
     *
     *   phone.verification.send    POST phone/verification-notification  web, auth:web, throttle:6,1
     *   phone.verification.verify  POST phone/verify                     web, auth:web, throttle:6,1
     */
    public static function phoneVerification(): void
    {
        $guard = config('auth_features.guard', 'web');

        Route::get('phone/verify', SpaController::class)
            ->middleware(['web', "auth:{$guard}"]);

        Route::post('phone/verification-notification', PhoneVerificationNotificationController::class)
            ->middleware(['web', "auth:{$guard}", 'throttle:6,1'])
            ->name('phone.verification.send');

        Route::post('phone/verify', PhoneVerificationController::class)
            ->middleware(['web', "auth:{$guard}", 'throttle:6,1'])
            ->name('phone.verification.verify');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Register the eight canonical passkey routes.
     *
     * Guest (unauthenticated) flows:
     *   passkeys.authenticate.options  GET  passkeys/login/options    web, guest:web, throttle:6,1
     *   passkeys.authenticate          POST passkeys/login            web, guest:web, throttle:6,1
     *
     * Authenticated flows (password confirmation not required):
     *   passkeys.confirm.options       GET  passkeys/confirm/options  web, auth:web, throttle:6,1
     *   passkeys.confirm               POST passkeys/confirm          web, auth:web, throttle:6,1
     *
     * Passkey management (password confirmation required):
     *   passkeys.index                 GET    user/passkeys                web, auth:web, password.confirm
     *   passkeys.register.options      GET    user/passkeys/options        web, auth:web, password.confirm, throttle:6,1
     *   passkeys.register.store        POST   user/passkeys                web, auth:web, password.confirm, throttle:6,1
     *   passkeys.destroy               DELETE user/passkeys/{passkey}      web, auth:web, password.confirm
     */
    public static function passkeys(): void
    {
        $guard    = config('auth_features.guard', 'web');
        $throttle = 'throttle:6,1';

        Route::get('passkeys/login/options', [PasskeyLoginController::class, 'index'])
            ->middleware(['web', "guest:{$guard}", $throttle])
            ->name('passkeys.authenticate.options');

        Route::post('passkeys/login', [PasskeyLoginController::class, 'store'])
            ->middleware(['web', "guest:{$guard}", $throttle])
            ->name('passkeys.authenticate');

        Route::get('passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
            ->middleware(['web', "auth:{$guard}", $throttle])
            ->name('passkeys.confirm.options');

        Route::post('passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}", $throttle])
            ->name('passkeys.confirm');

        Route::get('user/passkeys', [PasskeyRegistrationController::class, 'list'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm'])
            ->name('passkeys.index');

        Route::get('user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm', $throttle])
            ->name('passkeys.register.options');

        Route::post('user/passkeys', [PasskeyRegistrationController::class, 'store'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm', $throttle])
            ->name('passkeys.register.store');

        Route::delete('user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
            ->middleware(['web', "auth:{$guard}", 'password.confirm'])
            ->name('passkeys.destroy');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }
}
