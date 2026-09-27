<?php

namespace App\Policies;

use App\Models\User;

class RolePolicy
{
    /**
     * Nadie por policy — solo Developer crea roles nuevos, vía el Gate::before.
     */
    public function create(User $actor): bool
    {
        return false;
    }

    /**
     * Mismo criterio que create(): solo Developer edita metadata de un rol.
     */
    public function update(User $actor): bool
    {
        return false;
    }
}
