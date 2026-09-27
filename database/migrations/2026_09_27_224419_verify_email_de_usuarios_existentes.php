<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * login() ahora exige email_verified_at para poder entrar (nuevo requisito de
 * verificación de email al registrarse). Sin este backfill, TODOS los
 * usuarios ya existentes (que se dieron de alta antes de que este requisito
 * existiera) quedarían bloqueados de un día para el otro — se los da por
 * verificados retroactivamente, el requisito nuevo rige solo para altas de
 * acá en adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
