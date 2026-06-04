<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;

class MobileResetPassword extends ResetPassword
{
    /**
     * @param  CanResetPassword  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        $base = rtrim((string) config('clockwork.mobile_password_reset_url'), '?');

        return $base.'?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
