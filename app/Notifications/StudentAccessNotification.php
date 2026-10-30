<?php

namespace App\Notifications;

use App\Enums\AccessReadingResult;
use App\Models\StudentAccessRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly StudentAccessRecord $record,
        public readonly AccessReadingResult $result,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->notificationData();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->notificationData();

        return (new MailMessage)
            ->subject($data['title'].' - '.config('school.name'))
            ->view('emails.student-access', [
                'recipientName' => $notifiable->full_name,
                'studentName' => $data['student_name'],
                'movementLabel' => $data['movement_label'],
                'occurredAt' => $data['occurred_at'],
                'schoolName' => config('school.name'),
                'portalUrl' => rtrim((string) config('app.frontend_url'), '/').'/responsavel',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->notificationData();
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationData(): array
    {
        $isEntry = $this->result === AccessReadingResult::EntryRegistered;
        $occurredAt = $isEntry ? $this->record->entered_at : $this->record->exited_at;

        return [
            'kind' => 'student_access',
            'access_record_id' => $this->record->id,
            'student_id' => $this->record->student_id,
            'student_name' => $this->record->student->full_name,
            'movement' => $isEntry ? 'entry' : 'exit',
            'movement_label' => $isEntry ? 'Entrada registrada' : 'Saída registrada',
            'title' => $isEntry ? 'Entrada registrada' : 'Saída registrada',
            'message' => $isEntry
                ? 'registrou entrada na escola.'
                : 'registrou saída da escola.',
            'occurred_at' => $occurredAt
                ? $occurredAt->setTimezone(config('school.timezone'))->format('d/m/Y \à\s H:i')
                : null,
        ];
    }
}
