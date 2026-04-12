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

    // LISTAR: Cada uno ve los programas de su grupo (o todos, según prefieras)
    public function index(Request $request) // <--- Agregado el Request
    {
        $user = $request->user();
        $query = Program::query()->with(['user:id,name', 'rama:id,nombre', 'grupo:id,nombre']);

        // --- FILTRADO SEGÚN MATRIZ DE ROLES ---
        if ($user->hasRole('Director') || $user->hasRole('Aux Prog General')) {
            return response()->json($query->get());
        }

        if ($user->hasRole('Aux Prog Rama')) {
            return response()->json($query->where('rama_id', $user->rama_id)->get());
        }

        if ($user->hasRole('Jefe Grupo')) {
            return response()->json($query->where('grupo_id', $user->grupo_id)->get());
        }

        // Educador: Solo ve sus programas
        return response()->json($query->where('user_id', $user->id)->get());
    }

    // Nuevo método: Cambiar estado (Borrador -> Revisión -> Publicado)
    public function updateStatus(Request $request, Program $program)
    {
        // Solo el autor o un Director puede cambiar el estado
        $this->authorize('update', $program);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,revision,publicado,rechazado'
        ]);

        $program->update(['estado' => $validated['estado']]);

        return response()->json(['message' => 'Estado actualizado', 'program' => $program]);
    }

    // CREAR
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'rama_id' => 'required|exists:ramas,id',
        ]);

        $program = Program::create([
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'rama_id' => $validated['rama_id'],
            'user_id' => Auth::id(),
            'grupo_id' => Auth::user()->grupo_id,
            'estado' => 'borrador'
        ]);

        return response()->json([
            'message' => 'Programa creado con éxito',
            'data' => $program
        ], 201);
    }

    // ELIMINAR (Con el Escudo de Autor)
    public function destroy(Program $program)
    {
        // Laravel busca automáticamente la Policy 'delete' para este modelo
        $this->authorize('delete', $program);

        $program->delete();

        return response()->json(['message' => 'Programa eliminado correctamente']);
    }
}