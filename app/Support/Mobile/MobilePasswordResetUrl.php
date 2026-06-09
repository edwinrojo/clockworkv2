<?php

namespace App\Support\Mobile;

class MobilePasswordResetUrl
{
    public static function webLink(string $token, string $email): string
    {
        return route('mobile.password-reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public static function deepLink(string $token, string $email): string
    {
        $base = rtrim((string) config('clockwork.mobile_password_reset_url'), '?');

        return $base.'?'.http_build_query([
            'token' => $token,
            'email' => $email,
        ]);
    }
}
