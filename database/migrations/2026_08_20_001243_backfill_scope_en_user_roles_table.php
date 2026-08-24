<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Las asignaciones de rol sembradas ANTES de agregar rama_id/grupo_id al
     * pivot (migración add_scope_y_auditoria_a_user_roles_table) quedaron con
     * ese scope en null. ProgramController/ProgramPolicy/NoteController ahora
     * leen el scope de Jefe de Grupo/Aux Prog Rama desde ahí (no desde
     * users.rama_id/grupo_id) — sin este backfill, esos usuarios ya
     * existentes dejarían de ver sus programas. Copiamos desde el caché del
     * usuario, que hasta ahora era la única fuente de verdad.
     */
    public function up(): void
    {
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
     * No hay reversa razonable: no podemos distinguir un scope backfillado de
     * uno asignado a mano después de correr esta migración.
     */
    public function down(): void
    {
        //
    }
};
