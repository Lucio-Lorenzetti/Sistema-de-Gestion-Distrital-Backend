<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chat con Gemini para ayudar a Developer a pensar actualizaciones —
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

        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return response()->json([
                'configurado' => false,
                'message' => 'Falta configurar GEMINI_API_KEY en el servidor.',
            ], 200);
        }

        $roadmap = FeatureRequest::orderByDesc('created_at')->limit(40)->get(['contenido', 'estado', 'prioridad']);

        $contexto = $roadmap->isEmpty()
            ? 'El roadmap todavía no tiene ninguna idea cargada.'
            : $roadmap->map(fn ($f) => "- [{$f->estado} / prioridad {$f->prioridad}] {$f->contenido}")->implode("\n");

        $systemPrompt = "Sos un asistente que ayuda al Developer de un sistema de gestión para un distrito de Scouts de Argentina "
            . "(gestiona Programas educativos, Noticias, Cursos, Biblioteca y Usuarios/Roles) a pensar ideas para próximas versiones. "
            . "Sé concreto y breve. No repitas ideas que ya están en este roadmap:\n\n{$contexto}";

        // Gemini usa "user"/"model" (no "assistant") como roles del historial.
        $contents = collect($validated['historial'] ?? [])
            ->map(fn ($m) => [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['contenido']]],
            ])
            ->push(['role' => 'user', 'parts' => [['text' => $validated['mensaje']]]])
            ->values()
            ->all();

        $model = config('services.gemini.model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $body = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
        ];

        // El tier gratis de Gemini devuelve 503 "high demand" seguido — no es
        // un error nuestro, pero sin reintento la demo queda a la suerte.
        // Un timeout/corte de red tira ConnectionException ANTES de que haya
        // Response para chequear ->failed(), por eso el try/catch adentro
        // del mismo loop de reintentos.
        $respuesta = null;
        $intentos = 3;

        for ($intento = 1; $intento <= $intentos; $intento++) {
            try {
                $respuesta = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post($url, $body);

                if ($respuesta->successful()) {
                    break;
                }

                Log::warning('Asistente IA (Gemini): respuesta con error', [
                    'intento' => $intento, 'status' => $respuesta->status(), 'body' => $respuesta->body(),
                ]);
            } catch (ConnectionException $e) {
                Log::warning('Asistente IA (Gemini): fallo de conexión', ['intento' => $intento, 'error' => $e->getMessage()]);
                $respuesta = null;
            }

            if ($intento < $intentos) {
                usleep(800_000);
            }
        }

        if (!$respuesta || !$respuesta->successful()) {
            return response()->json([
                'configurado' => true,
                'message' => 'El asistente no pudo responder ahora mismo. Probá de nuevo en un rato.',
            ], 502);
        }

        $texto = $respuesta->json('candidates.0.content.parts.0.text', '');

        return response()->json(['configurado' => true, 'respuesta' => $texto]);
    }
}
