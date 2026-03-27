<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $roles = ['Director', 'Jefe de Grupo', 'Aux Prog General', 'Aux Prog Rama', 'Aux Comunicación', 'Educador'];
        foreach ($roles as $role) {
            \App\Models\Role::create(['nombre' => $role]);
        }
    }
}
