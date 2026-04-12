<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProgramPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Program $program): bool
    {
        // Director y Auxiliar General ven todo
        if ($user->hasRole('Director') || $user->hasRole('Aux Prog General')) return true;

        // Auxiliar de Rama ve solo su rama
        if ($user->hasRole('Aux Prog Rama')) return $user->rama_id === $program->rama_id;

        // Jefe de Grupo ve todo su grupo
        if ($user->hasRole('Jefe Grupo')) return $user->grupo_id === $program->grupo_id;

        // Educador solo ve lo propio
        return $user->id === $program->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Program $program): bool
    {
        // Solo el autor puede editar
        return $user->id === $program->user_id;
    }

    public function delete(User $user, Program $program): bool
    {
        // Solo el autor puede borrar
        return $user->id === $program->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Program $program): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Program $program): bool
    {
        return false;
    }
}
