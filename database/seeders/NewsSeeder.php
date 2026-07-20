<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $autor = User::first() ?? User::factory()->create();

        $noticias = [
            ['titulo' => 'Gran Fogón Distrital de Apertura 2026', 'categoria' => 'Distrital', 'estado' => 'Publicada'],
            ['titulo' => 'Convocatoria a Asamblea General de Zona', 'categoria' => 'Institucional', 'estado' => 'Publicada'],
            ['titulo' => 'Ficha Médica 2026: Recordatorio de Entrega', 'categoria' => 'Secretaría', 'estado' => 'Borrador'],
            ['titulo' => 'Resumen de Logros del Indaba Distrital', 'categoria' => 'Formación', 'estado' => 'Publicada'],
            ['titulo' => 'Nuevos Grupos Incorporados al Distrito 3', 'categoria' => 'Grupos', 'estado' => 'Publicada'],
            ['titulo' => 'Curso de Primeros Auxilios — Inscripciones Abiertas', 'categoria' => 'Formación', 'estado' => 'Publicada'],
            ['titulo' => 'Actualización del Reglamento Interno Distrital', 'categoria' => 'Institucional', 'estado' => 'Borrador'],
            ['titulo' => 'Campamento Distrital de Invierno: Detalles y Logística', 'categoria' => 'Distrital', 'estado' => 'Publicada'],
            ['titulo' => 'Reconocimiento a Educadores Destacados 2025', 'categoria' => 'Institucional', 'estado' => 'Publicada'],
            ['titulo' => 'Taller de Técnicas de Vida en la Naturaleza', 'categoria' => 'Formación', 'estado' => 'Publicada'],
            ['titulo' => 'Comunicado: Cambio de Sede para Reuniones de Jefes', 'categoria' => 'Secretaría', 'estado' => 'Publicada'],
            ['titulo' => 'G.S. Pompeya celebra su 40° Aniversario', 'categoria' => 'Grupos', 'estado' => 'Publicada'],
            ['titulo' => 'Informe Anual de Actividades — Ciclo 2025', 'categoria' => 'Institucional', 'estado' => 'Borrador'],
            ['titulo' => 'Jornada de Integración Intergrupal — Mayo 2026', 'categoria' => 'Distrital', 'estado' => 'Publicada'],
            ['titulo' => 'Apertura del Proceso de Promesas Rover', 'categoria' => 'Grupos', 'estado' => 'Publicada'],
            ['titulo' => 'Módulo de Liderazgo Juvenil: Nueva Fecha', 'categoria' => 'Formación', 'estado' => 'Publicada'],
            ['titulo' => 'Circular N°12 — Rendición de Cuentas Grupales', 'categoria' => 'Secretaría', 'estado' => 'Borrador'],
            ['titulo' => 'Encuentro de Caminantes y Rovers — Zona 22', 'categoria' => 'Distrital', 'estado' => 'Publicada'],
            ['titulo' => 'Nueva Plataforma Digital: Guía de Uso para Educadores', 'categoria' => 'Institucional', 'estado' => 'Publicada'],
            ['titulo' => 'Convocatoria Voluntarios — Jamboree Nacional 2027', 'categoria' => 'Distrital', 'estado' => 'Publicada'],
        ];

        $contenidos = [
            'El evento convocó a más de 200 participantes de toda la zona, consolidando el espíritu de comunidad del Distrito 3.',
            'Se informa a todos los educadores y jefes de grupo la obligatoriedad de asistencia según el reglamento vigente.',
            'Recordamos que el plazo de entrega vence el próximo viernes. Los formularios están disponibles en secretaría.',
            'Durante el ciclo se alcanzaron metas históricas en materia de formación, con más de 80 educadores certificados.',
            'Damos la bienvenida a los nuevos grupos que se suman a nuestra estructura distrital este año lectivo.',
            'El curso tendrá modalidad presencial y cupos limitados. Las inscripciones se habilitaron en el sistema esta semana.',
            'La comisión directiva pone a disposición el borrador actualizado para recibir observaciones hasta fin de mes.',
            'Compartimos la información logística completa: ubicación, fechas, lista de materiales y cronograma de actividades.',
            'La ceremonia de reconocimiento se realizó en el salón principal con presencia de autoridades zonales y distritales.',
            'El taller abarcó orientación, nudos, cocina al aire libre y técnicas de supervivencia básica.',
            'A partir del próximo mes las reuniones ordinarias de jefes de grupo se realizarán en la nueva sede.',
            'El grupo celebra cuatro décadas de servicio ininterrumpido con una jornada especial abierta a la comunidad.',
            'El informe detalla las actividades, logros, dificultades y proyecciones del ciclo educativo completado.',
            'La jornada reunió a educadores y jóvenes de cinco grupos en actividades de integración y trabajo en equipo.',
            'Se convoca a los rovers que cumplan los requisitos a presentar su carpeta de promesa antes del 30 de junio.',
            'Por motivos de agenda del equipo formador, el módulo se reprogramó para la segunda semana de julio.',
            'Recordamos a todos los jefes de grupo la obligación de presentar la rendición trimestral antes del cierre.',
            'El encuentro de ramas mayores reunirá a caminantes y rovers de toda la zona en un fin de semana de actividades.',
            'Publicamos la guía paso a paso para que todos los educadores puedan aprovechar al máximo el nuevo sistema.',
            'Se buscan voluntarios mayores de 18 años con disponibilidad para el evento internacional del próximo año.',
        ];

        News::truncate();

        foreach ($noticias as $index => $datos) {
            News::create([
                'titulo' => $datos['titulo'],
                'copete' => substr($contenidos[$index], 0, 100) . '...', // Creamos un copete corto
                'contenido' => $contenidos[$index],
                'autor_id' => $autor->id,
                'estado' => $datos['estado'],
                'categoria' => $datos['categoria'],
                'visitas' => rand(10, 500),
                'publicado_at' => $datos['estado'] === 'Publicada'
                    ? Carbon::now()->subDays($index + 1)
                    : null,
            ]);
        }
    }
}