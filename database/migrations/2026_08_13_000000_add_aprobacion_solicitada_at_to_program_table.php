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
        Schema::table('programs', function (Blueprint $table) {
            // Distingue "enviado a revisión" (se puede seguir comentando) de "el
            // educador ya pidió que se lo apruebe" — señal explícita y puntual,
            // igual en espíritu a un comentario: no existe hasta que alguien la
            // dispara. Se resetea a null en cualquier transición de estado.
            $table->timestamp('aprobacion_solicitada_at')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('aprobacion_solicitada_at');
        });
    }
};
