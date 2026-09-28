<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CursoCreadoEditadoNotification extends Notification
{
    use Queueable;

    public function __construct(private Course $curso, private bool $esNuevo)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->esNuevo ? 'curso_creado' : 'curso_editado',
            'mensaje' => $this->esNuevo
                ? "Se creó un curso nuevo: \"{$this->curso->titulo}\"."
                : "Se editó el curso \"{$this->curso->titulo}\".",
            'url' => '/gestion-cursos/administrar',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/') . '/gestion-cursos/administrar';

        return (new MailMessage)
            ->subject($this->esNuevo ? 'Nuevo curso publicado' : 'Se editó un curso')
            ->greeting("Hola {$notifiable->name},")
            ->line($this->esNuevo
                ? "Se creó un curso nuevo: \"{$this->curso->titulo}\"."
                : "Se editó el curso \"{$this->curso->titulo}\".")
            ->action('Ver cursos', $url)
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
