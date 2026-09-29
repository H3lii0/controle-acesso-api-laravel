<?php

namespace App\Notifications;

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
        $formattedExpiration = $this->expiresAt
            ->copy()
            ->setTimezone((string) config('school.timezone'))
            ->format('d/m/Y \à\s H:i');

        return (new MailMessage)
            ->subject('Ative seu acesso - '.config('school.name'))
            ->greeting('Olá, '.$notifiable->full_name.'!')
            ->line('Seu acesso ao sistema de controle escolar foi criado.')
            ->line('Use o botão abaixo para criar sua senha e ativar a conta.')
            ->action('Criar minha senha', $activationUrl)
            ->line('Este convite expira em '.$formattedExpiration.'.')
            ->line('Se você não esperava este convite, desconsidere esta mensagem.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
