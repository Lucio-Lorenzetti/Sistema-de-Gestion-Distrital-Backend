<?php

namespace App\Services;

use App\Models\Grupo;
use App\Models\Rama;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleRequestService
{
    /**
     * Crea una solicitud de rol (de alta self-service, o de un usuario ya activo
     * pidiendo un rol adicional). Valida la metadata del rol: si no es
     * autosolicitable, si requiere rama/grupo, y que no tenga ya una solicitud
     * pendiente de ese mismo rol.
     *
     * Ya tener el rol NO bloquea la solicitud — al contrario, es el camino
     * para "solicitar cambio de rol": un Educador que pasa de una rama a otra,
     * o un Aux Prog Rama que se muda a otra rama, piden el MISMO rol con un
     * scope distinto. Al aprobarla, RoleRequestService::aprobar() hace
     * syncWithoutDetaching(), que actualiza el scope de la asignación
     * existente en vez de crear una segunda (la PK compuesta [user_id,
     * role_id] del pivot ya impide tener dos asignaciones del mismo rol).
     */
    public function crearSolicitud(User $user, Role $role, ?Rama $rama, ?Grupo $grupo): RoleRequest
    {
        if (!$role->autosolicitable) {
            throw ValidationException::withMessages([
                'role_id' => ['Este rol no se puede solicitar — se designa directamente.'],
            ]);
        }

        if ($role->requiere_rama && !$rama) {
            throw ValidationException::withMessages(['rama_id' => ['Este rol requiere elegir una rama.']]);
        }

        if ($role->requiere_grupo && !$grupo) {
            throw ValidationException::withMessages(['grupo_id' => ['Este rol requiere elegir un grupo.']]);
        }

        $tienePendiente = $user->roleRequests()
            ->where('role_id', $role->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($tienePendiente) {
            throw ValidationException::withMessages([
                'role_id' => ['Ya tenés una solicitud pendiente de este rol.'],
            ]);
        }

        return RoleRequest::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'rama_id' => $rama?->id,
            'grupo_id' => $grupo?->id,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Aprobar = activar la cuenta (si no lo estaba ya) + asignar el rol, en un
     * solo paso — para que quien administra no tenga que hacer dos cosas.
     */
    public function aprobar(RoleRequest $solicitud, User $actor): void
    {
        DB::transaction(function () use ($solicitud, $actor) {
            $solicitud->update([
                'estado' => 'aprobada',
                'revisado_por_id' => $actor->id,
                'revisado_at' => now(),
            ]);

            $target = $solicitud->user;

            $target->roles()->syncWithoutDetaching([
                $solicitud->role_id => [
                    'rama_id' => $solicitud->rama_id,
                    'grupo_id' => $solicitud->grupo_id,
                    'asignado_por_id' => $actor->id,
                    'asignado_at' => now(),
                ],
            ]);

            if (!$target->activo) {
                $target->forceFill(['activo' => true])->save();
            }

            if (strtolower($solicitud->role->nombre) === 'educador') {
                UserScopeCache::sync($target->refresh());
            }

            ActivityLogger::log(
                'solicitud_rol_aprobada',
                'Se aprobó una solicitud de rol',
                "{$target->name} → {$solicitud->role->nombre}"
            );
        });
    }

    /**
     * Rechazar con motivo obligatorio (mismo patrón que "Rechazar con motivo" de
     * Programas). Si era una cuenta recién registrada, queda inactiva.
     */
    public function rechazar(RoleRequest $solicitud, User $actor, string $motivo): void
    {
        $solicitud->update([
            'estado' => 'rechazada',
            'motivo_rechazo' => $motivo,
            'revisado_por_id' => $actor->id,
            'revisado_at' => now(),
        ]);

        ActivityLogger::log(
            'solicitud_rol_rechazada',
            'Se rechazó una solicitud de rol',
            "{$solicitud->user->name} → {$solicitud->role->nombre}"
        );
    }
}
