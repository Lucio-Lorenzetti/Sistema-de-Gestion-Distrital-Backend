<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Registra un evento de actividad del sistema.
     *
     * Uso típico, al final de un store() exitoso:
     *   ActivityLogger::log('noticia_creada', 'Se publicó una noticia', $noticia->titulo);
     *
     * @param string $accion Identificador corto y estable (para filtrar/agrupar a futuro)
     * @param string $titulo Frase corta en pasado, lista para mostrar ("Subió un programa")
     * @param string|null $descripcion Detalle específico (título de la noticia, nombre del curso, etc.)
     */
    public static function log(string $accion, string $titulo, ?string $descripcion = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
        ]);
    }
}