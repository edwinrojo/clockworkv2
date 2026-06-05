<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeEmailVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $code,
        private int $expiresInMinutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Confirm your Clockwork account'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->first_name]))
            ->line(__('Use this code in the Clockwork mobile app to confirm your email address:'))
            ->line('**'.$this->code.'**')
            ->line(__('This code expires in :minutes minutes.', ['minutes' => $this->expiresInMinutes]))
            ->line(__('After confirming, sign in with your work email and ID number (initial password).'));
    }
}
