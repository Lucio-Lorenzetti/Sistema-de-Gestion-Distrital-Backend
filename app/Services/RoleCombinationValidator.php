<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Regla de negocio sobre qué roles puede combinar una misma persona (no había
 * ninguna validación de esto antes — el pivot [user_id, role_id] solo impide
 * tener el MISMO rol dos veces, pero no impedía, por ejemplo, ser Educador de
 * un grupo y Jefe de Grupo de otro al mismo tiempo).
 *
 * Regla acordada:
 * - Los roles "de distrito" (no requieren grupo — Director, Aux Prog General,
 *   Aux Prog Rama, Aux Comunicación) se combinan libremente entre sí y con,
 *   como mucho, un solo grupo.
 * - Los roles "dentro de un grupo" (requieren grupo — Jefe de Grupo, Educador)
 *   no pueden corresponder a más de un grupo distinto en la misma persona.
 * - Dentro de un mismo grupo, la única combinación posible de roles de grupo
 *   es Jefe de Grupo + Educador (nunca dos Educadores de ramas distintas del
 *   mismo grupo bajo un solo usuario, ni dos Jefes de Grupo, etc.).
 * - Developer queda afuera de esta regla por completo: puede tener cualquier
 *   combinación, asignada directa, sin pasar por solicitud.
 */
class RoleCombinationValidator
{
    public static function validar(User $user, Role $roleNuevo, ?int $grupoId): void
    {
        if ($user->isDeveloper() || strtolower($roleNuevo->nombre) === 'developer') {
            return;
        }

        if (!$roleNuevo->requiere_grupo) {
            return;
        }

        $rolesDeGrupoActuales = $user->roles()->get()
            ->filter(fn (Role $r) => $r->id !== $roleNuevo->id && $r->requiere_grupo);

        foreach ($rolesDeGrupoActuales as $existente) {
            $grupoExistente = $existente->pivot->grupo_id;

            if ($grupoExistente !== null && $grupoId !== null && $grupoExistente !== $grupoId) {
                throw ValidationException::withMessages([
                    'grupo_id' => ["No se puede tener roles en más de un grupo distinto — ya tiene \"{$existente->nombre}\" asignado en otro grupo."],
                ]);
            }

            $combo = collect([strtolower($existente->nombre), strtolower($roleNuevo->nombre)])->sort()->values()->all();

            if ($combo !== ['educador', 'jefe de grupo']) {
                throw ValidationException::withMessages([
                    'role_id' => ["Dentro de un mismo grupo, solo se puede combinar Jefe de Grupo con Educador — ya tiene \"{$existente->nombre}\" en ese grupo."],
                ]);
            }
        }
    }
}
