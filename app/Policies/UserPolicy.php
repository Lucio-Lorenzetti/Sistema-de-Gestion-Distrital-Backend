<?php

namespace App\Policies;

use App\Models\Grupo;
use App\Models\User;

class UserPolicy
{
    /**
     * ¿Puede abrir el listado de usuarios? El alcance real (todos vs. solo su
     * grupo) lo aplica el controller, igual que ProgramController::index() con Programas.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole(['Director', 'Jefe de Grupo']);
    }

    /**
     * Director: cualquiera. Jefe de Grupo: solo usuarios de SU grupo — el
     * scope real de "su grupo" vive en el pivot (roleScope), no en
     * $actor->grupo_id (que es el caché de Educador y puede no coincidir).
     */
    public function view(User $actor, User $target): bool
    {
        if ($actor->hasRole('Director')) {
            return true;
        }

        if ($actor->hasRole('Jefe de Grupo')) {
            return $target->grupo_id === $actor->roleScope('Jefe de Grupo')?->grupo_id;
        }

        return false;
    }

    /**
     * Nadie por policy — solo Developer borra usuarios, vía el Gate::before.
     * El guard de no poder borrar id===1 sigue viviendo en el modelo.
     */
    public function delete(User $actor, User $target): bool
    {
        return false;
    }

    /**
     * Asignar/quitar cualquier rol sin pasar por solicitud/designación — solo Developer.
     */
    public function assignRoleFreely(User $actor): bool
    {
        return false;
    }

    /**
     * Director/Developer siempre puede designar Jefe de Grupo de cualquier
     * grupo. El Jefe de Grupo ACTUAL de ESE grupo puede traspasar su propio cargo.
     */
    public function designarJefeDeGrupo(User $actor, Grupo $grupo): bool
    {
        if ($actor->hasRole('Director')) {
            return true;
        }

        return $actor->hasRole('Jefe de Grupo')
            && $actor->roleScope('Jefe de Grupo')?->grupo_id === $grupo->id;
    }

    /**
     * El Director actual designa a su reemplazo (traspaso, nunca dos a la vez).
     */
    public function designarDirector(User $actor): bool
    {
        return $actor->hasRole('Director');
    }
}
