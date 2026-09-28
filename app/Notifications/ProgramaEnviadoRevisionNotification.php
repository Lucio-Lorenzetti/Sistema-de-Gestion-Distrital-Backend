<?php

namespace App\Notifications;

use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgramaEnviadoRevisionNotification extends Notification
{
    use Queueable;

    public function __construct(private Program $program)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'programa_enviado_revision',
            'mensaje' => "El programa \"{$this->program->titulo}\" fue enviado a revisión.",
            'url' => "/gestion-programas/revisar/{$this->program->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/') . "/gestion-programas/revisar/{$this->program->id}";

        return (new MailMessage)
            ->subject('Un programa fue enviado a revisión')
            ->greeting("Hola {$notifiable->name},")
            ->line("El programa \"{$this->program->titulo}\" fue enviado a revisión.")
            ->action('Ver programa', $url)
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
