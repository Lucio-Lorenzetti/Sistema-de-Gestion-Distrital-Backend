<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DesignacionController extends Controller
{
    /**
     * Designación directa (no pasa por solicitud): reemplaza al Jefe de Grupo
     * actual de ESE grupo por el nuevo — nunca dos a la vez en el mismo grupo.
     */
    public function jefeDeGrupo(Request $request, Grupo $grupo)
    {
        Gate::authorize('designarJefeDeGrupo', $grupo);

        $validated = $request->validate(['user_id' => 'required|exists:users,id']);

        $rol = Role::where('nombre', 'Jefe de Grupo')->firstOrFail();
        $nuevoTitular = User::findOrFail($validated['user_id']);
        $actor = Auth::user();

        DB::transaction(function () use ($rol, $grupo, $nuevoTitular, $actor) {
            DB::table('user_roles')
                ->where('role_id', $rol->id)
                ->where('grupo_id', $grupo->id)
                ->delete();

            $nuevoTitular->roles()->syncWithoutDetaching([
                $rol->id => [
                    'grupo_id' => $grupo->id,
                    'asignado_por_id' => $actor->id,
                    'asignado_at' => now(),
                ],
            ]);

            if (!$nuevoTitular->activo) {
                $nuevoTitular->forceFill(['activo' => true])->save();
            }
        });

        ActivityLogger::log('jefe_de_grupo_designado', 'Se designó un nuevo Jefe de Grupo', "{$nuevoTitular->name} — {$grupo->nombre}");

        return response()->json(['message' => 'Jefe de Grupo designado correctamente']);
    }

    /**
     * Mismo patrón, singleton a nivel distrito (sin grupo_id).
     */
    public function director(Request $request)
    {
        Gate::authorize('designarDirector', User::class);

        $validated = $request->validate(['user_id' => 'required|exists:users,id']);

        $rol = Role::where('nombre', 'Director')->firstOrFail();
        $nuevoTitular = User::findOrFail($validated['user_id']);
        $actor = Auth::user();

        DB::transaction(function () use ($rol, $nuevoTitular, $actor) {
            DB::table('user_roles')->where('role_id', $rol->id)->delete();

            $nuevoTitular->roles()->syncWithoutDetaching([
                $rol->id => [
                    'asignado_por_id' => $actor->id,
                    'asignado_at' => now(),
                ],
            ]);

            if (!$nuevoTitular->activo) {
                $nuevoTitular->forceFill(['activo' => true])->save();
            }
        });

        ActivityLogger::log('director_designado', 'Se designó un nuevo Director', $nuevoTitular->name);

        return response()->json(['message' => 'Director designado correctamente']);
    }
}
