<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Rama;
use App\Models\Grupo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $passwordFija = Hash::make('12345');
        $grupoPrueba = Grupo::first()?->id;

        // 1. Aseguramos ID 1 para el Director
        DB::table('users')->updateOrInsert(
            ['email' => 'lucioadriell@gmail.com'],
            [
                'id' => 1,
                'name' => 'Lucio Lorenzetti',
                'password' => $passwordFija,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. 🔥 RESETEAR LA SECUENCIA DE POSTGRES A 2 🔥
        if (config('database.default') === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), 2, true);");
        }

        // 3. Ahora sí, asignamos el rol del Director
        $director = User::find(1);
        $rolDirector = Role::where('nombre', 'Director')->first();
        if ($rolDirector) {
            $director->roles()->syncWithoutDetaching([$rolDirector->id]);
        }

        // El primer Developer se asigna a mano, acá — es la cuenta real del
        // dueño del sistema, no un flujo de auto-elevación. En un entorno
        // real, esta línea es la que hay que revisar/quitar si corresponde.
        $rolDeveloper = Role::where('nombre', 'Developer')->first();
        if ($rolDeveloper) {
            $director->roles()->syncWithoutDetaching([$rolDeveloper->id]);
        }

        // 4. Creamos el resto de usuarios
        $auxGeneral = User::create([
            'name' => 'Mariano Costa',
            'email' => 'aux.general@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
        ]);
        $auxGeneral->roles()->attach(Role::where('nombre', 'Aux Prog General')->first());

        // Mapeo de Auxiliares de Rama
        // OJO: estos nombres tienen que ser IDÉNTICOS a los de RamaSeeder
        // (Castores, Lobatos, Unidad Scout, Caminantes, Rovers), si no, Rama::where()
        // devuelve null y el auxiliar queda sin rama_id.
        $ramasMapeo = [
            'Castores'     => 'aux.castores@gmail.com',
            'Lobatos'      => 'aux.lobatos@gmail.com',
            'Unidad Scout' => 'aux.unidad@gmail.com',
            'Caminantes'   => 'aux.caminantes@gmail.com',
            'Rovers'       => 'aux.rovers@gmail.com',
        ];

        foreach ($ramasMapeo as $nombreRama => $emailAux) {
            $rama = Rama::where('nombre', $nombreRama)->first();
            $auxRama = User::create([
                'name' => "Auxiliar de {$nombreRama}",
                'email' => $emailAux,
                'password' => $passwordFija,
                'activo' => true,
                'rama_id' => $rama ? $rama->id : null,
            ]);
            $auxRama->roles()->attach(Role::where('nombre', 'Aux Prog Rama')->first());
        }

        // 8. Auxiliar de Comunicación
        $auxCom = User::create([
            'name' => 'Sofía Medina',
            'email' => 'aux.comunicacion@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
        ]);
        $auxCom->roles()->attach(Role::where('nombre', 'Aux Comunicación')->first());

        // 9. Jefe de Grupo (de prueba)
        $jefe = User::create([
            'name' => 'Jefe de Grupo Pompeya',
            'email' => 'jefe.grupo@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'grupo_id' => $grupoPrueba,
        ]);
        $jefe->roles()->attach(Role::whereIn('nombre', ['Jefe de Grupo', 'Educador'])->get());

        // 10. Educador (de prueba)
        $educador = User::create([
            'name' => 'Educador de Prueba',
            'email' => 'educador@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'grupo_id' => $grupoPrueba,
        ]);
        $educador->roles()->attach(Role::where('nombre', 'Educador')->first());

        // --- NUEVOS DATOS SOLICITADOS ---
        $grupos = [
            ['numero' => '034', 'nombre' => 'Pompeya'],
            ['numero' => '000', 'nombre' => 'San Pio'],
            ['numero' => '999', 'nombre' => 'San Antonio de Padua'],
            ['numero' => '294', 'nombre' => 'San Pantaleon'],
            ['numero' => '000', 'nombre' => 'San Jorge'],
            ['numero' => '000', 'nombre' => 'Nuestra Señora de Fatima'],
            ['numero' => '000', 'nombre' => '19 de Mayo'],
            ['numero' => '000', 'nombre' => 'San Francisco'],
            ['numero' => '000', 'nombre' => 'Perito Moreno'],
        ];

        // Alineado 1:1 con los nombres de RamaSeeder (antes faltaba Castores
        // y el resto no matcheaba: Manada->Lobatos, Unidad->Unidad Scout, Rover->Rovers)
        $ramasEducador = ['Castores', 'Lobatos', 'Unidad Scout', 'Caminantes', 'Rovers'];
        $rolEducador = Role::where('nombre', 'Educador')->first();
        $rolJefe = Role::where('nombre', 'Jefe de Grupo')->first();

        foreach ($grupos as $g) {
            $grupoModel = Grupo::firstOrCreate(['nombre' => $g['nombre']], ['numero' => $g['numero']]);

            // Crear Jefe de Grupo
            User::create([
                'name' => "Jefe de Grupo {$g['nombre']}",
                'email' => "jefe." . str_replace(' ', '', strtolower($g['nombre'])) . "@distrito.com",
                'password' => $passwordFija,
                'activo' => true,
                'grupo_id' => $grupoModel->id
            ])->roles()->attach([$rolJefe->id, $rolEducador->id]);

            // Crear 2 educadores por cada rama
            foreach ($ramasEducador as $nombreRama) {
                $rama = Rama::where('nombre', $nombreRama)->first();
                for ($i = 1; $i <= 2; $i++) {
                    User::create([
                        'name' => "Educador {$nombreRama} {$i} ({$g['nombre']})",
                        'email' => "edu." . str_replace(' ', '', strtolower($nombreRama)) . ".{$i}." . str_replace(' ', '', strtolower($g['nombre'])) . "@distrito.com",
                        'password' => $passwordFija,
                        'activo' => true,
                        'grupo_id' => $grupoModel->id,
                        'rama_id' => $rama?->id
                    ])->roles()->attach($rolEducador->id);
                }
            }
        }
    }
}