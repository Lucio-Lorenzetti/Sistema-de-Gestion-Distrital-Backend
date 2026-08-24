<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('requiere_rama')->default(false);
            $table->boolean('requiere_grupo')->default(false);
            $table->boolean('autosolicitable')->default(false);
            // null = no es de reemplazo único; 'grupo' = único por grupo (Jefe de Grupo);
            // 'distrito' = único en todo el distrito (Director).
            $table->string('reemplazo_unico')->nullable();
            // Educador: como máximo una asignación activa por usuario.
            $table->boolean('unico_por_usuario')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['requiere_rama', 'requiere_grupo', 'autosolicitable', 'reemplazo_unico', 'unico_por_usuario']);
        });
    }
};
