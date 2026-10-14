<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
}
