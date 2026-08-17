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
            // Antes vivían como texto libre dentro de la plantilla del cronograma
            // (contentEditable) y el modal de detalle los "adivinaba" parseando con
            // regex — si el educador tocaba esas líneas, el dato desaparecía en
            // silencio. Ahora son campos reales. Solo los usan Campamento y CFA.
            $table->string('lugar')->nullable()->after('anexos');
            $table->string('valor')->nullable()->after('lugar');
            $table->string('transporte')->nullable()->after('valor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['lugar', 'valor', 'transporte']);
        });
    }
};
