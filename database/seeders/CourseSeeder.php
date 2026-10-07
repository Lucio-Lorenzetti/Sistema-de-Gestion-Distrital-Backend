<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CourseSeeder extends Seeder
{
    /**
     * El estado de Course es calculado (Course::getEstadoAttribute(), nunca
     * se guarda), así que las fechas acá tienen que ser relativas a "hoy" —
     * fechas fijas quedan obsoletas apenas pasa la fecha y todos los cursos
     * terminan mostrando "Finalizado". Repartidos en 3 bloques para cubrir
     * los 3 estados posibles sin importar cuándo se corra el seeder:
     * - fecha_cierre y fecha_fin en el futuro  -> Abierto
     * - fecha_cierre pasada, fecha_fin futura  -> Cerrado
     * - fecha_cierre y fecha_fin en el pasado  -> Finalizado
     */
    public function run(): void
    {
        $hoy = Carbon::now();

        $cursos = [
            // --- Abierto (4) ---
            [
                'titulo' => 'Técnicas de Vida en la Naturaleza',
                'descripcion' => 'Curso práctico sobre acampe, orientación y manejo seguro en entornos naturales.',
                'link_formulario' => 'https://forms.google.com/tecnicas-vida-naturaleza',
                'categoria' => 'Programa',
                'ramas' => ['Manada', 'Unidad'],
                'fecha_cierre' => $hoy->copy()->addDays(20)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(45)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Primeros Auxilios en Campamento',
                'descripcion' => 'Capacitación en respuesta ante emergencias y atención básica durante actividades al aire libre.',
                'link_formulario' => 'https://forms.google.com/primeros-auxilios',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => $hoy->copy()->addDays(10)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(35)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Curso de Trepador (Nudos y Amarres)',
                'descripcion' => 'Formación técnica en construcciones scout, nudos y amarres para actividades de unidad.',
                'link_formulario' => 'https://forms.google.com/trepador-nudos',
                'categoria' => 'Programa',
                'ramas' => ['Unidad'],
                'fecha_cierre' => $hoy->copy()->addDays(25)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(60)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Juegos y Dinámicas para Pre-menores',
                'descripcion' => 'Repertorio de juegos y actividades adaptadas para la rama más pequeña.',
                'link_formulario' => 'https://forms.google.com/juegos-premenores',
                'categoria' => 'Programa',
                'ramas' => ['Pre-menores'],
                'fecha_cierre' => $hoy->copy()->addDays(15)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(40)->format('Y-m-d'),
            ],

            // --- Cerrado (3): cierre de inscripción pasado, curso todavía no terminó ---
            [
                'titulo' => 'Liderazgo y Dinámica de Grupos',
                'descripcion' => 'Herramientas de conducción y trabajo en equipo orientadas a ramas mayores.',
                'link_formulario' => 'https://forms.google.com/liderazgo-dinamica',
                'categoria' => 'Programa',
                'ramas' => ['Caminantes', 'Rovers'],
                'fecha_cierre' => $hoy->copy()->subDays(5)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(15)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Gestión Económica de Grupos Scouts',
                'descripcion' => 'Administración de fondos, rendiciones y planificación presupuestaria para dirigentes.',
                'link_formulario' => 'https://forms.google.com/gestion-economica',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => $hoy->copy()->subDays(2)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(8)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Curso de Trepador Avanzado',
                'descripcion' => 'Construcciones scout de mayor complejidad, pensado para dirigentes con experiencia previa.',
                'link_formulario' => 'https://forms.google.com/trepador-avanzado',
                'categoria' => 'Programa',
                'ramas' => ['Caminantes'],
                'fecha_cierre' => $hoy->copy()->subDays(10)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->addDays(20)->format('Y-m-d'),
            ],

            // --- Finalizado (3): ya terminó por completo ---
            [
                'titulo' => 'Ceremonias y Simbología Rover',
                'descripcion' => 'Profundización en el ceremonial propio de la rama Rovers y su significado.',
                'link_formulario' => 'https://forms.google.com/ceremonias-rover',
                'categoria' => 'Programa',
                'ramas' => ['Rovers'],
                'fecha_cierre' => $hoy->copy()->subDays(60)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->subDays(40)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Formación en Seguridad e Higiene',
                'descripcion' => 'Normativa vigente y buenas prácticas de seguridad para eventos y campamentos distritales.',
                'link_formulario' => 'https://forms.google.com/seguridad-higiene',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => $hoy->copy()->subDays(50)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->subDays(20)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Comunicación Institucional y Redes',
                'descripcion' => 'Manejo de canales oficiales, redes sociales y comunicación distrital.',
                'link_formulario' => 'https://forms.google.com/comunicacion-institucional',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => $hoy->copy()->subDays(30)->format('Y-m-d'),
                'fecha_fin' => $hoy->copy()->subDays(5)->format('Y-m-d'),
            ],
        ];

        foreach ($cursos as $curso) {
            Course::create($curso);
        }
    }
}