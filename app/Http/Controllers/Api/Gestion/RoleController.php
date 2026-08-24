<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Todos los roles + su metadata — para pantallas de gestión (protegido).
     */
    public function index()
    {
        return response()->json(Role::orderBy('nombre')->get());
    }

    /**
     * Roles que se pueden pedir desde el registro (público, sin login) —
     * incluye la metadata (requiere_rama/requiere_grupo) para que el
     * formulario sepa qué campos mostrar según el rol elegido.
     */
    public function solicitables()
    {
        return response()->json(
            Role::where('autosolicitable', true)->orderBy('nombre')->get()
        );
    }

    /**
     * Crear un rol nuevo — solo Developer (nadie más por policy).
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Role::class);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:roles,nombre',
            'requiere_rama' => 'boolean',
            'requiere_grupo' => 'boolean',
            'autosolicitable' => 'boolean',
            'reemplazo_unico' => 'nullable|in:grupo,distrito',
            'unico_por_usuario' => 'boolean',
        ]);

        $role = Role::create($validated);

        return response()->json($role, 201);
    }
}
