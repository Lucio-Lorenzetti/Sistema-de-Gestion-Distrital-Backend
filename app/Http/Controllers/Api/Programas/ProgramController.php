<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramController extends Controller
{
    /**
     * Obtener listado de programas accesibles por el usuario.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $programs = Program::with(['rama', 'owner:id,name,email'])
            ->where('grupo_id', $user->grupo_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($programs, 200);
    }

    /**
     * Guardar un nuevo programa.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'       => 'required|string|max:255',
            'diagnostico'  => 'nullable|string',
            'objetivos'    => 'nullable|string',
            'tipo'         => 'nullable|string',
            'rama_id'      => 'required|exists:ramas,id',
            'fechaInicio'  => 'nullable|date',
            'fechaFin'     => 'nullable|date|after_or_equal:fechaInicio',
            'dias'         => 'nullable|array',
            'anexos'       => 'nullable|array',
        ]);

        $user = Auth::user();

        $program = Program::create([
            'titulo'       => $validated['titulo'],
            'diagnostico'  => $validated['diagnostico'] ?? null,
            'objetivos'    => $validated['objetivos'] ?? null,
            'tipo'         => $validated['tipo'] ?? 'cfa',
            'rama_id'      => $validated['rama_id'],
            'fecha_inicio' => $validated['fechaInicio'] ?? null,
            'fecha_fin'    => $validated['fechaFin'] ?? null,
            'cronograma'   => $validated['dias'] ?? [], // Mapea el array 'dias' del front
            'anexos'       => $validated['anexos'] ?? [],
            'owner_id'     => $user->id,
            'grupo_id'     => $user->grupo_id,
            'estado'       => 'borrador',
        ]);

        return response()->json([
            'message' => 'Programa creado exitosamente',
            'data'    => $program->load('rama')
        ], 201);
    }

    /**
     * Mostrar un programa específico.
     */
    public function show($id)
    {
        $program = Program::with(['rama', 'owner:id,name,email', 'grupo'])->findOrFail($id);
        
        return response()->json($program, 200);
    }

    /**
     * Actualizar un programa existente.
     */
    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'titulo'       => 'sometimes|required|string|max:255',
            'diagnostico'  => 'nullable|string',
            'objetivos'    => 'nullable|string',
            'tipo'         => 'nullable|string',
            'rama_id'      => 'sometimes|required|exists:ramas,id',
            'fechaInicio'  => 'nullable|date',
            'fechaFin'     => 'nullable|date|after_or_equal:fechaInicio',
            'dias'         => 'nullable|array',
            'anexos'       => 'nullable|array',
            'estado'       => 'nullable|in:borrador,enviado,aprobado,rechazado',
        ]);

        $updateData = [];

        if (array_key_exists('titulo', $validated))      $updateData['titulo'] = $validated['titulo'];
        if (array_key_exists('diagnostico', $validated)) $updateData['diagnostico'] = $validated['diagnostico'];
        if (array_key_exists('objetivos', $validated))   $updateData['objetivos'] = $validated['objetivos'];
        if (array_key_exists('tipo', $validated))        $updateData['tipo'] = $validated['tipo'];
        if (array_key_exists('rama_id', $validated))     $updateData['rama_id'] = $validated['rama_id'];
        if (array_key_exists('fechaInicio', $validated)) $updateData['fecha_inicio'] = $validated['fechaInicio'];
        if (array_key_exists('fechaFin', $validated))    $updateData['fecha_fin'] = $validated['fechaFin'];
        if (array_key_exists('dias', $validated))        $updateData['cronograma'] = $validated['dias'];
        if (array_key_exists('anexos', $validated))      $updateData['anexos'] = $validated['anexos'];
        if (array_key_exists('estado', $validated))      $updateData['estado'] = $validated['estado'];

        $program->update($updateData);

        return response()->json([
            'message' => 'Programa actualizado exitosamente',
            'data'    => $program->fresh('rama')
        ], 200);
    }

    /**
     * Eliminar un programa.
     */
    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return response()->json([
            'message' => 'Programa eliminado correctamente'
        ], 200);
    }
}