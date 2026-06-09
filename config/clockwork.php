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
    | Deep link opened by the mobile password-reset bridge page
    | (`GET /mobile/reset-password`). Reset emails link to the HTTPS bridge;
    | this URL is where the page redirects into the Flutter app.
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
    | Default mobile GPS accuracy (meters)
    |--------------------------------------------------------------------------
    |
    | Applied when the app does not send an accuracy value on check-in. Mobile
    | GPS is often off by 10–50 m even when maps look correct.
    |
    */

    'default_gps_accuracy_meters' => (int) env('CLOCKWORK_DEFAULT_GPS_ACCURACY', 30),

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

    /*
    |--------------------------------------------------------------------------
    | Legacy ATTLOG export (biometric HRIS import)
    |--------------------------------------------------------------------------
    |
    | Tab-separated _attlog.dat files for the legacy Clockwork scanner import.
    | Column 0 uses users.employee_number. Register a matching virtual scanner
    | in the legacy system using device_uid.
    |
    */

    'legacy_attlog_export' => [
        'device_uid' => (int) env('CLOCKWORK_LEGACY_EXPORT_DEVICE_UID', 99),
        'state' => (int) env('CLOCKWORK_LEGACY_EXPORT_STATE', 0),
        'mode' => (int) env('CLOCKWORK_LEGACY_EXPORT_MODE', 3),
        'work_code' => (int) env('CLOCKWORK_LEGACY_EXPORT_WORK_CODE', 0),
    ],

];
