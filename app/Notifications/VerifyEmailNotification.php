<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appName = config('app.name') === 'Laravel'
            ? 'Vitae'
            : config('app.name', 'Vitae');

        return (new MailMessage)
            ->subject('Verifikasi Email ' . $appName)
            ->view('emails.auth.verify-email', [
                'appName' => $appName,
                'user' => $notifiable,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'expirationMinutes' => max(1, (int) Config::get('auth.verification.expire', 60)),
                'supportEmail' => config('mail.from.address'),
            ]);
    }

    private function verificationUrl($notifiable): string
    {
        $expiresAt = $notifiable->email_verification_expires_at
            ?: now()->addMinutes(max(1, (int) Config::get('auth.verification.expire', 60)));

        return URL::temporarySignedRoute(
            'verification.verify',
            $expiresAt,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
