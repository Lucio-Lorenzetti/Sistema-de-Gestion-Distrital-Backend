<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DistritoSeeder::class, // IMPORTANTE: Primero el distrito
            GrupoSeeder::class,    // Después los grupos
            RoleSeeder::class,
            RamaSeeder::class,
            UserSeeder::class,
        ]);
    }

}
