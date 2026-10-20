<?php

namespace App\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Guarda los últimos errores/advertencias del backend para mostrarlos en el
 * panel del Developer, sin tener que entrar a los logs de Render.
 *
 * Se alimenta de MessageLogged (ver AppServiceProvider), o sea de todo lo que
 * pase por Log::warning/error/critical: excepciones sin atajar, fallos de
 * mail (SafeNotifier), errores del asistente de IA, etc.
 *
 * Vive en el cache (ya es una tabla en la base) a propósito: así no hace falta
 * una migración nueva. Es un registro liviano de "qué falló hace poco", no un
 * historial: se pierde al limpiar el cache y guarda solo las últimas entradas.
 */
class ErrorBuffer
{
    private const CLAVE = 'sistema:errores_recientes';
    private const MAX = 25;
    private const NIVELES = ['warning', 'error', 'critical', 'alert', 'emergency'];

    // Si guardar el error falla y eso loguea otro error, no entrar en bucle.
    private static bool $registrando = false;

    public static function registrar(MessageLogged $evento): void
    {
        if (self::$registrando || ! in_array($evento->level, self::NIVELES, true)) {
            return;
        }

        self::$registrando = true;

        try {
            $entrada = [
                'nivel' => $evento->level,
                'mensaje' => mb_strimwidth((string) $evento->message, 0, 300, '…'),
                'fecha' => now()->toIso8601String(),
            ];

            $excepcion = $evento->context['exception'] ?? null;
            if ($excepcion instanceof Throwable) {
                $entrada['origen'] = class_basename($excepcion) . ' · ' . basename($excepcion->getFile()) . ':' . $excepcion->getLine();
            } elseif (isset($evento->context['notification'])) {
                $entrada['origen'] = class_basename((string) $evento->context['notification']);
            }

            $lista = Cache::get(self::CLAVE, []);
            array_unshift($lista, $entrada);

            Cache::forever(self::CLAVE, array_slice($lista, 0, self::MAX));
        } catch (Throwable) {
            // Si el cache no responde, no hay nada más que hacer: no puede romper el pedido.
        } finally {
            self::$registrando = false;
        }
    }

    public static function recientes(): array
    {
        try {
            return Cache::get(self::CLAVE, []);
        } catch (Throwable) {
            return [];
        }
    }
}
