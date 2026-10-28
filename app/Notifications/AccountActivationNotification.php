<?php

namespace App\Notifications;

use App\Enums\AccountType;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $activationUrl = rtrim((string) config('app.frontend_url'), '/')
            .'/ativar-conta?token='.rawurlencode($this->token);
        if ($notifiable->account_type === AccountType::Guardian) {
            $activationUrl .= '&portal=responsavel';
        }
        $formattedExpiration = $this->expiresAt
            ->copy()
            ->setTimezone((string) config('school.timezone'))
            ->format('d/m/Y \à\s H:i');

        return (new MailMessage)
            ->subject('Ative seu acesso - '.config('school.name'))
            ->action('Criar minha senha', $activationUrl)
            ->view('emails.account-activation', [
                'recipientName' => $notifiable->full_name,
                'activationUrl' => $activationUrl,
                'expiration' => $formattedExpiration,
                'schoolName' => config('school.name'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
