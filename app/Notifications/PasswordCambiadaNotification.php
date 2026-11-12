<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Aviso de seguridad: la contraseña de la cuenta cambió (desde Mi Perfil o con
// el link de "Olvidé mi contraseña"). Si quien lo recibe no fue, es la señal
// de que alguien más entró a su cuenta. Solo mail: no tiene sentido en la
// campanita, que la lee quien ya está adentro.
class PasswordCambiadaNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $cuando = now()->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject('Tu contraseña fue cambiada')
            ->greeting("Hola {$notifiable->name},")
            ->line("La contraseña de tu cuenta se cambió el {$cuando} (hora de Argentina).")
            ->line('Si fuiste vos, no tenés que hacer nada. Por seguridad, cerramos las sesiones que tenías abiertas en otros dispositivos.')
            ->line('Si NO fuiste vos, alguien más pudo haber entrado a tu cuenta: recuperala ahora y avisale al Director o al Developer.')
            ->action('Recuperar mi cuenta', "{$frontendUrl}/recuperar-contrasena")
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
