<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    /**
     * ¿Puede el usuario ver el listado de programas?
     * El filtro real (qué programas ve cada uno) lo hace el controller
     * (matriz de roles) + el scope Program::visiblePara() para Educador.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Educador', 'Director', 'Aux Prog General', 'Aux Prog Rama', 'Jefe de Grupo',
        ]);
    }

    /**
     * ¿Puede ver ESTE programa puntual?
     * Director/Aux Prog General: cualquiera.
     * Aux Prog Rama: los de su rama.
     * Jefe de Grupo: los de su grupo.
     * Educador: autor, o mismo grupo + misma rama.
     */
    public function view(User $user, Program $program): bool
    {
        if ($user->hasAnyRole(['Director', 'Aux Prog General'])) {
            return true;
        }

        if ($user->hasRole('Aux Prog Rama')) {
            return $program->rama_id === $user->rama_id;
        }

        if ($user->hasRole('Jefe de Grupo')) {
            return $program->grupo_id === $user->grupo_id;
        }

        return $program->owner_id === $user->id
            || ($program->grupo_id === $user->grupo_id && $program->rama_id === $user->rama_id);
    }

    /**
     * ¿Puede crear/subir un programa nuevo?
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Educador');
    }

    /**
     * ¿Puede editar este programa?
     * - El autor siempre puede editar.
     * - Mientras esté en 'borrador', cualquier educador del mismo grupo+rama
     *   también puede editar (armado colaborativo antes de publicar).
     * - Una vez 'publicado', solo el autor.
     */
    public function update(User $user, Program $program): bool
    {
        if ($program->owner_id === $user->id) {
            return true;
        }

        if ($program->estado === 'borrador') {
            return $program->grupo_id === $user->grupo_id
                && $program->rama_id === $user->rama_id;
        }

        return false;
    }

    /**
     * ¿Puede borrar este programa?
     */
    public function delete(User $user, Program $program): bool
    {
        return $program->owner_id === $user->id;
    }
}