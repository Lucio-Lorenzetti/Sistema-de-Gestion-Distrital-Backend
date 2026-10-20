<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscamos el objeto del Distrito 3
        $distrito = \App\Models\Distrito::where('numero', 3)->first();

        // Verificamos que exista para evitar errores
        if (!$distrito) {
            throw new \Exception("Error: El Distrito 3 no existe. Corré primero el DistritoSeeder.");
        }

        // Idempotente: se puede correr cuantas veces haga falta
        // (php artisan db:seed --class=GrupoSeeder) sin duplicar grupos.
        // Un grupo se identifica por (distrito, nombre).
        // - 'numero' solo se fija al CREAR el grupo: el Jefe de Grupo puede haberlo
        //   cargado desde "Mi Grupo" y no hay que pisarlo con el '000' de acá.
        // - 'descripcion', 'direccion' e 'instagram' (opcionales) SÍ se sobreescriben
        //   en grupos que ya existen. Si un grupo no trae alguno, se deja como está.
        $grupos = [
            [
                'numero' => '034',
                'nombre' => 'Pompeya',
                'descripcion' => 'Grupo Scout Nº 34. Fundado el 28 de abril de 1963.',
                'instagram' => 'https://www.instagram.com/gspompeya/',
            ],
            [
                'numero' => '000',
                'nombre' => 'San Pio',
                'direccion' => 'Italia 1133',
                'descripcion' => 'El grupo San Pío X fue fundado el día 21 de Septiembre de 1961 por la hermana Kostka, misionera cruzada de la iglesia de la capilla Pequeña Obra. En un primer comienzo, la idea de la hermana fue la de sacar a los chicos del barrio de la calle y darles un lugar de contención y educación en valores. Con el paso del tiempo el grupo fue creciendo en número y en vivencias, los chicos se comprometían cada vez más con el escultismo y con el entorno en el que vivían. Hoy en día, podemos decir que, lo que una vez pensó nuestra fundadora fue alcanzado y superado. Los muchachos que ya desde hace unos años pasan por el grupo, no solo trabajan en la educación no formal dentro del mismo, si no, que salen comprometidos a trabajar con las necesidades de nuestra sociedad, en particular en Bahía Blanca, nuestra ciudad.

Además de nuestro grupo, Kostka fundó en el año 1963 al grupo Guía Santa Juana de Arco. Desde ese entonces y hasta la actualidad, ambos trabajamos hermanados en la coeducación de chicas y chicos.

Hoy contamos con 80 beneficiarios (chicos de 7 a 20 años) y 13 dirigentes (mayores a 21 años). El grupo trabaja y vive en su sede de Italia 1133. Anualmente realizamos un campamento de 14 días donde ponemos en práctica todo lo aprendido en el año, y fortalecemos valores, sentido de pertenencia y las relaciones interpersonales.',
                'instagram' => 'https://www.instagram.com/gruposanpiox/',
            ],
            [
                'numero' => '999',
                'nombre' => 'San Antonio de Padua',
                'descripcion' => 'El grupo fue fundado en 1989 por el Padre Bara, el padre de la Iglesia, por lo tanto, al día de hoy, es un grupo católico. Hasta el año 2005 fueron conocidos como Montañeros, ese mismo año se asociaron a Scouts de Argentina.
Famosos por comer torta fritas todos los sábados.',
                'instagram' => 'https://www.instagram.com/gscoutsap',
            ],
            [
                'numero' => '294',
                'nombre' => 'San Pantaleon',
                'descripcion' => 'El Grupo Scout San Pantaleón, emplazado en B° Bella Vista, es parte de Scouts de Argentina, asociación civil sin ánimos de lucro desde hace más 32 años, que tiene como Misión contribuir al desarrollo de los niños y jóvenes, ayudándolos a desarrollar plenamente sus posibilidades físicas, intelectuales, sociales y espirituales; como persona, como ciudadano responsable y como miembro de la comunidad local, nacional e internacional.
Nuestro grupo consta con 80 beneficiarios, entre 7 y 21 años, coordinados por 25 adultos responsables, que de forma voluntaria dedican su tiempo libre en una tarea de prevención y promoción de valores.',
                'instagram' => 'https://www.instagram.com/gs.sanpantaleon/',
            ],
            [
                'numero' => '000',
                'nombre' => 'San Jorge',
                'direccion' => 'Rawson y Bermudez',
                'descripcion' => 'El Grupo Scout esta ubicado en el barrio Sanchez Elias, ya distintivo de la comunidad.
Con 65 años de Historia, sigue acompañando y educando a niñas y niños de entre 5 a 21 años, formando una gran comunidad.',
                'instagram' => 'https://www.instagram.com/gs_sanjorge/',
            ],
            [
                'numero' => '000',
                'nombre' => 'Nuestra Señora de Fatima',
                'direccion' => 'Los Adobes y Ramón y Cajal',
                'descripcion' => 'Fundado el 2 de Abril de 2017, el grupo ubicado en Aldea Romana, en calle Los Adobes y Ramón y Cajal, comenzó solo con la rama de manada, al pasar el tiempo, han ido creciendo y, al día de hoy, ya cuentan con las ramas Unidad y Caminantes. Acompañan y forman a niños y niñas de 7 a 21 años.',
                'instagram' => 'https://www.instagram.com/grupo1370fatima',
            ],
            [
                'numero' => '000',
                'nombre' => '19 de Mayo',
                'direccion' => 'Yapeyú y Victorica',
                'descripcion' => 'El Grupo Scout "19 de Mayo" está ubicado en Yapeyú y Victorica, del Barrio Patagonia en Bahía Blanca.
¡Ya llevamos 33 años de vida construyendo un Mundo Mejor!',
                'instagram' => 'https://www.instagram.com/19dmayo/',
            ],
            ['numero' => '000', 'nombre' => 'San Francisco'],
            ['numero' => '000', 'nombre' => 'Perito Moreno'],
        ];

        $camposPisables = ['descripcion', 'direccion', 'instagram'];

        foreach ($grupos as $datos) {
            $grupo = \App\Models\Grupo::firstOrNew([
                'distrito_id' => $distrito->id, // <--- Aquí se asigna dinámicamente
                'nombre' => $datos['nombre'],
            ]);

            if (!$grupo->exists) {
                $grupo->numero = $datos['numero'];
            }

            foreach ($camposPisables as $campo) {
                if (array_key_exists($campo, $datos)) {
                    $grupo->{$campo} = $datos[$campo];
                }
            }

            $grupo->save();
        }
    }
}
