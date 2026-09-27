<?php

namespace App\Notifications;

use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Canales 'database' (campanita) + 'mail'. Sincrónica (no ShouldQueue): no hay
// worker de colas en Render free, así que el mail se manda en el mismo
// request que aprueba/rechaza el programa.
class ProgramaRevisadoNotification extends Notification
{
    use Queueable;

    public function __construct(private Program $program, private string $estado)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $aprobado = $this->estado === 'aprobado';

        return [
            'tipo' => $aprobado ? 'programa_aprobado' : 'programa_rechazado',
            'mensaje' => $aprobado
                ? "Tu programa \"{$this->program->titulo}\" fue aprobado."
                : "Tu programa \"{$this->program->titulo}\" fue rechazado.",
            'url' => "/gestion-programas/revisar/{$this->program->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $aprobado = $this->estado === 'aprobado';
        $url = rtrim(config('app.frontend_url'), '/') . "/gestion-programas/revisar/{$this->program->id}";

        $mensaje = (new MailMessage)
            ->subject($aprobado ? 'Tu programa fue aprobado' : 'Tu programa fue rechazado')
            ->greeting("Hola {$notifiable->name},")
            ->line($aprobado
                ? "Tu programa \"{$this->program->titulo}\" fue aprobado."
                : "Tu programa \"{$this->program->titulo}\" fue rechazado.");

        if (!$aprobado && $this->program->motivo_rechazo) {
            $mensaje->line("Motivo: {$this->program->motivo_rechazo}");
        }

        return $mensaje
            ->action('Ver programa', $url)
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
