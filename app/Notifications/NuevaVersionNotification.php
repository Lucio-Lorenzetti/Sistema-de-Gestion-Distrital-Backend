<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Aviso de que el sistema pasó a una versión nueva. Lo reciben el Director y los
// Developer (ver App\Services\AvisoNuevaVersion). Solo mail: no hay nada que
// resolver adentro del sistema, es una novedad para estar al tanto.
class NuevaVersionNotification extends Notification
{
    use Queueable;

    public function __construct(private string $version, private ?string $titulo = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        $mail = (new MailMessage)
            ->subject('Nueva versión del Sistema Distrital')
            // El "saludo" de MailMessage es el título grande del cuerpo del mail.
            ->greeting("🚀 Nueva versión en marcha: v{$this->version}")
            ->line("Llegó una nueva versión del sistema del Distrito 3: ahora estamos en la versión v{$this->version}. Gracias por acompañar el camino.");

        if (filled($this->titulo)) {
            $mail->line("En esta versión: {$this->titulo}.");
        }

        return $mail
            ->action('Abrir el sistema', "{$frontendUrl}/dashboard")
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
