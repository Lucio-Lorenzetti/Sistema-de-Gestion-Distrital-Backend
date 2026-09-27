<?php

namespace App\Notifications;

use App\Models\ProgramNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComentarioNuevoNotification extends Notification
{
    use Queueable;

    public function __construct(private ProgramNote $nota, private bool $esRespuesta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $autor = $this->nota->user->nombre_visible ?? $this->nota->user->name;

        return [
            'tipo' => 'comentario_nuevo',
            'mensaje' => $this->esRespuesta
                ? "{$autor} te respondió en \"{$this->nota->program->titulo}\"."
                : "{$autor} comentó en \"{$this->nota->program->titulo}\".",
            'url' => "/gestion-programas/revisar/{$this->nota->program_id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $autor = $this->nota->user->nombre_visible ?? $this->nota->user->name;
        $url = rtrim(config('app.frontend_url'), '/') . "/gestion-programas/revisar/{$this->nota->program_id}";

        return (new MailMessage)
            ->subject($this->esRespuesta ? 'Te respondieron un comentario' : 'Nuevo comentario en tu programa')
            ->greeting("Hola {$notifiable->name},")
            ->line($this->esRespuesta
                ? "{$autor} te respondió en \"{$this->nota->program->titulo}\":"
                : "{$autor} comentó en \"{$this->nota->program->titulo}\":")
            ->line("\"{$this->nota->contenido}\"")
            ->action('Ver comentario', $url)
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
