<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * add_metadata_to_roles_table agregó requiere_rama/requiere_grupo con
     * default false y los valores reales solo los cargaba RoleSeeder. En una
     * base existente donde se migró sin re-seedear (producción), el backfill
     * de backfill_scope_en_user_roles_table filtró por esos flags en false y
     * no tocó nada: los Jefes de Grupo quedaron con user_roles.grupo_id en
     * null y /mi-grupo responde 403. Acá se cargan los flags (mismos valores
     * que RoleSeeder) y se repite el backfill. Idempotente: solo completa
     * scopes en null.
     */
    public function up(): void
    {
        $roles = [
            'Developer'        => ['requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => false, 'reemplazo_unico' => null,       'unico_por_usuario' => false],
            'Director'         => ['requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => false, 'reemplazo_unico' => 'distrito', 'unico_por_usuario' => false],
            'Jefe de Grupo'    => ['requiere_rama' => false, 'requiere_grupo' => true,  'autosolicitable' => false, 'reemplazo_unico' => 'grupo',    'unico_por_usuario' => false],
            'Aux Prog General' => ['requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            'Aux Prog Rama'    => ['requiere_rama' => true,  'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            'Aux Comunicación' => ['requiere_rama' => false, 'requiere_grupo' => false, 'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => false],
            'Educador'         => ['requiere_rama' => true,  'requiere_grupo' => true,  'autosolicitable' => true,  'reemplazo_unico' => null,       'unico_por_usuario' => true],
        ];

        foreach ($roles as $nombre => $flags) {
            DB::table('roles')->where('nombre', $nombre)->update($flags);
        }

        DB::statement('
            UPDATE user_roles ur
            SET rama_id = u.rama_id
            FROM users u, roles r
            WHERE ur.user_id = u.id
              AND ur.role_id = r.id
              AND r.requiere_rama = true
              AND ur.rama_id IS NULL
        ');

        DB::statement('
            UPDATE user_roles ur
            SET grupo_id = u.grupo_id
            FROM users u, roles r
            WHERE ur.user_id = u.id
              AND ur.role_id = r.id
              AND r.requiere_grupo = true
              AND ur.grupo_id IS NULL
        ');
    }

    /**
     * Sin reversa: igual que el backfill original, no se puede distinguir un
     * scope completado acá de uno asignado a mano después.
     */
    public function down(): void
    {
        //
    }
};
