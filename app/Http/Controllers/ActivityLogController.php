<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Registro de actividad del sistema. Muestra quién hizo qué (aprobaciones,
     * designaciones, etc.), así que solo lo ven Director y Developer. Antes era
     * una ruta pública, sin sesión.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        abort_unless($usuario->isDeveloper() || $usuario->hasRole('Director'), 403);

        return ActivityLog::with('user:id,name,totem')
            ->orderByDesc('created_at')
            ->orderByDesc('id') // desempate: varias acciones pueden caer en el mismo segundo
            ->limit(30)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'titulo' => $log->titulo,
                // Sin usuario (p. ej. alguien que se registra y todavía no tiene sesión),
                // la descripción sola: antes quedaba " · detalle" con un separador suelto.
                'desc' => collect([$log->user?->nombre_visible, $log->descripcion])
                    ->filter()
                    ->whenEmpty(fn ($c) => $c->push('Sistema'))
                    ->implode(' · '),
                'time' => $log->created_at->diffForHumans(),
            ]);
    }
}
