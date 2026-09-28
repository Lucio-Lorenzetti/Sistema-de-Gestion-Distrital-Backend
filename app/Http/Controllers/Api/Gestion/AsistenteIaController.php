<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Chat con Claude para ayudar a Developer a pensar actualizaciones —
 * conversación efímera (no se persiste), el historial vive en el estado del
 * frontend y se manda de vuelta en cada request. Le paso como contexto el
 * roadmap actual (feature_requests) para que no repita ideas ya cargadas.
 */
class AsistenteIaController extends Controller
{
    public function chat(Request $request)
    {
        abort_unless($request->user()->isDeveloper(), 403);

        $validated = $request->validate([
            'mensaje' => 'required|string|max:4000',
            'historial' => 'nullable|array|max:20',
            'historial.*.role' => 'required_with:historial|in:user,assistant',
            'historial.*.contenido' => 'required_with:historial|string',
        ]);

        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            return response()->json([
                'configurado' => false,
                'message' => 'Falta configurar ANTHROPIC_API_KEY en el servidor.',
            ], 200);
        }

        $roadmap = FeatureRequest::orderByDesc('created_at')->limit(40)->get(['contenido', 'estado', 'prioridad']);

        $contexto = $roadmap->isEmpty()
            ? 'El roadmap todavía no tiene ninguna idea cargada.'
            : $roadmap->map(fn ($f) => "- [{$f->estado} / prioridad {$f->prioridad}] {$f->contenido}")->implode("\n");

        $systemPrompt = "Sos un asistente que ayuda al Developer de un sistema de gestión para un distrito de Scouts de Argentina "
            . "(gestiona Programas educativos, Noticias, Cursos, Biblioteca y Usuarios/Roles) a pensar ideas para próximas versiones. "
            . "Sé concreto y breve. No repitas ideas que ya están en este roadmap:\n\n{$contexto}";

        $mensajes = collect($validated['historial'] ?? [])
            ->map(fn ($m) => ['role' => $m['role'], 'content' => $m['contenido']])
            ->push(['role' => 'user', 'content' => $validated['mensaje']])
            ->values()
            ->all();

        $respuesta = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => $mensajes,
        ]);

        if ($respuesta->failed()) {
            return response()->json([
                'configurado' => true,
                'message' => 'El asistente no pudo responder ahora mismo. Probá de nuevo en un rato.',
            ], 502);
        }

        $texto = collect($respuesta->json('content'))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return response()->json(['configurado' => true, 'respuesta' => $texto]);
    }
}
