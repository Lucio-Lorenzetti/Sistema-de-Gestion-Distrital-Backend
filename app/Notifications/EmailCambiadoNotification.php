<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Aviso de seguridad que se manda a la dirección ANTERIOR (no a la nueva)
// cuando cambia el correo de una cuenta: el dueño legítimo se entera aunque
// quien lo cambió sea otra persona con su sesión abierta. Como el correo ya
// cambió, el notifiable es una ruta anónima (Notification::route('mail', ...)),
// por eso el saludo no lleva nombre.
class EmailCambiadoNotification extends Notification
{
    use Queueable;

    public function __construct(private string $nuevoEmail)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cuando = now()->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject('El correo de tu cuenta fue cambiado')
            ->greeting('Hola,')
            ->line("El correo de tu cuenta del sistema del Distrito 3 se cambió a {$this->nuevoEmail} el {$cuando} (hora de Argentina).")
            ->line('Desde ahora, los avisos y los links para recuperar la contraseña van a esa dirección.')
            ->line('Si fuiste vos, no tenés que hacer nada.')
            ->line('Si NO fuiste vos, alguien tiene acceso a tu cuenta: avisale ya al Director o al Developer para que la recuperen.')
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
