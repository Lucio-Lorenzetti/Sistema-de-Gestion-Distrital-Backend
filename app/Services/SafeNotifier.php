<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envía notificaciones (canal 'mail' + 'database') sin que un fallo del mail
 * tumbe la acción que lo disparó.
 *
 * No hay worker de colas en el plan free de Render, así que las notificaciones
 * son sincrónicas: se mandan en el mismo request que aprueba un programa, crea
 * un curso, etc. Antes, si el SMTP no respondía (caído, timeout, puerto
 * bloqueado), la excepción subía sin atajarse y el request entero fallaba con
 * 500 — aunque el cambio real (aprobar/rechazar/crear) ya se hubiera guardado
 * en la base. El usuario veía un error por algo que en realidad sí funcionó.
 *
 * Esto además evita que, en un ->each() sobre varios destinatarios, que le
 * falle el mail a uno corte el envío a los que siguen en la lista.
 */
class SafeNotifier
{
    public static function enviar(object $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $e) {
            Log::error('No se pudo enviar una notificación: ' . $e->getMessage(), [
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_type' => get_class($notifiable),
            ]);
        }
    }
}
