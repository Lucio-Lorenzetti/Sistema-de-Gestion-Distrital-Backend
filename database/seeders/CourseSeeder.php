<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Fechas pensadas relativas a "hoy" para cubrir los 3 estados posibles:
     * - fecha_cierre y fecha_fin en el futuro  -> Abierto
     * - fecha_cierre pasada, fecha_fin futura  -> Cerrado
     * - fecha_cierre y fecha_fin en el pasado  -> Finalizado
     */
    public function run(): void
    {
        $cursos = [
            [
                'titulo' => 'Técnicas de Vida en la Naturaleza',
                'descripcion' => 'Curso práctico sobre acampe, orientación y manejo seguro en entornos naturales.',
                'link_formulario' => 'https://forms.google.com/tecnicas-vida-naturaleza',
                'categoria' => 'Programa',
                'ramas' => ['Manada', 'Unidad'],
                'fecha_cierre' => '2026-07-20',
                'fecha_fin' => '2026-08-15',
            ],
            [
                'titulo' => 'Primeros Auxilios en Campamento',
                'descripcion' => 'Capacitación en respuesta ante emergencias y atención básica durante actividades al aire libre.',
                'link_formulario' => 'https://forms.google.com/primeros-auxilios',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => '2026-06-15',
                'fecha_fin' => '2026-07-10',
            ],
            [
                'titulo' => 'Liderazgo y Dinámica de Grupos',
                'descripcion' => 'Herramientas de conducción y trabajo en equipo orientadas a ramas mayores.',
                'link_formulario' => 'https://forms.google.com/liderazgo-dinamica',
                'categoria' => 'Programa',
                'ramas' => ['Caminantes', 'Rovers'],
                'fecha_cierre' => '2026-05-01',
                'fecha_fin' => '2026-05-20',
            ],
            [
                'titulo' => 'Curso de Trepador (Nudos y Amarres)',
                'descripcion' => 'Formación técnica en construcciones scout, nudos y amarres para actividades de unidad.',
                'link_formulario' => 'https://forms.google.com/trepador-nudos',
                'categoria' => 'Programa',
                'ramas' => ['Unidad'],
                'fecha_cierre' => '2026-07-25',
                'fecha_fin' => '2026-08-30',
            ],
            [
                'titulo' => 'Gestión Económica de Grupos Scouts',
                'descripcion' => 'Administración de fondos, rendiciones y planificación presupuestaria para dirigentes.',
                'link_formulario' => 'https://forms.google.com/gestion-economica',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => '2026-07-10',
                'fecha_fin' => '2026-07-15',
            ],
            [
                'titulo' => 'Ceremonias y Simbología Rover',
                'descripcion' => 'Profundización en el ceremonial propio de la rama Rovers y su significado.',
                'link_formulario' => 'https://forms.google.com/ceremonias-rover',
                'categoria' => 'Programa',
                'ramas' => ['Rovers'],
                'fecha_cierre' => '2026-04-01',
                'fecha_fin' => '2026-04-20',
            ],
            [
                'titulo' => 'Formación en Seguridad e Higiene',
                'descripcion' => 'Normativa vigente y buenas prácticas de seguridad para eventos y campamentos distritales.',
                'link_formulario' => 'https://forms.google.com/seguridad-higiene',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => '2026-06-01',
                'fecha_fin' => '2026-06-30',
            ],
            [
                'titulo' => 'Juegos y Dinámicas para Pre-menores',
                'descripcion' => 'Repertorio de juegos y actividades adaptadas para la rama más pequeña.',
                'link_formulario' => 'https://forms.google.com/juegos-premenores',
                'categoria' => 'Programa',
                'ramas' => ['Pre-menores'],
                'fecha_cierre' => '2026-07-18',
                'fecha_fin' => '2026-08-05',
            ],
            [
                'titulo' => 'Curso de Trepador Avanzado',
                'descripcion' => 'Construcciones scout de mayor complejidad, pensado para dirigentes con experiencia previa.',
                'link_formulario' => 'https://forms.google.com/trepador-avanzado',
                'categoria' => 'Programa',
                'ramas' => ['Caminantes'],
                'fecha_cierre' => '2026-06-20',
                'fecha_fin' => '2026-07-25',
            ],
            [
                'titulo' => 'Comunicación Institucional y Redes',
                'descripcion' => 'Manejo de canales oficiales, redes sociales y comunicación distrital.',
                'link_formulario' => 'https://forms.google.com/comunicacion-institucional',
                'categoria' => 'Gestion',
                'ramas' => [],
                'fecha_cierre' => '2026-07-30',
                'fecha_fin' => '2026-08-10',
            ],
        ];

        foreach ($cursos as $curso) {
            Course::create($curso);
        }
    }
}