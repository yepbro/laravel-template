<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Documents the project-owned auth route contract.
 *
 * These tests pin route names, URIs, HTTP methods, middleware, and controller
 * ownership. All routes must resolve to App\Http\Controllers\Auth controllers,
 * not Laravel\Fortify or any other third-party package controllers.
 *
 * GET /login, /register, /forgot-password, /reset-password/{token}, and other
 * public auth pages serve the same Vue SPA shell (SpaController) on canonical
 * URLs; named routes such as `login` and `password.reset` stay stable for URL
 * generation and non-GET actions.
 */
class FortifyParityRouteTest extends TestCase
{
    /**
     * @param list<string> $expectedMethods
     * @param list<string> $requiredMiddleware
     */
    #[DataProvider('activeRouteContractProvider')]
    public function test_active_route_contract(
        string $routeName,
        string $expectedUri,
        array $expectedMethods,
        array $requiredMiddleware,
    ): void {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull(
            $route,
            "Route '{$routeName}' is not registered. Update this test if the route contract intentionally changes.",
        );

        $this->assertSame(
            $expectedUri,
            $route->uri(),
            "Route '{$routeName}' URI mismatch -- expected '{$expectedUri}', got '{$route->uri()}'.",
        );

        $registeredMethods = $route->methods();
        foreach ($expectedMethods as $method) {
            $this->assertContains(
                strtoupper($method),
                $registeredMethods,
                "Route '{$routeName}' does not accept {$method}.",
            );
        }

        $assignedMiddleware = $route->middleware();
        foreach ($requiredMiddleware as $mw) {
            $this->assertContains(
                $mw,
                $assignedMiddleware,
                "Route '{$routeName}' is missing required middleware '{$mw}'.",
            );
        }
    }

    /**
     * @return array<string, array{string, string, list<string>, list<string>}>
     */
    public static function activeRouteContractProvider(): array
    {
        return [
            // -- Authentication -----------------------------------------------
            'login GET SPA shell'   => ['login',       'login',    ['GET'],    ['web', 'guest:web']],
            'login POST store'      => ['login.store', 'login',    ['POST'],   ['web', 'guest:web', 'throttle:login']],
            'logout POST'           => ['logout',      'logout',   ['POST'],   ['web', 'auth:web']],

            // -- Registration -------------------------------------------------
            'register GET SPA shell' => ['register',       'register', ['GET'],  ['web', 'guest:web']],
            'register POST store'   => ['register.store', 'register', ['POST'], ['web', 'guest:web']],

            // -- Password Reset -----------------------------------------------
            'password.request GET SPA shell' => ['password.request', 'forgot-password',        ['GET'],  ['web', 'guest:web']],
            'password.email POST'   => ['password.email',   'forgot-password',        ['POST'], ['web', 'guest:web']],
            'password.reset GET SPA shell' => ['password.reset',   'reset-password/{token}', ['GET'],  ['web', 'guest:web']],
            'password.update POST'  => ['password.update',  'reset-password',         ['POST'], ['web', 'guest:web']],

            // -- Password Confirmation ----------------------------------------
            'password.confirm GET'        => ['password.confirm',       'user/confirm-password',          ['GET'],  ['web', 'auth:web']],
            'password.confirm.store POST' => ['password.confirm.store', 'user/confirm-password',          ['POST'], ['web', 'auth:web']],
            'password.confirmation GET'   => ['password.confirmation',  'user/confirmed-password-status', ['GET'],  ['web', 'auth:web']],

            // -- Profile & Password Update ------------------------------------
            'user-password.update PUT'            => ['user-password.update',            'user/password',            ['PUT'], ['web', 'auth:web']],
            'user-profile-information.update PUT' => ['user-profile-information.update', 'user/profile-information', ['PUT'], ['web', 'auth:web']],

            // -- Two-Factor Challenge (pre-auth) ------------------------------
            'two-factor.login GET'        => ['two-factor.login',       'two-factor-challenge', ['GET'],  ['web', 'guest:web']],
            'two-factor.login.store POST' => ['two-factor.login.store', 'two-factor-challenge', ['POST'], ['web', 'guest:web', 'throttle:two-factor']],

            // -- Two-Factor Management (authenticated) ------------------------
            'two-factor.enable POST'    => ['two-factor.enable',   'user/two-factor-authentication',            ['POST'],   ['web', 'auth:web', 'password.confirm']],
            'two-factor.confirm POST'   => ['two-factor.confirm',  'user/confirmed-two-factor-authentication',  ['POST'],   ['web', 'auth:web', 'password.confirm']],
            'two-factor.disable DELETE' => ['two-factor.disable',  'user/two-factor-authentication',            ['DELETE'], ['web', 'auth:web', 'password.confirm']],
            'two-factor.qr-code GET'                   => ['two-factor.qr-code',                  'user/two-factor-qr-code',        ['GET'], ['web', 'auth:web', 'password.confirm']],
            'two-factor.secret-key GET'                => ['two-factor.secret-key',               'user/two-factor-secret-key',     ['GET'], ['web', 'auth:web', 'password.confirm']],
            'two-factor.recovery-codes GET'            => ['two-factor.recovery-codes',           'user/two-factor-recovery-codes', ['GET'], ['web', 'auth:web', 'password.confirm']],
            'two-factor.regenerate-recovery-codes POST' => ['two-factor.regenerate-recovery-codes', 'user/two-factor-recovery-codes', ['POST'], ['web', 'auth:web', 'password.confirm']],

            // -- Passkeys: guest login ----------------------------------------
            'passkeys.authenticate.options GET' => ['passkeys.authenticate.options', 'passkeys/login/options',  ['GET'],  ['web', 'guest:web']],
            'passkeys.authenticate POST'        => ['passkeys.authenticate',         'passkeys/login',          ['POST'], ['web', 'guest:web']],

            // -- Passkeys: password confirmation via passkey ------------------
            'passkeys.confirm.options GET' => ['passkeys.confirm.options', 'passkeys/confirm/options', ['GET'],  ['web', 'auth:web']],
            'passkeys.confirm POST'        => ['passkeys.confirm',         'passkeys/confirm',         ['POST'], ['web', 'auth:web']],

            // -- Passkeys: management (password-confirmed) --------------------
            'passkeys.index GET'            => ['passkeys.index',            'user/passkeys',             ['GET'],    ['web', 'auth:web', 'password.confirm']],
            'passkeys.register.options GET' => ['passkeys.register.options', 'user/passkeys/options',     ['GET'],    ['web', 'auth:web', 'password.confirm']],
            'passkeys.register.store POST'  => ['passkeys.register.store',   'user/passkeys',             ['POST'],   ['web', 'auth:web', 'password.confirm']],
            'passkeys.destroy DELETE'       => ['passkeys.destroy',          'user/passkeys/{passkey}',   ['DELETE'], ['web', 'auth:web', 'password.confirm']],
        ];
    }

