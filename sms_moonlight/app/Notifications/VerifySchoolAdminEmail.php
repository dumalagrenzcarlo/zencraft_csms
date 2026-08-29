<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifySchoolAdminEmail extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $schoolName,
        private readonly string $slug,
        private readonly string $email,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'signup.verify',
            now()->addHours(config('saas.public_signup.verification_expiration_hours', 24)),
            ['tenant' => $this->tenantId, 'email' => $this->email]
        );

        return (new MailMessage)
            ->subject("Verify your {$this->schoolName} CSMS account")
            ->greeting('Welcome to ZenCraft CSMS!')
            ->line("Your free workspace, {$this->schoolName}, has been created.")
            ->action('Verify email and activate login', $verificationUrl)
            ->line("After verification, your workspace is available at /{$this->slug}/admin.")
            ->line('This verification link expires for your security.');
    }
}
