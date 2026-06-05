<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late check-in grace period
    |--------------------------------------------------------------------------
    |
    | Minutes after check-in opens (or event start if not set) before a
    | mobile check-in is recorded as "late" instead of "present".
    |
    */

    'late_grace_minutes' => (int) env('CLOCKWORK_LATE_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Mobile password reset URL
    |--------------------------------------------------------------------------
    |
    | Deep link or in-app route used in reset emails for the Flutter app.
    | Query string includes token and email for POST /api/v1/auth/reset-password.
    |
    */

    'mobile_password_reset_url' => env(
        'CLOCKWORK_MOBILE_PASSWORD_RESET_URL',
        'clockwork://reset-password',
    ),

    /*
    |--------------------------------------------------------------------------
    | Venue geofence cache (seconds)
    |--------------------------------------------------------------------------
    |
    | Caches venue geofence fields during check-in to reduce DB reads under load.
    | Set to 0 to disable.
    |
    */

    'venue_geofence_cache_seconds' => (int) env('CLOCKWORK_VENUE_GEOFENCE_CACHE_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | Employee email verification code
    |--------------------------------------------------------------------------
    |
    | Six-digit codes sent to employees after HR import or on resend. Required
    | before mobile login. TTL is in minutes.
    |
    */

    'email_verification_code_ttl_minutes' => (int) env('CLOCKWORK_EMAIL_VERIFICATION_TTL', 30),

    'email_verification_code_length' => 6,

];
