<?php

namespace App\Notifications;

use App\Models\RoleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudRolCreadaNotification extends Notification
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
        $solicitante = $this->solicitud->user->nombre_visible ?? $this->solicitud->user->name;

        return [
            'tipo' => 'solicitud_rol_creada',
            'mensaje' => "{$solicitante} solicitó el rol \"{$this->solicitud->role->nombre}\".",
            'url' => '/usuarios',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $solicitante = $this->solicitud->user->nombre_visible ?? $this->solicitud->user->name;
        $url = rtrim(config('app.frontend_url'), '/') . '/usuarios';

        return (new MailMessage)
            ->subject('Nueva solicitud de rol para revisar')
            ->greeting("Hola {$notifiable->name},")
            ->line("{$solicitante} solicitó el rol \"{$this->solicitud->role->nombre}\".")
            ->action('Revisar solicitud', $url)
            ->salutation('— Distrito 3, Zona 13, Scouts de Argentina');
    }
}
