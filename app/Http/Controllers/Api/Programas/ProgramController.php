<?php

namespace App\Http\Controllers\Api\Programas;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ProgramController extends Controller
{
    /**
     * Obtener listado de programas accesibles por el usuario, según su rol.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $roleNames = $user->roles->pluck('nombre')
            ->map(fn ($nombre) => strtolower($nombre))
            ->toArray();

        $query = Program::with(['rama', 'grupo', 'owner:id,name,email']);

        if (in_array('director', $roleNames) || in_array('aux prog general', $roleNames)) {
            // Director y Aux Prog General ven TODOS los programas del distrito, sin filtro.
        } elseif (in_array('aux prog rama', $roleNames)) {
            $query->where('rama_id', $user->rama_id);
        } elseif (in_array('jefe de grupo', $roleNames)) {
            $query->where('grupo_id', $user->grupo_id);
        } else {
            // Educador (default): su rama + su grupo → incluye los suyos y los de sus pares.
            $query->where('grupo_id', $user->grupo_id)
                ->where('rama_id', $user->rama_id);
        }

        $programs = $query->orderBy('created_at', 'desc')->get();

        return response()->json($programs, 200);
    }

    /**
     * Guardar un nuevo programa.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'            => 'required|string|max:255',
            'diagnostico'       => 'nullable|string',
            'objetivos'         => 'nullable|string',
            'educadoresACargo'  => 'nullable|string',
            'tipo'              => 'nullable|string',
            'fechaInicio'       => 'nullable|date',
            'fechaFin'          => 'nullable|date|after_or_equal:fechaInicio',
            'dias'              => 'nullable|array',   // CFA: array de días
            'contenidoHtml'     => 'nullable|string',  // Campamento / Cuatrimestre: HTML único
            'anexos'            => 'nullable|array',
        ]);

        $user = Auth::user();

        $program = Program::create([
            'titulo'              => $validated['titulo'],
            'diagnostico'         => $validated['diagnostico'] ?? null,
            'objetivos'           => $validated['objetivos'] ?? null,
            'educadores_a_cargo'  => $validated['educadoresACargo'] ?? null,
            'tipo'                => $validated['tipo'] ?? 'cfa',
            'rama_id'             => $user->rama_id, // sale del usuario, no del payload
            'fecha_inicio'        => $validated['fechaInicio'] ?? null,
            'fecha_fin'           => $validated['fechaFin'] ?? null,
            'cronograma'          => $validated['dias'] ?? (isset($validated['contenidoHtml']) ? ['contenidoHtml' => $validated['contenidoHtml']] : []),
            'anexos'              => $validated['anexos'] ?? [],
            'owner_id'            => $user->id,
            'grupo_id'            => $user->grupo_id,
            'estado'              => 'borrador',
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

    public function pdf($id)
    {
        $program = Program::with(['rama', 'grupo', 'owner:id,name,email'])->findOrFail($id);

        $cronograma = $program->cronograma ?? [];
        $disclaimer = null;

        if (isset($cronograma['contenidoHtml'])) {
            [$cronograma['contenidoHtml'], $disclaimer] = $this->extraerAyudaHtml($cronograma['contenidoHtml']);
            $cronograma['contenidoHtml'] = $this->quitarSeccionesDuplicadas($cronograma['contenidoHtml'], ['Título', 'Educadores a Cargo', 'Diagnóstico', 'Objetivo']);
        } elseif (isset($cronograma['contenido'])) {
            [$cronograma['contenido'], $disclaimer] = $this->extraerAyudaTexto($cronograma['contenido']);
        }

        $pdf = Pdf::loadView('programas.pdf', compact('program', 'cronograma', 'disclaimer'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = \Illuminate\Support\Str::slug($program->titulo) . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * El editor del front antepone un bloque de ayuda en gris (span color #9ca3af) al
     * contenido de Campamento/Cuatrimestre. Lo separamos para mostrarlo como pie de
     * página al final del PDF en vez de mezclado arriba del cronograma.
     */
    private function extraerAyudaHtml(string $html): array
    {
        if (preg_match('/^\s*(?:<div>\s*)?<span[^>]*style="[^"]*color:\s*#9ca3af[^"]*"[^>]*>(.*?)<\/span>(?:\s*<\/div>)?/is', $html, $m)) {
            $ayuda = trim(strip_tags($m[1]));
            $resto = substr($html, strlen($m[0]));

            return [$resto, $ayuda !== '' ? $ayuda : null];
        }

        return [$html, null];
    }

