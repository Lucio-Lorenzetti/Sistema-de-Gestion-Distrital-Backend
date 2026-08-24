<?php

namespace App\Http\Controllers\Api\Programas;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;

class ProgramController extends Controller
{
    /**
     * Obtener listado de programas accesibles por el usuario, según su rol.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Program::class);

        $user = Auth::user();

        $roleNames = $user->roles->pluck('nombre')
            ->map(fn ($nombre) => strtolower($nombre))
            ->toArray();

        $query = Program::with(['rama', 'grupo', 'owner:id,name,email,totem']);

        if (in_array('director', $roleNames) || in_array('aux prog general', $roleNames)) {
            // Director y Aux Prog General ven TODOS los programas del distrito, sin filtro.
        } elseif (in_array('aux prog rama', $roleNames)) {
            // Scope real de la asignación de Aux Prog Rama (no user->rama_id, que
            // es el caché de Educador y puede no coincidir si la persona tiene ambos).
            $query->where('rama_id', $user->roleScope('Aux Prog Rama')?->rama_id);
        } elseif (in_array('jefe de grupo', $roleNames)) {
            $query->where('grupo_id', $user->roleScope('Jefe de Grupo')?->grupo_id);
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
        Gate::authorize('create', Program::class);

        $validated = $request->validate([
            'titulo'            => 'required|string|max:255',
            // El frontend (CrearPrograma.jsx) ya los pide required en el form; alineado
            // acá para que un POST directo a la API no pueda crear un programa incompleto.
            'diagnostico'       => 'required|string',
            'objetivos'         => 'required|string',
            'educadoresACargo'  => 'required|string',
            'tipo'              => 'nullable|string|in:cuatrimestre,campamento,cfa',
            'fechaInicio'       => 'required|date',
            'fechaFin'          => 'required|date|after_or_equal:fechaInicio',
            'dias'              => 'nullable|array',   // CFA: array de días
            'contenidoHtml'     => 'nullable|string',  // Campamento / Cuatrimestre: HTML único
            'anexos'            => 'nullable|array',
            'lugar'             => 'nullable|string|max:255',   // Solo Campamento/CFA
            'valor'             => 'nullable|string|max:255',
            'transporte'        => 'nullable|string|max:255',
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
            'lugar'               => $validated['lugar'] ?? null,
            'valor'               => $validated['valor'] ?? null,
            'transporte'          => $validated['transporte'] ?? null,
            'owner_id'            => $user->id,
            'grupo_id'            => $user->grupo_id,
            'estado'              => 'borrador',
        ]);

        ActivityLogger::log('programa_creado', 'Se subió un nuevo programa', $program->titulo);

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
        $program = Program::with(['rama', 'owner:id,name,email,totem', 'grupo'])->findOrFail($id);

        Gate::authorize('view', $program);

        return response()->json($program, 200);
    }

    public function pdf($id)
    {
        $program = Program::with(['rama', 'grupo', 'owner:id,name,email,totem'])->findOrFail($id);

        $cronograma = $program->cronograma ?? [];
        $disclaimer = null;

        $encabezadosDuplicados = ['Título', 'Educadores a Cargo', 'Diagnóstico', 'Objetivo'];

        if (isset($cronograma['contenidoHtml'])) {
            [$cronograma['contenidoHtml'], $disclaimer] = $this->extraerAyudaHtml($cronograma['contenidoHtml']);
            $cronograma['contenidoHtml'] = $this->quitarSeccionesDuplicadas($cronograma['contenidoHtml'], $encabezadosDuplicados);
            $cronograma['contenidoHtml'] = $this->sanearHtml($cronograma['contenidoHtml']);
        } elseif (isset($cronograma['contenido'])) {
            [$cronograma['contenido'], $disclaimer] = $this->extraerAyudaTexto($cronograma['contenido']);
        } elseif (is_array($cronograma)) {
            // CFA: cada día trae su propio bloque de ayuda gris y repite los mismos
            // encabezados (Título/Educadores a Cargo/Diagnóstico/Objetivo). Se limpia
            // cada día y el disclaimer se muestra una sola vez al final del PDF.
            foreach ($cronograma as $i => $dia) {
                if (!is_array($dia) || !isset($dia['contenidoHtml'])) {
                    continue;
                }

                [$html, $ayudaDia] = $this->extraerAyudaHtmlDia($dia['contenidoHtml']);
                $html = $this->quitarSeccionesDuplicadas($html, $encabezadosDuplicados);
                $cronograma[$i]['contenidoHtml'] = $this->sanearHtml($html);
                $disclaimer ??= $ayudaDia;
            }
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
     * Equivalente a extraerAyudaHtml() para el cronograma de CFA (array de días).
     * Cada día empieza con un título gris duplicado ("Programa CFA — Día N (fecha)")
     * que se deja intacto en el cuerpo, y sólo el día 1 tiene, justo después, el
     * párrafo de ayuda real (sin <strong>). Se avanza sobre el título sin tocarlo
     * y se extrae únicamente ese párrafo si está presente.
     */
    private function extraerAyudaHtmlDia(string $html): array
    {
        $offset = 0;

        if (preg_match('/^\s*<div>\s*<span[^>]*style="[^"]*color:\s*#9ca3af[^"]*"[^>]*>\s*<strong>.*?<\/strong>\s*<\/span>\s*<\/div>\s*(?:<div>\s*<br\s*\/?>\s*<\/div>)?/is', $html, $m)) {
            $offset = strlen($m[0]);
        }

        $resto = substr($html, $offset);

        if (preg_match('/^\s*<div>\s*<span[^>]*style="[^"]*color:\s*#9ca3af[^"]*"[^>]*>(.*?)<\/span>\s*<\/div>\s*(?:<div>\s*<br\s*\/?>\s*<\/div>)?/is', $resto, $m2)) {
            $ayuda = trim(strip_tags($m2[1]));
            $restoLimpio = substr($resto, strlen($m2[0]));

            return [substr($html, 0, $offset) . $restoLimpio, $ayuda !== '' ? $ayuda : null];
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
     * El HTML de contenidoHtml lo escribe el educador vía contentEditable en el
     * front. Se muestra en el navegador ya saneado (programaLineas.js), pero el
     * PDF lo inyectaba tal cual sin ese paso — mismo criterio acá: sacar <script>,
     * handlers inline (on*) y href/src con "javascript:".
     */
    private function sanearHtml(?string $html): string
    {
        $html = $html ?? '';
        if (trim($html) === '') {
            return $html;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        foreach (iterator_to_array($dom->getElementsByTagName('script')) as $script) {
            $script->parentNode?->removeChild($script);
        }

        $xpath = new \DOMXPath($dom);
        foreach (iterator_to_array($xpath->query('//*')) as $el) {
            if (!$el instanceof \DOMElement) {
                continue;
            }

            foreach (iterator_to_array($el->attributes ?? []) as $attr) {
                $nombre = strtolower($attr->name);
                $valor = strtolower(trim($attr->value));
                $esHandler = str_starts_with($nombre, 'on');
                $esUrlPeligrosa = in_array($nombre, ['href', 'src'], true) && str_starts_with($valor, 'javascript:');

                if ($esHandler || $esUrlPeligrosa) {
                    $el->removeAttribute($attr->name);
                }
            }
        }

        $resultado = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $resultado .= $dom->saveHTML($child);
        }

        return $resultado;
    }

    /**
     * Actualizar un programa existente.
     */
    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        // El autor siempre puede editar; mientras el programa esté en 'borrador',
        // cualquier educador de la misma rama+grupo también (armado colaborativo).
        // Auxiliares, Jefe de Grupo y Director no comparten simultáneamente rama_id
        // y grupo_id con ningún programa, así que quedan afuera de esta vía.
        Gate::authorize('update', $program);

        $validated = $request->validate([
            'titulo'            => 'sometimes|required|string|max:255',
            // "sometimes" porque un PUT puede mandar solo un subconjunto de campos;
            // pero si vienen, no pueden venir vacíos (mismo criterio que store()).
            'diagnostico'       => 'sometimes|required|string',
            'objetivos'         => 'sometimes|required|string',
            'educadoresACargo'  => 'sometimes|required|string',
            'tipo'              => 'nullable|string|in:cuatrimestre,campamento,cfa',
            'fechaInicio'       => 'sometimes|required|date',
            'fechaFin'          => 'sometimes|required|date|after_or_equal:fechaInicio',
            'dias'              => 'nullable|array',
            'contenidoHtml'     => 'nullable|string',
            'anexos'            => 'nullable|array',
            'lugar'             => 'nullable|string|max:255',
            'valor'             => 'nullable|string|max:255',
            'transporte'        => 'nullable|string|max:255',
            // "estado" NO se acepta acá a propósito: cambiar de estado tiene su propia
            // autorización por transición (Gate::authorize('updateStatus'/'solicitarAprobacion')),
            // que es más estricta que la de "editar contenido" de este endpoint. Si se
            // aceptara aquí, cualquiera con permiso de edición podría autoaprobarse
            // saltando el flujo de revisión. Usar PATCH /estado o /solicitar-aprobacion.
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
        if (array_key_exists('lugar', $validated))              $updateData['lugar'] = $validated['lugar'];
        if (array_key_exists('valor', $validated))              $updateData['valor'] = $validated['valor'];
        if (array_key_exists('transporte', $validated))         $updateData['transporte'] = $validated['transporte'];

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
            // El motivo es obligatorio únicamente al rechazar — es el sentido del
            // feature: antes se podía rechazar sin dejar ningún registro de por qué.
            'motivo' => 'required_if:estado,rechazado|nullable|string|max:2000',
        ]);

        Gate::authorize('updateStatus', [$program, $validated['estado']]);

        // Cualquier cambio de estado invalida un pedido de aprobación anterior:
        // volvió a borrador (hay que reenviarlo), o ya se resolvió (aprobado/rechazado).
        // El motivo de un rechazo anterior tampoco sobrevive a un cambio de estado
        // posterior (reenviado, aprobado, etc.): dejaría de corresponder al estado actual.
        $program->update([
            'estado' => $validated['estado'],
            'motivo_rechazo' => $validated['estado'] === 'rechazado' ? $validated['motivo'] : null,
            'aprobacion_solicitada_at' => null,
        ]);

        match ($validated['estado']) {
            'enviado'   => ActivityLogger::log('programa_enviado', 'Se envió un programa a revisión', $program->titulo),
            'aprobado'  => ActivityLogger::log('programa_aprobado', 'Se aprobó un programa', $program->titulo),
            'rechazado' => ActivityLogger::log('programa_rechazado', 'Se rechazó un programa', $program->titulo),
            default     => null,
        };

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data'    => $program->fresh(['rama', 'grupo']),
        ], 200);
    }

    /**
     * El autor marca el programa (ya 'enviado') como listo para que lo aprueben.
     * No cambia `estado` — es una señal aparte, para que el board de "próximos a
     * aprobar" del auxiliar solo muestre lo que el educador puntualmente pidió,
     * no todo lo que esté en revisión (que puede seguir yendo y viniendo con comentarios).
     */
    public function solicitarAprobacion($id)
    {
        $program = Program::findOrFail($id);

        Gate::authorize('solicitarAprobacion', $program);

        $program->update(['aprobacion_solicitada_at' => now()]);

        ActivityLogger::log('programa_aprobacion_solicitada', 'Se solicitó la aprobación de un programa', $program->titulo);

        return response()->json([
            'message' => 'Aprobación solicitada correctamente',
            'data'    => $program->fresh(['rama', 'grupo']),
        ], 200);
    }

    /**
     * Eliminar un programa.
     */
    public function destroy($id)
    {
        $program = Program::findOrFail($id);

        Gate::authorize('delete', $program);

        $program->delete();

        return response()->json([
            'message' => 'Programa eliminado correctamente'
        ], 200);
    }

    /**
     * Listado de programas en la papelera. Mismo alcance que delete()/restore():
     * cada usuario ve únicamente los que él mismo eliminó.
     */
    public function papelera()
    {
        $user = Auth::user();

        $programs = Program::onlyTrashed()
            ->where('owner_id', $user->id)
            ->with(['rama', 'grupo'])
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json($programs, 200);
    }

    /**
     * Restaurar un programa eliminado.
     */
    public function restore($id)
    {
        $program = Program::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $program);

        $program->restore();

        return response()->json([
            'message' => 'Programa restaurado correctamente'
        ], 200);
    }
}