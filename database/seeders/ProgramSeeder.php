<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProgramSeeder extends Seeder
{
    /**
     * 10 programas (2 grupos x 5 ramas), como si los hubiera cargado un
     * Educador real del sistema: mismos campos que completa el formulario
     * (diagnóstico, objetivos, cronograma, anexos, lugar/valor/transporte).
     * Cubren los 3 tipos y los 4 estados posibles (borrador/enviado/
     * aprobado/rechazado), varios de ellos ya habiendo pasado por el flujo
     * de solicitud de aprobación (aprobacion_solicitada_at).
     */
    public function run(): void
    {
        $gruposDePrueba = ['Pompeya', 'San Pio'];
        $ramasDePrueba = ['Castores', 'Lobatos', 'Unidad Scout', 'Caminantes', 'Rovers'];

        $tipos = ['cfa', 'campamento', 'cuatrimestre'];
        $estados = ['borrador', 'enviado', 'aprobado', 'rechazado'];

        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $formatearFecha = function (Carbon $fecha) use ($diasSemana, $meses) {
            return [
                'nombreDia' => $diasSemana[$fecha->dayOfWeek],
                'fechaFormatted' => "{$diasSemana[$fecha->dayOfWeek]}, {$fecha->day} de {$meses[$fecha->month]}",
            ];
        };

        $temasPorRama = [
            'Castores' => [
                'La colonia descubre el bosque',
                'Aventuras en la madriguera',
            ],
            'Lobatos' => [
                'La manada en la selva',
                'Cacería de Kim',
            ],
            'Unidad Scout' => [
                'Campamento de patrullas',
                'Técnica scout: nudos y pioneering',
            ],
            'Caminantes' => [
                'Proyecto de comunidad caminante',
                'Travesía de fin de año',
            ],
            'Rovers' => [
                'Proyecto de vida rover',
                'Ruta de servicio',
            ],
        ];

        $motivosRechazo = [
            'El diagnóstico no justifica la actividad propuesta — ampliar y volver a enviar.',
            'Falta el cronograma detallado del segundo día.',
            'El presupuesto estimado excede lo autorizado para esta rama este cuatrimestre.',
        ];

        $contador = 0;

        foreach ($gruposDePrueba as $indexGrupo => $nombreGrupo) {
            $grupo = Grupo::where('nombre', $nombreGrupo)->first();

            if (!$grupo) {
                $this->command->warn("Grupo '{$nombreGrupo}' no encontrado, se omite.");
                continue;
            }

            foreach ($ramasDePrueba as $nombreRama) {
                $rama = Rama::where('nombre', $nombreRama)->first();

                if (!$rama) {
                    $this->command->warn("Rama '{$nombreRama}' no encontrada, se omite.");
                    continue;
                }

                $owner = User::where('grupo_id', $grupo->id)
                    ->where('rama_id', $rama->id)
                    ->whereHas('roles', fn ($q) => $q->where('nombre', 'Educador'))
                    ->first();

                if (!$owner) {
                    $this->command->warn("No hay Educador de {$nombreRama} en {$nombreGrupo}, se omite ese programa.");
                    continue;
                }

                $titulo = $temasPorRama[$nombreRama][$indexGrupo] ?? "Programa de {$nombreRama}";
                $estado = $estados[$contador % count($estados)];
                $tipo = $tipos[$contador % count($tipos)];

                $fechaInicio = Carbon::now()->addDays(($contador * 3) + 5);
                $fechaFin = (clone $fechaInicio)->addDays(1);

                $diaUno = $formatearFecha($fechaInicio);
                $diaDos = $formatearFecha($fechaFin);

                // Si pasó (o está pasando) por revisión, queda el timestamp de
                // cuándo se solicitó — igual que haría RoleRequestService-style
                // flujo real vía AuthController/ProgramController::updateStatus().
                $aprobacionSolicitadaAt = in_array($estado, ['enviado', 'aprobado', 'rechazado'], true)
                    ? Carbon::now()->subDays(3)
                    : null;

                $motivoRechazo = $estado === 'rechazado'
                    ? $motivosRechazo[$contador % count($motivosRechazo)]
                    : null;

                Program::create([
                    'titulo'       => $titulo,
                    'diagnostico'  => "Diagnóstico de {$nombreRama} del Grupo {$nombreGrupo}: necesidades detectadas en la última reunión de programa.",
                    'objetivos'    => "Fortalecer el método scout en {$nombreRama}, fomentando el trabajo en equipo y la progresión personal.",
                    'educadores_a_cargo' => $owner->name,
                    'tipo'         => $tipo,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin'    => $fechaFin->format('Y-m-d'),
                    'cronograma'   => [
                        [
                            'dia'            => 1,
                            'fecha'          => $fechaInicio->format('Y-m-d'),
                            'nombreDia'      => $diaUno['nombreDia'],
                            'fechaFormatted' => $diaUno['fechaFormatted'],
                            'contenidoHtml'  => "<p><strong>15:00</strong> - Apertura y bienvenida.</p><p><strong>15:30</strong> - Actividad central: {$titulo}</p><p><strong>17:00</strong> - Cierre y ronda.</p>",
                        ],
                        [
                            'dia'            => 2,
                            'fecha'          => $fechaFin->format('Y-m-d'),
                            'nombreDia'      => $diaDos['nombreDia'],
                            'fechaFormatted' => $diaDos['fechaFormatted'],
                            'contenidoHtml'  => '<p><strong>10:00</strong> - Evaluación general y cierre de la actividad.</p>',
                        ],
                    ],
                    'anexos' => [
                        ['tipo' => 'juego', 'nombre' => 'Juego de integración'],
                        ['tipo' => 'material', 'nombre' => 'Lista de materiales necesarios'],
                    ],
                    'lugar'       => "Sede del Grupo {$nombreGrupo}",
                    'valor'       => '$0 (actividad sin costo)',
                    'transporte'  => 'A cargo de cada familia',
                    'estado'      => $estado,
                    'motivo_rechazo' => $motivoRechazo,
                    'aprobacion_solicitada_at' => $aprobacionSolicitadaAt,
                    'owner_id' => $owner->id,
                    'rama_id'  => $rama->id,
                    'grupo_id' => $grupo->id,
                ]);

                $contador++;
            }
        }

        $this->command->info("ProgramSeeder: {$contador} programas creados exitosamente.");
    }
}
