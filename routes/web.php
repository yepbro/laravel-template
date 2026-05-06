<?php

declare(strict_types=1);

use App\Auth\AuthFeatures;
use App\Auth\Routing\AuthRouteRegistrar;
use App\Http\Controllers\Auth\SecurityStatusController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);
Route::get('/spa/{path?}', SpaController::class)->where('path', '.*');

Route::middleware(['web', 'auth:' . AuthFeatures::make()->guard()])->group(function (): void {
    Route::get('/user/security-status', SecurityStatusController::class)
        ->name('auth.security-status');
});

// Core authentication routes (always active).
AuthRouteRegistrar::authentication();
AuthRouteRegistrar::registration();
AuthRouteRegistrar::passwordReset();
AuthRouteRegistrar::passwordConfirmation();
AuthRouteRegistrar::profileAndPassword();
AuthRouteRegistrar::twoFactor();

if (AuthFeatures::make()->emailVerificationEnabled()) {
    AuthRouteRegistrar::emailVerification();
}

if (AuthFeatures::make()->phoneVerificationEnabled()) {
    AuthRouteRegistrar::phoneVerification();
}

if (AuthFeatures::make()->passkeysEnabled()) {
    AuthRouteRegistrar::passkeys();
}
