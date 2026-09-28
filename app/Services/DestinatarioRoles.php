<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ubicar destinatarios de notificación por rol (+ scope, cuando aplica).
 * Reutilizado por las notificaciones de Cursos, Programas y Solicitudes de rol.
 */
class DestinatarioRoles
{
    public static function porRolYGrupo(string $rolNombre, int $grupoId): Collection
    {
        return User::whereHas('roles', function ($q) use ($rolNombre, $grupoId) {
            $q->where('roles.nombre', $rolNombre)->where('user_roles.grupo_id', $grupoId);
        })->get();
    }

    public static function porRolYRama(string $rolNombre, int $ramaId): Collection
    {
        return User::whereHas('roles', function ($q) use ($rolNombre, $ramaId) {
            $q->where('roles.nombre', $rolNombre)->where('user_roles.rama_id', $ramaId);
        })->get();
    }

    public static function porRol(string $rolNombre): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->where('roles.nombre', $rolNombre))->get();
    }
}
