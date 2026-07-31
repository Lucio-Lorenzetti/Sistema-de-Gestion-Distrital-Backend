<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET session_replication_role = replica;');

        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('grupos')->truncate();
        DB::table('ramas')->truncate();
        DB::table('news')->truncate();
        DB::table('courses')->truncate();
        DB::table('programs')->truncate(); 

        DB::statement('SET session_replication_role = DEFAULT;');

        $this->call([
            DistritoSeeder::class,
            GrupoSeeder::class,
            RamaSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            NewsSeeder::class,   
            CourseSeeder::class, 
            ProgramSeeder::class,
        ]);
    }
}