    public function test_reset_password_get_serves_spa_shell_preserving_token_and_query(): void
    {
        $this->get('/reset-password/sample-token')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/reset-password/simple-token?email=test@example.com')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    /**
     * @param non-empty-string $path
     */
    #[DataProvider('spaAuthHostViewPathsProvider')]
    public function test_public_auth_path_returns_spa_host_shell(string $path): void
    {
        $this->get($path)
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function spaAuthHostViewPathsProvider(): array
    {
        return [
            'canonical login'           => ['/login'],
            'canonical register'        => ['/register'],
            'canonical forgot-password' => ['/forgot-password'],
            'canonical reset-password'  => ['/reset-password/abc'],
            'legacy spa login'          => ['/spa/auth/login'],
            'legacy spa register'       => ['/spa/auth/register'],
            'legacy spa forgot'         => ['/spa/auth/forgot-password'],
            'legacy spa reset'          => ['/spa/auth/reset-password/abc'],
        ];
    }

    public function test_named_auth_urls_use_canonical_paths(): void
    {
        $this->assertSame(url('/login'), route('login'));
        $this->assertSame(url('/register'), route('register'));
        $this->assertSame(
            url('/forgot-password'),
            route('password.request'),
        );
        $this->assertSame(
            url('/reset-password/tok'),
            route('password.reset', ['token' => 'tok']),
        );
    }

    public function test_two_factor_challenge_get_without_session_redirects_to_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect(route('login'));
    }

    /**
     * Asserts that all app-owned auth routes (non-view GET routes) resolve to
     * project controllers in App\Http\Controllers\Auth, not any package controller.
     */
    public function test_non_view_routes_use_app_owned_controllers(): void
    {
        $actionRoutes = [
            'login.store',
            'logout',
            'register.store',
            'password.email',
            'password.update',
            'password.confirm.store',
            'password.confirmation',
            'user-password.update',
            'user-profile-information.update',
            'two-factor.login.store',
            'two-factor.enable',
            'two-factor.confirm',
            'two-factor.disable',
            'two-factor.qr-code',
            'two-factor.secret-key',
            'two-factor.recovery-codes',
            'two-factor.regenerate-recovery-codes',
            'passkeys.authenticate.options',
            'passkeys.authenticate',
            'passkeys.confirm.options',
            'passkeys.confirm',
            'passkeys.index',
            'passkeys.register.options',
            'passkeys.register.store',
            'passkeys.destroy',
        ];

        foreach ($actionRoutes as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route '{$name}' is not registered.");

            $action = $route->getActionName();
            $this->assertStringContainsString(
                'App\\Http\\Controllers\\Auth\\',
                $action,
                "Route '{$name}' action '{$action}' must be an app-owned controller.",
            );
        }
    }

    // -- Feature-gated routes (absent while features are disabled) ----------

    /**
     * Asserts that email verification routes are absent and the feature is disabled.
     *
     * Future contract when config/auth_features.php email_verification => true:
     *   verification.notice  GET  email/verify                      web, auth:web
     *   verification.send    POST email/verification-notification    web, auth:web, throttle:6,1
     *   verification.verify  GET  email/verify/{id}/{hash}          web, auth:web, signed, throttle:6,1
     *
     * To activate: set features.email_verification = true in config/auth_features.php.
     */
    public function test_email_verification_routes_are_absent_while_feature_is_disabled(): void
    {
        $this->assertFalse(
            config('auth_features.features.email_verification'),
            'Email verification feature must remain disabled until the migration stage is implemented.',
        );

        foreach (['verification.notice', 'verification.send', 'verification.verify'] as $routeName) {
            $this->assertNull(
                Route::getRoutes()->getByName($routeName),
                "Route '{$routeName}' should not be registered while email_verification is disabled.",
            );
        }
    }

    /**
     * Asserts that phone verification routes are absent and the feature is disabled.
     *
     * Future contract when config/auth_features.php phone_verification => true:
     *   phone.verification.send    POST phone/verification-notification  web, auth:web, throttle:6,1
     *   phone.verification.verify  POST phone/verify                     web, auth:web, throttle:6,1
     *
     * To activate: set features.phone_verification = true in config/auth_features.php.
     */
    public function test_phone_verification_routes_are_absent_while_feature_is_disabled(): void
    {
        $this->assertFalse(
            config('auth_features.features.phone_verification'),
            'Phone verification feature must remain disabled until the migration stage is implemented.',
        );

        foreach (['phone.verification.send', 'phone.verification.verify'] as $routeName) {
            $this->assertNull(
                Route::getRoutes()->getByName($routeName),
                "Route '{$routeName}' should not be registered while phone_verification is disabled.",
            );
        }
    }

    /**
     * Asserts the passkeys feature flag is enabled so routes are wired.
     */
    public function test_passkeys_feature_is_enabled(): void
    {
        $this->assertTrue(
            config('auth_features.features.passkeys'),
            'Passkeys feature must be enabled. Set features.passkeys = true in config/auth_features.php.',
        );
    }

    /**
     * Asserts that all app-owned passkey routes resolve to project controllers,
     * not the laravel/passkeys package controllers.
     */
    public function test_passkey_routes_use_app_owned_controllers(): void
    {
        $routeNames = [
            'passkeys.authenticate.options',
            'passkeys.authenticate',
            'passkeys.confirm.options',
            'passkeys.confirm',
            'passkeys.index',
            'passkeys.register.options',
            'passkeys.register.store',
            'passkeys.destroy',
        ];

        foreach ($routeNames as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route '{$name}' is not registered.");
            $this->assertStringContainsString(
                'App\\Http\\Controllers\\Auth\\',
                $route->getActionName(),
                "Route '{$name}' must use an app-owned controller.",
            );
        }
    }

    /**
     * Asserts that the laravel/passkeys package has not auto-registered its own routes.
     *
     * Passkeys::ignoreRoutes() is called in AppServiceProvider::register() to prevent
     * the package from auto-exposing endpoints. Project-owned routes use distinct names.
     */
    public function test_package_passkey_auto_routes_are_absent(): void
    {
        $packageRouteNames = [
            'passkey.login-options',
            'passkey.login',
            'passkey.confirm-options',
            'passkey.confirm',
            'passkey.registration-options',
            'passkey.store',
            'passkey.destroy',
        ];

        foreach ($packageRouteNames as $routeName) {
            $this->assertNull(
                Route::getRoutes()->getByName($routeName),
                "Package route '{$routeName}' must not be auto-registered; Passkeys::ignoreRoutes() must be called before boot.",
            );
        }
    }

    /**
     * Asserts that no route action references the Laravel\Fortify namespace.
     */
    public function test_no_route_uses_fortify_controller(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            $this->assertStringNotContainsString(
                'Laravel\\Fortify',
                $action,
                "Route '{$route->getName()}' ({$route->uri()}) must not use a Laravel\\Fortify controller.",
            );
        }
    }
}
