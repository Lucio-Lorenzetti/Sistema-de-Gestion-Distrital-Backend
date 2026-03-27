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

        foreach ($grupos as $grupo) {
            \App\Models\Grupo::create([
                'numero' => $grupo['numero'],
                'nombre' => $grupo['nombre'],
                'distrito_id' => $distrito->id // <--- Aquí se asigna dinámicamente
            ]);
        }
    }
}
