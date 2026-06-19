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
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. 🔥 RESETEAR LA SECUENCIA DE POSTGRES A 2 🔥
        // Esto le dice a Postgres que el próximo ID automático debe ser 2.
        // Hacemos esto ANTES de crear cualquier otro usuario.
        if (config('database.default') === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), 2, true);");
        }

        // 3. Ahora sí, asignamos el rol del Director (buscándolo por ID 1)
        $director = User::find(1);
        $rolDirector = Role::where('nombre', 'Director')->first();
        if ($rolDirector) {
            $director->roles()->syncWithoutDetaching([$rolDirector->id]);
        }

        // 4. Creamos el resto de usuarios (Postgres usará la secuencia que acabamos de resetear)
        $auxGeneral = User::create([
            'name' => 'Mariano Costa',
            'email' => 'aux.general@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'must_change_password' => false,
        ]);
        $auxGeneral->roles()->attach(Role::where('nombre', 'Aux Prog General')->first());

        // Mapeo de Auxiliares de Rama
        $ramasMapeo = [
            'Premenores' => 'aux.premenores@gmail.com',
            'Manada' => 'aux.manada@gmail.com',
            'Unidad' => 'aux.unidad@gmail.com',
            'Caminantes' => 'aux.caminantes@gmail.com',
            'Rover' => 'aux.rover@gmail.com',
        ];

        foreach ($ramasMapeo as $nombreRama => $emailAux) {
            $rama = Rama::where('nombre', $nombreRama)->first();
            $auxRama = User::create([
                'name' => "Auxiliar de {$nombreRama}",
                'email' => $emailAux,
                'password' => $passwordFija,
                'activo' => true,
                'must_change_password' => false,
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
            'must_change_password' => false,
        ]);
        $auxCom->roles()->attach(Role::where('nombre', 'Aux Comunicación')->first());

        // 9. Jefe de Grupo
        $jefe = User::create([
            'name' => 'Jefe de Grupo Pompeya',
            'email' => 'jefe.grupo@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'must_change_password' => false,
            'grupo_id' => $grupoPrueba,
        ]);
        $jefe->roles()->attach(Role::whereIn('nombre', ['Jefe de Grupo', 'Educador'])->get());

        // 10. Educador
        $educador = User::create([
            'name' => 'Educador de Prueba',
            'email' => 'educador@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'must_change_password' => true,
            'grupo_id' => $grupoPrueba,
        ]);
        $educador->roles()->attach(Role::where('nombre', 'Educador')->first());
    }
}