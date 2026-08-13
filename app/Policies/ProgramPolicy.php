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
     * Educador: autor, o mismo grupo + misma rama; y mientras está 'enviado',
     * alcanza con compartir la rama (sin exigir grupo) para poder entrar a
     * comentar vía comment(), que es a propósito más amplia que esta regla base.
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

        if ($program->owner_id === $user->id
            || ($program->grupo_id === $user->grupo_id && $program->rama_id === $user->rama_id)) {
            return true;
        }

        // Mientras está en revisión, cualquier educador de la rama puede entrar
        // a verlo y comentarlo, no solo los de su propio grupo.
        return $program->estado === 'enviado' && $program->rama_id === $user->rama_id;
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
     * - Mientras esté 'enviado', nadie edita (ni el autor): el contenido queda
     *   congelado en revisión para que los anclajes de línea de los comentarios
     *   sigan siendo válidos. El autor debe usar "Volver a Borrador" primero.
     * - El autor siempre puede editar (fuera de 'enviado').
     * - Mientras esté en 'borrador', cualquier educador del mismo grupo+rama
     *   también puede editar (armado colaborativo antes de publicar).
     * - Una vez 'publicado'/'aprobado'/'rechazado' (fuera de 'borrador'), solo el autor.
     */
    public function update(User $user, Program $program): bool
    {
        if ($program->estado === 'enviado') {
            return false;
        }

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
     * ¿Puede comentar (crear/responder/resolver hilos) en este programa?
     * Solo mientras estado === 'enviado' (fuera de eso, los hilos quedan
     * congelados en solo lectura para todos). Misma técnica de cascada por
     * roleNames que ProgramController::index(): un usuario sin roles cargados
     * cuenta como Educador por defecto.
     * - Aux Prog General: cualquier programa.
     * - Aux Prog Rama: solo los de su rama.
     * - Director / Jefe de Grupo: solo lectura, nunca comentan.
     * - Educador (default): su rama, sin importar el grupo (más amplio que update()).
     */
    public function comment(User $user, Program $program): bool
    {
        if ($program->estado !== 'enviado') {
            return false;
        }

        $roleNames = $user->roles->pluck('nombre')
            ->map(fn ($nombre) => strtolower($nombre))
            ->toArray();

        if (in_array('aux prog general', $roleNames)) {
            return true;
        }

        if (in_array('aux prog rama', $roleNames)) {
            return $program->rama_id === $user->rama_id;
        }

        if (in_array('director', $roleNames) || in_array('jefe de grupo', $roleNames)) {
            return false;
        }

        // Educador (default): sin rol cargado en user_roles también cuenta como educador.
        return $program->rama_id === $user->rama_id;
    }

    /**
     * ¿Puede borrar este programa?
     */
    public function delete(User $user, Program $program): bool
    {
        return $program->owner_id === $user->id;
    }

    /**
     * El autor de un programa, y cualquier educador de la misma rama+grupo, tienen
     * exactamente las mismas capacidades sobre él — es trabajo colaborativo del
     * grupo, no algo personal de quien lo creó. A diferencia de update(), esto NO
     * depende del estado: se usa para acciones que justamente ocurren fuera de
     * 'borrador' (volver a borrador, solicitar aprobación).
     */
    private function esColaborador(User $user, Program $program): bool
    {
        return $program->owner_id === $user->id
            || ($program->grupo_id === $user->grupo_id && $program->rama_id === $user->rama_id);
    }

    /**
     * ¿Puede pasar el programa de $program->estado a $nuevoEstado?
     * Cada transición tiene su propio dueño:
     * - borrador → enviado ("Enviar a revisión"): mismo criterio que update()
     *   (autor, o cualquier educador del mismo grupo+rama mientras está en borrador).
     * - enviado → borrador ("Volver a borrador"): autor, o cualquier educador del
     *   mismo grupo+rama.
     * - enviado → aprobado/rechazado ("Aprobar"/"Rechazar"): Aux Prog General
     *   (cualquiera) o Aux Prog Rama (solo los de su rama) — mismo alcance que comment().
     * Cualquier otra combinación (saltos de estado, mismo estado, etc.) queda afuera.
     */
    public function updateStatus(User $user, Program $program, string $nuevoEstado): bool
    {
        if ($program->estado === 'borrador' && $nuevoEstado === 'enviado') {
            return $this->update($user, $program);
        }

        if ($program->estado === 'enviado' && $nuevoEstado === 'borrador') {
            return $this->esColaborador($user, $program);
        }

        if ($program->estado === 'enviado' && in_array($nuevoEstado, ['aprobado', 'rechazado'], true)) {
            if ($user->hasRole('Aux Prog General')) {
                return true;
            }

            if ($user->hasRole('Aux Prog Rama')) {
                return $program->rama_id === $user->rama_id;
            }

            return false;
        }

        return false;
    }

    /**
     * ¿Puede marcar el programa como "listo para aprobar"? Autor, o cualquier
     * educador del mismo grupo+rama, y solo mientras está 'enviado' (si no está
     * en revisión no hay nada que pedir; una vez aprobado/rechazado/vuelto a
     * borrador hay que volver a enviarlo primero).
     */
    public function solicitarAprobacion(User $user, Program $program): bool
    {
        return $program->estado === 'enviado' && $this->esColaborador($user, $program);
    }
}