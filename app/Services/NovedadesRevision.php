<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Arma el feed de novedades de revisión de un auxiliar de programa.
 *
 * Es lógica pura sobre modelos ya cargados (sin consultas), así se puede probar
 * sin base de datos. Cada novedad es un arreglo con: tipo, fecha, program, actor, detalle.
 *
 * Tipos:
 *  - enviado:   un educador mandó el programa a revisión por primera vez.
 *  - reenviado: lo volvió a mandar después de una vuelta de comentarios.
 *  - respuesta: alguien respondió un hilo (se cuenta la última respuesta de cada hilo).
 *  - resuelto:  alguien marcó un hilo como resuelto.
 *
 * Nunca incluye lo que hizo el propio usuario que mira, ni lo anterior a $desde.
 * La aprobación solicitada no está acá: ya tiene su propia tarjeta ("Próximos a Aprobar").
 */
class NovedadesRevision
{
    public static function armar(Collection $programas, Collection $hilos, int $usuarioId, CarbonInterface $desde, int $limite = 15): Collection
    {
        $novedades = collect();

        foreach ($programas as $programa) {
            if ($programa->enviado_at && $programa->enviado_at->gte($desde)) {
                $novedades->push(self::item(
                    $programa->enviado_tras_comentarios ? 'reenviado' : 'enviado',
                    $programa->enviado_at,
                    $programa,
                    $programa->owner,
                    null,
                ));
            }
        }

        $porId = $programas->keyBy('id');

        foreach ($hilos as $hilo) {
            $programa = $porId->get($hilo->program_id);
            if (!$programa) {
                continue;
            }

            $ultimaRespuesta = $hilo->replies
                ->filter(fn ($r) => $r->user_id !== $usuarioId && $r->created_at->gte($desde))
                ->sortByDesc('created_at')
                ->first();

            if ($ultimaRespuesta) {
                $novedades->push(self::item('respuesta', $ultimaRespuesta->created_at, $programa, $ultimaRespuesta->user, $ultimaRespuesta->contenido));
            }

            if ($hilo->resuelta && $hilo->resuelta_at && $hilo->resuelta_por !== $usuarioId && $hilo->resuelta_at->gte($desde)) {
                $novedades->push(self::item('resuelto', $hilo->resuelta_at, $programa, $hilo->resolutor, $hilo->contenido));
            }
        }

        return $novedades->sortByDesc('fecha')->take($limite)->values();
    }

    private static function item(string $tipo, CarbonInterface $fecha, $programa, $actor, ?string $detalle): array
    {
        return [
            'tipo'    => $tipo,
            'fecha'   => $fecha->toIso8601String(),
            'program' => [
                'id'     => $programa->id,
                'titulo' => $programa->titulo,
                'owner'  => $programa->owner,
                'rama'   => $programa->rama,
                'grupo'  => $programa->grupo,
            ],
            'actor'   => $actor,
            'detalle' => $detalle !== null ? mb_substr($detalle, 0, 140) : null,
        ];
    }
}
