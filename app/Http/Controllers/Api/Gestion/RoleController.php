<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Todos los roles + su metadata — para pantallas de gestión (protegido).
     * Los roles casi no cambian (solo vía store()/update() de este mismo
     * controller), así que se cachean y se invalidan ahí.
     */
    public function index()
    {
        return response()->json(
            Cache::remember('catalogo:roles', 86400, fn () => Role::orderBy('nombre')->get())
        );
    }

    /**
     * Roles que se pueden pedir desde el registro (público, sin login) —
     * incluye la metadata (requiere_rama/requiere_grupo) para que el
     * formulario sepa qué campos mostrar según el rol elegido.
     */
    public function solicitables()
    {
        $roles = Cache::remember('catalogo:roles:solicitables', 86400, function () {
            return Role::where('autosolicitable', true)->orderBy('nombre')->get();
        });

        return response()->json($roles);
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

        ActivityLogger::log('rol_creado', 'Se creó un rol del sistema', $role->nombre);

        $this->invalidarCatalogo();

        return response()->json($role, 201);
    }

    /**
     * Editar la metadata de un rol ya creado — solo Developer. El "nombre" NO
     * se puede tocar acá a propósito: hay comparaciones de string hardcodeadas
     * contra nombres de rol en todo el backend (isDeveloper(), hasRole('Director'),
     * etc.) — renombrar un rol existente las rompería en silencio.
     */
    public function update(Request $request, Role $role)
    {
        Gate::authorize('update', Role::class);

        $validated = $request->validate([
            'requiere_rama' => 'boolean',
            'requiere_grupo' => 'boolean',
            'autosolicitable' => 'boolean',
            'reemplazo_unico' => 'nullable|in:grupo,distrito',
            'unico_por_usuario' => 'boolean',
        ]);

        $role->update($validated);

        ActivityLogger::log('rol_configurado', 'Se editó la configuración de un rol', $role->nombre);

        $this->invalidarCatalogo();

        return response()->json($role);
    }

    private function invalidarCatalogo(): void
    {
        Cache::forget('catalogo:roles');
        Cache::forget('catalogo:roles:solicitables');
    }
}
