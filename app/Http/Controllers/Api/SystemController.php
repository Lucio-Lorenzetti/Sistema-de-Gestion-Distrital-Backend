<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvisoNuevaVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Mantiene viva la base de Supabase (uso interno, llamada por cron-job.org).
     * Protegida por token, no por sesión: la pega un servicio externo.
     *
     * Antes era una Closure en routes/api.php, pero `php artisan route:cache`
     * no soporta rutas con Closure (las ignora sin avisar), así que no cacheaba
     * ninguna ruta de la API. Al ser un método de controller, sí se cachea.
     */
    public function heartbeat(string $token): JsonResponse
    {
        $secreto = env('CRON_SECRET', '');

        if ($secreto === '' || ! hash_equals($secreto, $token)) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        try {
            // El cliente (cron-job.org) puede cortar la conexión por timeout
            // antes de que el comando termine; que siga corriendo igual.
            ignore_user_abort(true);
            set_time_limit(300);
            Artisan::call('db:heartbeat');

            return response()->json(['status' => 'success', 'message' => 'Heartbeat registrado']);
        } catch (\Exception $e) {
            Log::error('Error en cron [db:heartbeat]: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Aviso de versión nueva (uso interno, lo llama el build del frontend en Vercel
     * al terminar cada deploy de producción). Protegido por un secreto compartido
     * en el header X-Aviso-Secret, no por sesión. Es idempotente: si la versión ya
     * se avisó (o es más vieja que la última avisada) responde avisado=false.
     */
    public function nuevaVersion(Request $request): JsonResponse
    {
        $secreto = (string) config('services.aviso_version.secret');

        if ($secreto === '' || ! hash_equals($secreto, (string) $request->header('X-Aviso-Secret', ''))) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $datos = $request->validate([
            'version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'titulo'  => ['nullable', 'string', 'max:150'],
        ]);

        $avisado = AvisoNuevaVersion::avisar($datos['version'], $datos['titulo'] ?? null);

        return response()->json(['avisado' => $avisado, 'version' => $datos['version']]);
    }
}
