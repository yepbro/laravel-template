<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Channels\FakePhoneOtpChannel;
use App\Auth\Contracts\PhoneOtpChannel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkeys;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Default to the fake channel until a real SMS provider is configured.
        // Override this binding in the container to swap in a real provider.
        $this->app->singleton(PhoneOtpChannel::class, FakePhoneOtpChannel::class);

        // Prevent the package from auto-registering passkey routes. Project-owned
        // routes are gated by auth_features.features.passkeys and registered via
        // routes/web.php. This must be called before the package boots.
        Passkeys::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login rate limiter: 5 attempts per minute, keyed by identifier + IP.
        // The identifier is read from the 'login' field or the 'email' fallback,
        // matching the normalization applied in LoginRequest::prepareForValidation().
        RateLimiter::for('login', function (Request $request) {
            $login      = $request->input('login');
            $identifier = is_string($login) ? $login : $request->string('email')->toString();

            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower($identifier) . '|' . $request->ip()),
            );
        });

        // Two-factor challenge rate limiter: 5 attempts per minute per pending
        // challenge session. The session key '_two_factor_login_id' is set by
        // AuthenticatedSessionController when a user with confirmed 2FA logs in.
        RateLimiter::for('two-factor', function (Request $request) {
            $userId = $request->session()->get('_two_factor_login_id', '');
            $key    = is_scalar($userId) ? (string) $userId : '';

            return Limit::perMinute(5)->by($key . '|' . $request->ip());
        });
    }
}
