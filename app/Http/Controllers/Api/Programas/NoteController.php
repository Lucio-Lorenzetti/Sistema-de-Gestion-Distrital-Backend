<?php

namespace App\Http\Controllers\Api\Programas;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    /**
     * Listar los hilos (raíces) de un programa, con sus respuestas.
     */
    public function index(Program $program)
    {
        Gate::authorize('view', $program);

        return response()->json(
            $program->notes()
                ->with(['user:id,name,totem', 'replies.user:id,name,totem'])
                ->get()
        );
    }

    /**
     * Crear un hilo nuevo (con line_ref) o responder uno existente (con parent_id).
     */
    public function store(Request $request, Program $program)
    {
        Gate::authorize('comment', $program);

        $validated = $request->validate([
            'contenido' => 'required|string',
            'parent_id' => 'nullable|integer',
            'line_ref'  => 'nullable|string|max:190',
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId !== null) {
            $padreEsRaizDelPrograma = ProgramNote::where('id', $parentId)
                ->where('program_id', $program->id)
                ->whereNull('parent_id')
                ->exists();

            if (!$padreEsRaizDelPrograma) {
                return response()->json([
                    'message' => 'El comentario padre no existe o no es la raíz de un hilo de este programa',
                ], 422);
            }
        } elseif (empty($validated['line_ref'])) {
            return response()->json([
                'message' => 'line_ref es obligatorio para crear un hilo nuevo',
            ], 422);
        }

        $note = ProgramNote::create([
            'program_id' => $program->id,
            'user_id'    => Auth::id(),
            'parent_id'  => $parentId,
            'line_ref'   => $parentId === null ? $validated['line_ref'] : null,
            'contenido'  => $validated['contenido'],
            'resuelta'   => false,
        ]);

        return response()->json($note->load('user:id,name,totem'), 201);
    }

    /**
     * Marcar/desmarcar como resuelto un hilo. Solo aplica sobre la raíz.
     */
    public function toggleResolucion(Request $request, Program $program, ProgramNote $note)
    {
        Gate::authorize('comment', $program);

        if ($note->program_id !== $program->id) {
            abort(404);
        }

        if ($note->parent_id !== null) {
            return response()->json([
                'message' => 'Solo se puede resolver la raíz de un hilo',
            ], 422);
        }

        $validated = $request->validate([
            'resuelta' => 'required|boolean',
        ]);

        $note->update(['resuelta' => $validated['resuelta']]);

        return response()->json($note->load('user:id,name,totem'));
    }

    /**
     * Hilos raíz sin resolver, de programas 'enviado', visibles para el usuario
     * logueado según la misma matriz de roles que comment() (sin el filtro de
     * estado porque ya lo aplica esta query). Director y Jefe de Grupo no
     * comentan, así que no tienen nada que resolver.
     */
    public function pendientes(Request $request)
    {
        $user = Auth::user();
        $roleNames = $user->roles->pluck('nombre')
            ->map(fn ($nombre) => strtolower($nombre))
            ->toArray();

        if (in_array('director', $roleNames) || in_array('jefe de grupo', $roleNames)) {
            return response()->json([]);
        }

        $query = ProgramNote::whereNull('parent_id')
            ->where('resuelta', false)
            ->whereHas('program', fn ($q) => $q->where('estado', 'enviado'));

        if (!in_array('aux prog general', $roleNames)) {
            // Aux Prog Rama: scope real de esa asignación (no user->rama_id, que es
            // el caché de Educador). Educador (default): sí usa el caché.
            $ramaId = in_array('aux prog rama', $roleNames)
                ? $user->roleScope('Aux Prog Rama')?->rama_id
                : $user->rama_id;
            $query->whereHas('program', fn ($q) => $q->where('rama_id', $ramaId));
        }

        $notas = $query->with([
                'user:id,name,totem',
                'replies.user:id,name,totem',
                'program:id,titulo,rama_id,grupo_id',
                'program.rama:id,nombre',
                'program.grupo:id,nombre',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($notas);
    }
}
