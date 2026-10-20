<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Peticiones de mejora" — el roadmap liviano de la app. Director puede
 * cargar ideas (quedan "pendiente" hasta que Developer las triage); Developer
 * ve todo, agrega las suyas directo (sin quedar "pendiente", ya que no
 * necesita aprobación de nadie) y decide el estado de cualquiera.
 *
 * Ojo: hasRole()/hasAnyRole() bypassean todo para Developer (devuelven true
 * para cualquier string), así que para distinguir "es literalmente Developer"
 * de "es Director" hay que usar isDeveloper(), no hasRole('Developer').
 */
class FeatureRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isDeveloper() || $user->hasAnyRole(['Director']), 403);

        $query = FeatureRequest::with('autor:id,name,totem')->latest();

        if (!$user->isDeveloper()) {
            $query->where('autor_id', $user->id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isDeveloper() || $user->hasAnyRole(['Director']), 403);

        $validated = $request->validate([
            'contenido' => 'required|string|max:2000',
            'estado' => 'nullable|in:pendiente,proxima_version,descartada,implementada',
            'prioridad' => 'nullable|in:alta,media,baja',
        ]);

        // Developer no pasa por triage propio: si no elige estado, entra
        // directo a "próxima versión". Director siempre arranca "pendiente".
        $estado = $user->isDeveloper()
            ? ($validated['estado'] ?? 'proxima_version')
            : 'pendiente';

        $peticion = FeatureRequest::create([
            'autor_id' => $user->id,
            'contenido' => $validated['contenido'],
            'estado' => $estado,
            'prioridad' => $validated['prioridad'] ?? 'media',
        ]);

        ActivityLogger::log('peticion_mejora_creada', 'Se propuso una mejora al sistema', Str::limit($peticion->contenido, 80));

        return response()->json($peticion->load('autor:id,name,totem'), 201);
    }

    public function update(Request $request, FeatureRequest $featureRequest)
    {
        abort_unless($request->user()->isDeveloper(), 403);

        $validated = $request->validate([
            'estado' => 'sometimes|in:pendiente,proxima_version,descartada,implementada',
            'prioridad' => 'sometimes|nullable|in:alta,media,baja',
        ]);

        $featureRequest->update($validated);

        // Solo el cambio de estado es un hito del roadmap; mover la prioridad no.
        if (array_key_exists('estado', $validated)) {
            ActivityLogger::log(
                'peticion_mejora_estado',
                'Se cambió el estado de una petición de mejora',
                str_replace('_', ' ', $featureRequest->estado) . ' · ' . Str::limit($featureRequest->contenido, 60)
            );
        }

        return response()->json($featureRequest->load('autor:id,name,totem'));
    }

    public function destroy(Request $request, FeatureRequest $featureRequest)
    {
        abort_unless($request->user()->isDeveloper(), 403);

        $featureRequest->delete();

        ActivityLogger::log('peticion_mejora_eliminada', 'Se eliminó una petición de mejora', Str::limit($featureRequest->contenido, 80));

        return response()->json(['message' => 'Petición eliminada']);
    }
}
