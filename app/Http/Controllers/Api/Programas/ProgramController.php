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
    public function index()
    {
        $user = Auth::user();
        // Por ahora, traemos todos los del grupo del usuario logueado
        return Program::where('grupo_id', $user->grupo_id)
                      ->with('user:id,name') // Para saber quién lo hizo
                      ->get();
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
            'estado' => 'publicado'
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