<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Program;
use App\Models\Rama;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ErrorBuffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Datos del Dashboard del Developer: salud del sistema y métricas del distrito.
 * Solo Developer. Nunca devuelve secretos: de las claves/credenciales solo se
 * informa si están configuradas (true/false).
 */
class SistemaController extends Controller
{
    /**
     * Estado técnico: base de datos, cron que la mantiene viva, servicios
     * externos configurados, entorno y últimos errores del backend.
     */
    public function estado(Request $request): JsonResponse
    {
        abort_unless($request->user()->isDeveloper(), 403);

        $inicio = microtime(true);
        try {
            DB::select('select 1');
            $baseDeDatos = ['ok' => true, 'ms' => (int) round((microtime(true) - $inicio) * 1000)];
        } catch (Throwable $e) {
            $baseDeDatos = ['ok' => false, 'ms' => null];
        }

        // db:heartbeat (lo dispara cron-job.org) guarda acá la fecha de su última corrida.
        $ultimoHeartbeat = Cache::get('db_heartbeat');

        $mailer = (string) config('mail.default');

        return response()->json([
            'base_de_datos' => $baseDeDatos,
            'heartbeat' => [
                'ultimo' => $ultimoHeartbeat,
                'cron_secret_configurado' => filled(env('CRON_SECRET')),
            ],
            'servicios' => [
                'gemini' => [
                    'configurado' => filled(config('services.gemini.key')),
                    'modelo' => config('services.gemini.model'),
                ],
                'correo' => [
                    // 'log' y 'array' no envían nada: solo escriben o descartan el mail.
                    'configurado' => ! in_array($mailer, ['log', 'array'], true),
                    'driver' => $mailer,
                    'host' => config('mail.mailers.smtp.host'),
                ],
                'almacenamiento' => [
                    'disco' => config('filesystems.uploads_disk'),
                ],
            ],
            'entorno' => [
                'app_env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'cache' => config('cache.default'),
                // Render expone el commit desplegado en esta variable.
                'commit' => substr((string) env('RENDER_GIT_COMMIT', ''), 0, 7) ?: null,
            ],
            'errores_recientes' => ErrorBuffer::recientes(),
        ]);
    }

    /**
     * Vacía el registro de errores recientes del Dashboard. Solo borra ese registro
     * (el buffer del cache), no los logs del hosting ni la actividad del sistema.
     */
    public function vaciarErrores(Request $request): JsonResponse
    {
        abort_unless($request->user()->isDeveloper(), 403);

        $eliminados = ErrorBuffer::vaciar();

        ActivityLogger::log('errores_vaciados', 'Se vació el registro de errores recientes', "{$eliminados} entradas");

        return response()->json(['message' => 'Errores vaciados', 'eliminados' => $eliminados]);
    }

    /**
     * Métricas del distrito: usuarios, programas y completitud del perfil público de los grupos.
     */
    public function metricas(Request $request): JsonResponse
    {
        abort_unless($request->user()->isDeveloper(), 403);

        // — Usuarios —
        $usuarios = [
            'total' => User::count(),
            'activos' => User::where('activo', true)->count(),
            // Cuentas que todavía no fueron aprobadas.
            'pendientes_de_aprobacion' => User::where('activo', false)->count(),
            'sin_verificar_correo' => User::whereNull('email_verified_at')->count(),
            'solicitudes_de_rol_pendientes' => RoleRequest::where('estado', 'pendiente')->count(),
        ];

        $personasPorRol = DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->whereNull('users.deleted_at')
            ->selectRaw('user_roles.role_id, count(*) as total')
            ->groupBy('user_roles.role_id')
            ->pluck('total', 'role_id');

        $porRol = Role::orderBy('id')->get()->map(fn (Role $rol) => [
            'rol' => $rol->nombre,
            'total' => (int) ($personasPorRol[$rol->id] ?? 0),
        ])->values();

        // — Programas —
        $estados = ['borrador', 'enviado', 'aprobado', 'rechazado'];

        $filas = Program::selectRaw('rama_id, estado, count(*) as total')
            ->groupBy('rama_id', 'estado')
            ->get();

        $porEstado = collect($estados)->mapWithKeys(
            fn (string $estado) => [$estado => (int) $filas->where('estado', $estado)->sum('total')]
        );

        $porRama = Rama::orderBy('id')->get()->map(function (Rama $rama) use ($filas, $estados) {
            $deLaRama = $filas->where('rama_id', $rama->id);

            return [
                'rama' => $rama->nombre,
                'total' => (int) $deLaRama->sum('total'),
                'por_estado' => collect($estados)->mapWithKeys(
                    fn (string $estado) => [$estado => (int) $deLaRama->where('estado', $estado)->sum('total')]
                ),
            ];
        })->values();

        // — Perfil público de los grupos —
        $camposPerfil = [
            'descripcion' => 'Descripción',
            'foto' => 'Foto',
            'direccion' => 'Dirección',
            'telefono' => 'Teléfono',
        ];

        $grupos = Grupo::orderBy('nombre')->get();

        $incompletos = $grupos->map(function (Grupo $grupo) use ($camposPerfil) {
            $faltan = collect($camposPerfil)
                ->filter(fn (string $etiqueta, string $campo) => blank($grupo->{$campo}))
                ->values();

            return [
                'id' => $grupo->id,
                'nombre' => $grupo->nombre,
                'faltan' => $faltan,
            ];
        })->filter(fn (array $grupo) => $grupo['faltan']->isNotEmpty())->values();

        return response()->json([
            'usuarios' => $usuarios,
            'usuarios_por_rol' => $porRol,
            'programas' => [
                'total' => (int) $porEstado->sum(),
                'por_estado' => $porEstado,
                'por_rama' => $porRama,
            ],
            'grupos' => [
                'total' => $grupos->count(),
                'con_perfil_completo' => $grupos->count() - $incompletos->count(),
                'incompletos' => $incompletos,
            ],
        ]);
    }
}
