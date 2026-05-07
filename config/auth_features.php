<?php

declare(strict_types=1);

/**
 * Project-owned auth feature configuration.
 *
 * This file is the single source of truth for which auth capabilities are active.
 * Feature flags default to parity with the original Fortify configuration.
 * Future features default to false until the corresponding implementation stage lands.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The guard used for web authentication. Must match an entry in config/auth.php.
    |
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Password Broker
    |--------------------------------------------------------------------------
    |
    | The password broker to use for password resets. Must match an entry in
    | the 'passwords' section of config/auth.php.
    |
    */
    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username Field
    |--------------------------------------------------------------------------
    |
    | The model attribute treated as the username when authenticating.
    |
    | 'email' names the REQUEST field accepted by password-reset endpoints
    | (e.g. 'email_address' for SPAs that prefer that name). It is purely
    | a request alias - the password broker always queries users.email
    | internally because that is the column used by the Eloquent user
    | provider. Changing this value does NOT change the database column.
    |
    */
    'username' => 'email',
    'email'    => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    |
    | When true, usernames are lowercased before persisting to the database.
    | Disable only if your database collation handles case-insensitivity natively.
    |
    */
    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Post-Authentication Redirect
    |--------------------------------------------------------------------------
    |
    | The path users are redirected to after a successful login or password reset.
    |
    */
    'home' => '/account',

    /*
    |--------------------------------------------------------------------------
    | Post-Reset Redirect
    |--------------------------------------------------------------------------
    |
    | Named route the user is redirected to after a successful password reset
    | via the web (non-JSON) flow. Defaults to the 'login' named route.
    |
    */
    'password_reset_redirect' => 'login',

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | When false, GET routes that return auth views are suppressed. Useful for
    | headless / SPA setups that handle routing client-side.
    |
    */
    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Auth Features
    |--------------------------------------------------------------------------
    |
    | Toggle individual auth capabilities.
    |
    */
    'features' => [
        // Core features
        'registration'                              => true,
        'reset_passwords'                           => true,
        'update_profile_information'                => true,
        'update_passwords'                          => true,
        'two_factor_authentication'                 => true,
        'two_factor_requires_confirmation'          => true,
        'two_factor_requires_password_confirmation' => true,

        // Future -- disabled until dedicated migration stage
        'email_verification'  => false,
        'phone_verification'  => false,
        'passkeys'            => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Mode
    |--------------------------------------------------------------------------
    |
    | Controls which credential type is accepted during registration.
    |
    | Supported: 'email', 'phone', 'both'
    |
    */
    'registration_mode' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Phone / OTP Configuration (skeleton for future stage)
    |--------------------------------------------------------------------------
    |
    | Used when registration_mode includes 'phone'. Driver and TTL settings
    | for one-time password delivery. Left intentionally minimal until the
    | phone-auth migration stage is implemented.
    |
    */
    'phone_otp' => [
        'driver'          => null, // e.g. 'twilio', 'vonage'
        'length'          => 6,
        'expires_minutes' => 10,
        'max_attempts'    => 5,
    ],

];
