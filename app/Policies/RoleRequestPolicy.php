<?php

namespace App\Policies;

use App\Models\RoleRequest;
use App\Models\User;

class RoleRequestPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole(['Director', 'Jefe de Grupo']);
    }

    /**
     * Educador: aprueba el Jefe de Grupo DEL GRUPO PEDIDO en la solicitud
     * (no cualquier Jefe de Grupo). El resto de los roles solicitables
     * (Aux Prog General / Aux Prog Rama / Aux Comunicación) son "de distrito" → Director.
     */
    public function approve(User $actor, RoleRequest $solicitud): bool
    {
        if (strtolower($solicitud->role->nombre) === 'educador') {
            return $actor->hasRole('Jefe de Grupo')
                && $actor->roleScope('Jefe de Grupo')?->grupo_id === $solicitud->grupo_id;
        }

        return $actor->hasRole('Director');
    }

    public function reject(User $actor, RoleRequest $solicitud): bool
    {
        return $this->approve($actor, $solicitud);
    }
}
