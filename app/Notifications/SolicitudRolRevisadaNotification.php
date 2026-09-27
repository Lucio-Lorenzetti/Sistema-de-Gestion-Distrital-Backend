<?php

namespace App\Notifications;

use App\Models\RoleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// El hueco más importante que había: hoy nadie avisaba nada (ni in-app ni
// mail) cuando se resolvía una solicitud de rol — quien se registra no tenía
// forma de enterarse de que ya puede entrar, salvo probar a loguearse.
class SolicitudRolRevisadaNotification extends Notification
{
    use Queueable;

    public function __construct(private RoleRequest $solicitud)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $aprobada = $this->solicitud->estado === 'aprobada';

        return [
            'tipo' => $aprobada ? 'solicitud_rol_aprobada' : 'solicitud_rol_rechazada',
            'mensaje' => $aprobada
                ? "Tu solicitud de rol \"{$this->solicitud->role->nombre}\" fue aprobada. Ya podés ingresar."
                : "Tu solicitud de rol \"{$this->solicitud->role->nombre}\" fue rechazada.",
            'url' => $aprobada ? '/mi-perfil' : null,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $aprobada = $this->solicitud->estado === 'aprobada';
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        $mensaje = (new MailMessage)
            ->subject($aprobada ? 'Tu solicitud fue aprobada' : 'Tu solicitud fue rechazada')
            ->greeting("Hola {$notifiable->name},");

        if ($aprobada) {
            $mensaje->line("Tu solicitud de rol \"{$this->solicitud->role->nombre}\" fue aprobada. Ya podés ingresar al sistema.")
                ->action('Ingresar', "{$frontendUrl}/login");
        } else {
            $mensaje->line("Tu solicitud de rol \"{$this->solicitud->role->nombre}\" fue rechazada.");
            if ($this->solicitud->motivo_rechazo) {
                $mensaje->line("Motivo: {$this->solicitud->motivo_rechazo}");
            }
        }

        return $mensaje->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
