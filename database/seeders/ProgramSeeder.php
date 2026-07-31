<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Genera una grilla de 5 grupos x 5 ramas = hasta 25 programas.
     * Cada programa queda asignado a un Educador real de ese grupo+rama
     * (creado en UserSeeder), así podemos probar los 4 casos de visibilidad:
     *  - mismo grupo + misma rama   -> debe verse
     *  - mismo grupo + otra rama    -> no debe verse
     *  - otro grupo + misma rama    -> no debe verse
     *  - otro grupo + otra rama     -> no debe verse
     */
    public function run(): void
    {
        $gruposDePrueba = ['Pompeya', 'San Pio', 'San Pantaleon', 'Nuestra Señora de Fatima', 'San Francisco'];
        $ramasDePrueba = ['Castores', 'Lobatos', 'Unidad Scout', 'Caminantes', 'Rovers'];

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

        // 3 de cada 4 quedan "publicado", 1 de cada 4 "borrador", para tener variedad de estados
        $estados = ['publicado', 'publicado', 'publicado', 'borrador'];
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

                Program::create([
                    'titulo' => $titulo,
                    'diagnostico' => "Diagnóstico de {$nombreRama} del Grupo {$nombreGrupo}: necesidades detectadas en la última reunión de programa.",
                    'objetivos' => "Fortalecer el método scout en {$nombreRama}, fomentando el trabajo en equipo y la progresión personal.",
                    'cronograma' => [
                        ['dia' => 'Sábado', 'hora' => '15:00', 'actividad' => 'Apertura y bienvenida'],
                        ['dia' => 'Sábado', 'hora' => '15:30', 'actividad' => 'Actividad central: ' . $titulo],
                        ['dia' => 'Sábado', 'hora' => '17:00', 'actividad' => 'Cierre y ronda'],
                    ],
                    'anexos' => [
                        ['tipo' => 'juego', 'nombre' => 'Juego de integración'],
                        ['tipo' => 'material', 'nombre' => 'Lista de materiales necesarios'],
                    ],
                    'estado' => $estado,
                    'owner_id' => $owner->id,
                    'rama_id' => $rama->id,
                    'grupo_id' => $grupo->id,
                ]);

                $contador++;
            }
        }

        $this->command->info("ProgramSeeder: {$contador} programas creados.");
    }
}