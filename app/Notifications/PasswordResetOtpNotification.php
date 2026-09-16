<?php

namespace App\Notifications;

use App\Actions\PasswordResetOtp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    public function __construct(public string $otp) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password reset code')
            ->markdown('mail.password-reset-otp', [
                'otp' => $this->otp,
                'minutes' => PasswordResetOtp::EXPIRES_MINUTES,
            ]);
    }
}
