<?php

namespace App\Http\Controllers\Api\Programas;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProgramController extends Controller
{
    use AuthorizesRequests;

    /**
     * Listado de programas, según la matriz de roles:
     * - Director / Aux Prog General -> todos los programas del distrito.
     * - Aux Prog Rama -> los de su rama.
     * - Jefe de Grupo -> los de su grupo.
     * - Educador -> los suyos + los de su mismo grupo+rama (Program::visiblePara()).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Program::class);

        $user = $request->user();
        $query = Program::query()->with(['owner:id,name', 'rama:id,nombre', 'grupo:id,nombre']);

        if ($user->hasAnyRole(['Director', 'Aux Prog General'])) {
            return response()->json($query->latest()->get());
        }

        if ($user->hasRole('Aux Prog Rama')) {
            return response()->json($query->where('rama_id', $user->rama_id)->latest()->get());
        }

        if ($user->hasRole('Jefe de Grupo')) {
            return response()->json($query->where('grupo_id', $user->grupo_id)->latest()->get());
        }

        // Educador: dueño de sus programas + programas de su mismo grupo+rama
        return response()->json(
            $query->visiblePara($user)->latest()->get()
        );
    }

    public function show(Program $program)
    {
        $this->authorize('view', $program);

        return response()->json(
            $program->load(['owner:id,name', 'rama:id,nombre', 'grupo:id,nombre'])
        );
    }

    /**
     * Cambiar estado (Borrador -> Revisión -> Publicado / Rechazado).
     * Reutiliza la policy 'update', que ya contempla el caso colaborativo en borrador.
     */
    public function updateStatus(Request $request, Program $program)
    {
        $this->authorize('update', $program);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,revision,publicado,rechazado',
        ]);

        $program->update(['estado' => $validated['estado']]);

        return response()->json(['message' => 'Estado actualizado', 'program' => $program]);
    }

    /**
     * CREAR — validación mínima para que ande sin romper. Las reglas finas de
     * negocio (campos obligatorios, adjuntos, etc.) se terminan de definir en
     * la Fase 7 (US2), esto es solo para no dejar el endpoint roto mientras tanto.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Program::class);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'diagnostico' => 'nullable|string',
            'objetivos' => 'nullable|string',
            'rama_id' => 'required|exists:ramas,id',
        ]);

        $program = Program::create([
            'titulo' => $validated['titulo'],
            'diagnostico' => $validated['diagnostico'] ?? null,
            'objetivos' => $validated['objetivos'] ?? null,
            'rama_id' => $validated['rama_id'],
            'owner_id' => Auth::id(),
            'grupo_id' => Auth::user()->grupo_id,
            'estado' => 'borrador',
        ]);

        return response()->json([
            'message' => 'Programa creado con éxito',
            'data' => $program,
        ], 201);
    }

    /**
     * EDITAR — igual que store(), validación mínima por ahora.
     */
    public function update(Request $request, Program $program)
    {
        $this->authorize('update', $program);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'diagnostico' => 'nullable|string',
            'objetivos' => 'nullable|string',
            'cronograma' => 'nullable|array',
            'anexos' => 'nullable|array',
        ]);

        $program->update($validated);

        return response()->json(['message' => 'Programa actualizado', 'data' => $program]);
    }

    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);

        $program->delete();

        return response()->json(['message' => 'Programa eliminado correctamente']);
    }
}