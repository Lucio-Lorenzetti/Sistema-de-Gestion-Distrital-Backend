<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Desactivamos FK para limpiar todo sin errores
        DB::statement('SET session_replication_role = replica;');

        // 2. Limpiamos las tablas (en orden inverso de dependencia)
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('grupos')->truncate();
        DB::table('ramas')->truncate();

        // 3. Reactivamos FK
        DB::statement('SET session_replication_role = DEFAULT;');

        // 4. Invocamos los seeders en orden estricto
        $this->call([
            RoleSeeder::class,
            DistritoSeeder::class,
            GrupoSeeder::class,
            RamaSeeder::class,
            UserSeeder::class, // <-- UserSeeder debe ser el ÚLTIMO de los maestros
        ]);
    }
}