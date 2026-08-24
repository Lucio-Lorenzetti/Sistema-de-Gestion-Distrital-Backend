<?php

namespace App\Services;

use App\Models\User;

class UserScopeCache
{
    /**
     * Recalcula users.rama_id/grupo_id — el caché que Programas ya lee para
     * Educador (ProgramController::index/store, ProgramPolicy). Sincroniza
     * ÚNICAMENTE desde la asignación de Educador de este usuario: Jefe de
     * Grupo y Aux Prog Rama tienen su propio scope real en el pivot user_roles
     * (ver User::roleScope()) y no dependen de este caché.
     */
    public static function sync(User $user): void
    {
        $scope = $user->roleScope('Educador');

        $user->forceFill([
            'rama_id' => $scope?->rama_id,
            'grupo_id' => $scope?->grupo_id,
        ])->save();
    }
}
