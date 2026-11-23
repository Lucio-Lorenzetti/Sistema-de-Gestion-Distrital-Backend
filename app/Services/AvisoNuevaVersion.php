<?php

namespace App\Services;

use App\Notifications\NuevaVersionNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Avisa por mail al Director y a todos los Developer que el sistema pasó a una
 * versión nueva. Lo dispara el build del frontend en Vercel (ver
 * SystemController::nuevaVersion y scripts/avisar-nueva-version.mjs del front).
 *
 * Reglas:
 * - Se avisa una sola vez por versión: la última avisada queda en el caché, así
 *   que cada deploy que no cambia el número no manda nada.
 * - Solo se avisa si la versión es MÁS NUEVA que la última avisada. Un rollback a
 *   una versión anterior no se anuncia como "nueva versión".
 * - Si no hay ninguna avisada todavía (primera vez), se avisa la versión actual.
 * - Si el SMTP falla, SafeNotifier lo registra en el log y sigue: la versión igual
 *   queda como avisada, no se reintenta.
 */
class AvisoNuevaVersion
{
    private const CLAVE_ULTIMA = 'version_avisada';

    /**
     * @param  Collection|null  $destinatarios  Solo para tests; por defecto, Director y Developer activos.
     * @return bool  true si mandó el aviso, false si no correspondía.
     */
    public static function avisar(string $version, ?string $titulo = null, ?Collection $destinatarios = null): bool
    {
        $ultima = Cache::get(self::CLAVE_ULTIMA);

        if ($ultima !== null && version_compare($version, $ultima, '<=')) {
            return false;
        }

        // Si llegan dos pedidos a la vez (por ejemplo, un reintento del build),
        // solo uno manda los mails.
        if (! Cache::add("aviso_version_en_curso:{$version}", true, now()->addMinutes(5))) {
            return false;
        }

        foreach ($destinatarios ?? self::destinatarios() as $usuario) {
            SafeNotifier::enviar($usuario, new NuevaVersionNotification($version, $titulo));
        }

        Cache::forever(self::CLAVE_ULTIMA, $version);

        return true;
    }

    /**
     * El Director y cualquier usuario con el rol Developer (hoy es uno, pero puede
     * haber más). Sin repetidos, si alguien tiene los dos roles, y sin cuentas
     * dadas de baja.
     */
    public static function destinatarios(): Collection
    {
        return DestinatarioRoles::porRol('Director')
            ->merge(DestinatarioRoles::porRol('Developer'))
            ->where('activo', true)
            ->unique('id')
            ->values();
    }
}
