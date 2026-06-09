<?php

namespace App\Notifications;

use App\Support\Mobile\MobilePasswordResetUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;

class MobileResetPassword extends ResetPassword
{
    /**
     * @param  CanResetPassword  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return MobilePasswordResetUrl::webLink(
            $this->token,
            $notifiable->getEmailForPasswordReset(),
        );
    }
}
