<?php

namespace App\Notifications;

use Carbon\Carbon;
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
            ? 'CV HRIS'
            : config('app.name', 'CV HRIS');

        return (new MailMessage)
            ->subject('Verifikasi Email ' . $appName)
            ->view('emails.auth.verify-email', [
                'appName' => $appName,
                'user' => $notifiable,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'expirationMinutes' => Config::get('auth.verification.expire', 60),
                'supportEmail' => config('mail.from.address'),
            ]);
    }

    private function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