    /**
     * Equivalente a extraerAyudaHtml() para programas creados antes del cambio de
     * contrato (texto plano en cronograma['contenido']), donde el bloque de ayuda es
     * simplemente el primer párrafo del texto.
     */
    private function extraerAyudaTexto(string $texto): array
    {
        $needle = 'Este Template es para unificar criterios mínimos';

        if (!str_starts_with(trim($texto), $needle)) {
            return [$texto, null];
        }

        $partes = preg_split('/\R\s*\R/', $texto, 2);

        return [ltrim($partes[1] ?? '', "\r\n"), trim($partes[0])];
    }

    /**
     * El editor del front repite dentro del cronograma encabezados que ya se muestran
     * como secciones propias del PDF (Título, Diagnóstico, Objetivo). Cada uno viene
     * como un <div><strong>Encabezado</strong></div> aislado seguido de su contenido,
     * así que se puede quitar el bloque completo hasta el próximo encabezado sin tocar
     * texto libre del usuario (p. ej. "Objetivo de la Actividad" en los anexos no matchea
     * porque no está envuelto solo en <strong>).
     */
    private function quitarSeccionesDuplicadas(string $html, array $encabezados): string
    {
        foreach ($encabezados as $encabezado) {
            $patron = '/<div>\s*<strong>\s*' . preg_quote($encabezado, '/') . '\s*<\/strong>\s*<\/div>.*?(?=<div>\s*<strong>|$)/isu';
            $html = preg_replace($patron, '', $html, 1);
        }

        return $html;
    }

    /**
     * Actualizar un programa existente.
     */
    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'titulo'            => 'sometimes|required|string|max:255',
            'diagnostico'       => 'nullable|string',
            'objetivos'         => 'nullable|string',
            'educadoresACargo'  => 'nullable|string',
            'tipo'              => 'nullable|string',
            'fechaInicio'       => 'nullable|date',
            'fechaFin'          => 'nullable|date|after_or_equal:fechaInicio',
            'dias'              => 'nullable|array',
            'contenidoHtml'     => 'nullable|string',
            'anexos'            => 'nullable|array',
            'estado'            => 'nullable|in:borrador,enviado,aprobado,rechazado',
        ]);

        $updateData = [];

        if (array_key_exists('titulo', $validated))           $updateData['titulo'] = $validated['titulo'];
        if (array_key_exists('diagnostico', $validated))       $updateData['diagnostico'] = $validated['diagnostico'];
        if (array_key_exists('objetivos', $validated))         $updateData['objetivos'] = $validated['objetivos'];
        if (array_key_exists('educadoresACargo', $validated))  $updateData['educadores_a_cargo'] = $validated['educadoresACargo'];
        if (array_key_exists('tipo', $validated))               $updateData['tipo'] = $validated['tipo'];
        if (array_key_exists('fechaInicio', $validated))        $updateData['fecha_inicio'] = $validated['fechaInicio'];
        if (array_key_exists('fechaFin', $validated))           $updateData['fecha_fin'] = $validated['fechaFin'];
        if (array_key_exists('dias', $validated))               $updateData['cronograma'] = $validated['dias'];
        elseif (array_key_exists('contenidoHtml', $validated))  $updateData['cronograma'] = ['contenidoHtml' => $validated['contenidoHtml']];
        if (array_key_exists('anexos', $validated))             $updateData['anexos'] = $validated['anexos'];
        if (array_key_exists('estado', $validated))             $updateData['estado'] = $validated['estado'];

        $program->update($updateData);

        return response()->json([
            'message' => 'Programa actualizado exitosamente',
            'data'    => $program->fresh(['rama', 'grupo'])
        ], 200);
    }

    /**
     * Cambiar el estado de un programa (flujo de aprobación).
     */
    public function updateStatus(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,enviado,aprobado,rechazado',
        ]);

        $program->update(['estado' => $validated['estado']]);

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data'    => $program->fresh(['rama', 'grupo']),
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