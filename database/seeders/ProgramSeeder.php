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
     * Genera programas de prueba para la grilla de grupos y ramas.
     */
    public function run(): void
    {
        $gruposDePrueba = ['Pompeya', 'San Pio', 'San Pantaleon', 'Nuestra Señora de Fatima', 'San Francisco'];
        $ramasDePrueba = ['Castores', 'Lobatos', 'Unidad Scout', 'Caminantes', 'Rovers'];
        
        $tipos = ['cfa', 'campamento', 'cuatrimestre'];

        $temasPorRama = [
            'Castores' => [
                'La colonia descubre el bosque',
                'Aventuras en la madriguera',
                'Castores cuidando el agua',
                'Juegos de la Gran Familia',
                'Explorando el jardín de la sede',
            ],
            'Lobatos' => [
                'La manada en la selva',
                'Cacería de Kim',
                'Lobatos al rescate',
                'El Consejo de Roca',
                'Semana de la Ley de la Manada',
            ],
            'Unidad Scout' => [
                'Campamento de patrullas',
                'Técnica scout: nudos y pioneering',
                'Raid de orientación',
                'Proyecto de servicio comunitario',
                'Especialidades: primeros auxilios',
            ],
            'Caminantes' => [
                'Proyecto de comunidad caminante',
                'Travesía de fin de año',
                'Taller de liderazgo caminante',
                'Salida de contacto con la naturaleza',
                'Foro de participación juvenil',
            ],
            'Rovers' => [
                'Proyecto de vida rover',
                'Ruta de servicio',
                'Clan rover: planificación anual',
                'Empresa rover',
                'Encuentro distrital de clanes',
            ],
        ];

        // 3 de cada 4 quedan "aprobado" (equivalente a publicado), 1 de cada 4 "borrador"
        $estados = ['aprobado', 'aprobado', 'aprobado', 'borrador'];
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

                // Buscamos un Educador real de ese grupo+rama para usarlo de owner
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

                // Fechas ficticias de prueba
                $fechaInicio = Carbon::now()->addDays($contador * 2)->format('Y-m-d');
                $fechaFin = Carbon::now()->addDays(($contador * 2) + 3)->format('Y-m-d');

                Program::create([
                    'titulo'       => $titulo,
                    'diagnostico'  => "Diagnóstico de {$nombreRama} del Grupo {$nombreGrupo}: necesidades detectadas en la última reunión de programa.",
                    'objetivos'    => "Fortalecer el método scout en {$nombreRama}, fomentando el trabajo en equipo y la progresión personal.",
                    'tipo'         => $tipo,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin,
                    'cronograma'   => [
                        [
                            'dia'            => 1,
                            'fecha'          => $fechaInicio,
                            'nombreDia'      => 'Sábado',
                            'fechaFormatted' => 'Sábado, 15 de Octubre',
                            'contenidoHtml'  => "<p><strong>15:00</strong> - Apertura y bienvenida.</p><p><strong>15:30</strong> - Actividad central: {$titulo}</p><p><strong>17:00</strong> - Cierre y ronda.</p>"
                        ],
                        [
                            'dia'            => 2,
                            'fecha'          => $fechaFin,
                            'nombreDia'      => 'Domingo',
                            'fechaFormatted' => 'Domingo, 16 de Octubre',
                            'contenidoHtml'  => '<p><strong>10:00</strong> - Evaluación general y cierre de la actividad.</p>'
                        ]
                    ],
                    'anexos' => [
                        ['tipo' => 'juego', 'nombre' => 'Juego de integración'],
                        ['tipo' => 'material', 'nombre' => 'Lista de materiales necesarios'],
                    ],
                    'estado'   => $estado,
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