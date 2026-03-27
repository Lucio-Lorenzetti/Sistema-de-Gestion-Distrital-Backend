<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RamaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $ramas = ['Castores', 'Lobatos', 'Unidad Scout', 'Caminantes', 'Rovers'];
        foreach ($ramas as $rama) {
            \App\Models\Rama::create(['nombre' => $rama]);
        }
    }
}
