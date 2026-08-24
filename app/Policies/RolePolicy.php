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
}
