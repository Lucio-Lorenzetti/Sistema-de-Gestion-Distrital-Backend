<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Director/Developer: todos los usuarios. Jefe de Grupo: solo los de SU
     * grupo (scope real vía roleScope, no user->grupo_id).
     */
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        $actor = Auth::user();
        $query = User::with('roles')->orderBy('name');

        if (!$actor->hasRole('Director')) {
            $query->where('grupo_id', $actor->roleScope('Jefe de Grupo')?->grupo_id);
        }

        return response()->json($this->ocultarDeveloper($query->get(), $actor));
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $ocultos = $this->ocultarDeveloper(collect([$user->load('roles')]), Auth::user());

        // Si el único usuario de la colección se filtró (era Developer y el
        // actor no lo es), no hay nada legítimo que devolver.
        abort_if($ocultos->isEmpty(), 404);

        return response()->json($ocultos->first());
    }

    /**
     * Developer es omnipresente/omnipotente y nadie más puede ver rastro de
     * ese rol — ni Director ni Jefe de Grupo. Se le saca el rol "Developer"
     * de la lista de roles de cada usuario; si a alguien no le queda NINGÚN
     * rol después de sacárselo (o sea, Developer era su único rol), se lo
     * saca de la respuesta directamente. El propio Developer ve todo intacto.
     */
    private function ocultarDeveloper($usuarios, User $actor)
    {
        if ($actor->isDeveloper()) {
            return $usuarios;
        }

        return $usuarios
            ->reject(function (User $usuario) {
                $roles = $usuario->roles->pluck('nombre')->map('strtolower');
                // Developer era su ÚNICO rol: no queda nada legítimo que mostrar.
                return $roles->contains('developer') && $roles->count() === 1;
            })
            ->each(function (User $usuario) {
                $usuario->setRelation(
                    'roles',
                    $usuario->roles->reject(fn ($r) => strtolower($r->nombre) === 'developer')->values()
                );
            })
            ->values();
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    /**
     * Papelera de usuarios eliminados — solo Developer (mismo criterio que
     * delete()/restore()). A diferencia de la papelera de Programas, no está
     * acotada por "quién lo borró": solo Developer puede borrar/restaurar
     * usuarios, así que ya ve todo lo que hay.
     */
    public function papelera()
    {
        Gate::authorize('viewPapelera', User::class);

        $usuarios = User::onlyTrashed()->with('roles')->orderBy('deleted_at', 'desc')->get();

        return response()->json($usuarios);
    }

    /**
     * Restaurar un usuario eliminado.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $user);

        $user->restore();

        ActivityLogger::log('usuario_restaurado', 'Se restauró un usuario eliminado', $user->name);

        return response()->json(['message' => 'Usuario restaurado correctamente']);
    }

    /**
     * Asignar cualquier rol+scope directo a un usuario, sin pasar por
     * solicitud/designación — solo Developer. NO sobre uno mismo: ni siquiera
     * Developer se salta el flujo normal para asignarse un rol a sí mismo
     * (Gate::before lo bypassea todo, así que este chequeo tiene que vivir acá
     * y no en la Policy, que nunca llega a ejecutarse para Developer).
     */
    public function assignRole(Request $request, User $user)
    {
        Gate::authorize('assignRoleFreely', User::class);

        abort_if($user->id === Auth::id(), 403, 'No podés asignarte un rol a vos mismo — pedilo como cualquier usuario, desde Mi Perfil.');

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'rama_id' => 'nullable|exists:ramas,id',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);

        $user->roles()->syncWithoutDetaching([
            $validated['role_id'] => [
                'rama_id' => $validated['rama_id'] ?? null,
                'grupo_id' => $validated['grupo_id'] ?? null,
                'asignado_por_id' => Auth::id(),
                'asignado_at' => now(),
            ],
        ]);

        if (!$user->activo) {
            $user->forceFill(['activo' => true])->save();
        }

        $role = Role::find($validated['role_id']);
        ActivityLogger::log('rol_asignado_developer', 'Developer asignó un rol directo', "{$user->name} → {$role->nombre}");

        return response()->json($user->load('roles'));
    }

    /**
     * Ni Developer puede sacarse un rol a sí mismo por acá — en particular,
     * esto es lo único que impide que Developer se saque su propio rol
     * Developer. Sí puede sacarle Developer (o cualquier rol) a OTRO usuario.
     */
    public function revokeRole(User $user, Role $role)
    {
        Gate::authorize('assignRoleFreely', User::class);

        abort_if($user->id === Auth::id(), 403, 'No podés sacarte un rol a vos mismo.');

        $user->roles()->detach($role->id);

        ActivityLogger::log('rol_quitado_developer', 'Developer quitó un rol directo', "{$user->name} → {$role->nombre}");

        return response()->json($user->load('roles'));
    }
}
