<?php

namespace App\Http\Controllers\Api\Programas;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    // Listar notas de un programa
    public function index(Program $program)
    {
        return response()->json($program->notes()->with('user:id,name')->get());
    }

    // Guardar una nota nueva
    public function store(Request $request, Program $program)
    {
        $user = Auth::user();

        // VALIDACIÓN SEGÚN MATRIZ: Solo Directores y Auxiliares
        if (!$user->hasAnyRole(['Director', 'Aux Prog General', 'Aux Prog Rama'])) {
            return response()->json(['message' => 'No tienes permiso para dejar feedback'], 403);
        }

        $validated = $request->validate([
            'contenido' => 'required|string'
        ]);

        $note = $program->notes()->create([
            'contenido' => $validated['contenido'],
            'user_id' => $user->id,
            'resuelta' => false
        ]);

        return response()->json($note, 201);
    }
}