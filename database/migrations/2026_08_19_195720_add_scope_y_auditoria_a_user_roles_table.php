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
        Schema::table('user_roles', function (Blueprint $table) {
            // Scope real de ESTA asignación de rol (no el de users.rama_id/grupo_id,
            // que es solo el caché de la asignación de Educador — ver UserScopeCache).
            $table->foreignId('rama_id')->nullable()->after('role_id')->constrained('ramas')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->after('rama_id')->constrained('grupos')->nullOnDelete();
            $table->foreignId('asignado_por_id')->nullable()->after('grupo_id')->constrained('users')->nullOnDelete();
            $table->timestamp('asignado_at')->nullable()->after('asignado_por_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rama_id');
            $table->dropConstrainedForeignId('grupo_id');
            $table->dropConstrainedForeignId('asignado_por_id');
            $table->dropColumn(['asignado_at', 'created_at', 'updated_at']);
        });
    }
};
