<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = rtrim((string) config('app.frontend_url'), '/')
            .'/redefinir-senha?token='.rawurlencode($this->token)
            .'&email='.rawurlencode($notifiable->getEmailForPasswordReset());
        $expirationMinutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Redefinição de senha - '.config('school.name'))
            ->greeting('Olá, '.$notifiable->full_name.'!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->action('Redefinir minha senha', $resetUrl)
            ->line("Este link é válido por {$expirationMinutes} minutos e pode ser utilizado apenas uma vez.")
            ->line('Se você não solicitou a alteração, ignore esta mensagem. Sua senha continuará a mesma.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
