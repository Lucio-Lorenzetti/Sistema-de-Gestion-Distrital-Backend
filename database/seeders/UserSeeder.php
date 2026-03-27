<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creamos explícitamente al SuperAdmin con ID 1
        $admin = \App\Models\User::create([
            'id' => 1, 
            'name' => 'Lucio Lorenzetti', // [cite: 1]
            'email' => 'lucio.admin@distrito.com',
            'password' => \Illuminate\Support\Facades\Hash::make('tu_password_segura'),
            'activo' => true,
        ]);

        // Le asignamos el rol de Director
        $role = \App\Models\Role::where('nombre', 'Director')->first();
        $admin->roles()->attach($role);
    }
}
