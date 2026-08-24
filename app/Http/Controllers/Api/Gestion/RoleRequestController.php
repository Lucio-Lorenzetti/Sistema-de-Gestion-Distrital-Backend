<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Services\RoleRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RoleRequestController extends Controller
{
    /**
     * Director/Developer: todas las solicitudes pendientes. Jefe de Grupo:
     * solo las de Educador para SU grupo (mismo scope que UserController::index).
     */
    public function index()
    {
        Gate::authorize('viewAny', RoleRequest::class);

        $actor = Auth::user();
        $query = RoleRequest::with(['user', 'role', 'rama', 'grupo'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at');

        if (!$actor->hasRole('Director')) {
            $query->whereHas('role', fn ($q) => $q->whereRaw('LOWER(nombre) = ?', ['educador']))
                ->where('grupo_id', $actor->roleScope('Jefe de Grupo')?->grupo_id);
        }

        return response()->json($query->get());
    }

    /**
     * Un usuario ya activo pide un rol adicional.
     */
    public function store(Request $request, RoleRequestService $service)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'rama_id' => 'nullable|exists:ramas,id',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $rama = isset($validated['rama_id']) ? Rama::find($validated['rama_id']) : null;
        $grupo = isset($validated['grupo_id']) ? Grupo::find($validated['grupo_id']) : null;

        $solicitud = $service->crearSolicitud(Auth::user(), $role, $rama, $grupo);

        return response()->json($solicitud, 201);
    }

    public function approve(RoleRequest $roleRequest, RoleRequestService $service)
    {
        Gate::authorize('approve', $roleRequest);

        $service->aprobar($roleRequest, Auth::user());

        return response()->json(['message' => 'Solicitud aprobada correctamente']);
    }

    public function reject(Request $request, RoleRequest $roleRequest, RoleRequestService $service)
    {
        Gate::authorize('reject', $roleRequest);

        $validated = $request->validate([
            'motivo' => 'required|string|max:2000',
        ]);

        $service->rechazar($roleRequest, Auth::user(), $validated['motivo']);

        return response()->json(['message' => 'Solicitud rechazada correctamente']);
    }
}
