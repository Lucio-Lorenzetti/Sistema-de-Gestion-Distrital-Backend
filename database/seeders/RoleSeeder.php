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
        $roles = [
            // Developer: bypass total (ver Gate::before en AppServiceProvider), no se
            // solicita ni se designa — el primer Developer se asigna a mano.
            ['nombre' => 'Developer',        'requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => false, 'reemplazo_unico' => null,       'unico_por_usuario' => false],
            ['nombre' => 'Director',         'requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => false, 'reemplazo_unico' => 'distrito', 'unico_por_usuario' => false],
            ['nombre' => 'Jefe de Grupo',    'requiere_rama' => false, 'requiere_grupo' => true,  'autosolicitable' => false, 'reemplazo_unico' => 'grupo',    'unico_por_usuario' => false],
            ['nombre' => 'Aux Prog General', 'requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            ['nombre' => 'Aux Prog Rama',    'requiere_rama' => true,  'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            ['nombre' => 'Aux Comunicación', 'requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            // Educador: como máximo una asignación activa por usuario (ver RoleRequestService).
            ['nombre' => 'Educador',         'requiere_rama' => true,  'requiere_grupo' => true,  'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => true],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate(['nombre' => $role['nombre']], $role);
        }
    }
}
