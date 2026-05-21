<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Rama;
use App\Models\Grupo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // <--- 1. IMPORTANTE: Agregamos esta línea para el parche

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Guardamos la contraseña encriptada en una variable limpia
        $passwordFija = Hash::make('12345'); 
        
        // Buscamos un grupo de prueba para asociar a los usuarios de grupo
        $grupoPrueba = Grupo::first() ? Grupo::first()->id : null;

        // 1. Lucio Lorenzetti - Director de Distrito (Mantenemos ID 1 para tu escudo de seguridad)
        $director = User::create([
            'id' => 1, 
            'name' => 'Lucio Lorenzetti',
            'email' => 'lucioadriell@gmail.com',
            'password' => $passwordFija,
            'activo' => true,
            'must_change_password' => false,
        ]);
        $director->roles()->attach(Role::where('nombre', 'Director')->first());

        // 🔥 2. EL PARCHE MÁGICO PARA POSTGRESQL 🔥
        // Le ordenamos a Postgres que actualice la secuencia interna de la tabla users
        if (config('database.default') === 'pgsql') {
            DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");
        }

        // 3. Auxiliar de Programa General (Ahora Postgres sabe que tiene que asignarle el ID 2)
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
            'Manada'     => 'aux.manada@gmail.com',
            'Unidad'     => 'aux.unidad@gmail.com',
            'Caminantes' => 'aux.caminantes@gmail.com',
            'Rover'      => 'aux.rover@gmail.com',
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
            'must_change_password' => true, // Fuerza la redirección en tu interfaz React
            'grupo_id' => $grupoPrueba,
        ]);
        $educador->roles()->attach(Role::where('nombre', 'Educador')->first());
    }
}