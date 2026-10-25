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
        Schema::table('program_notes', function (Blueprint $table) {
            // Copia del texto de la línea al momento de comentar. El anclaje por
            // line_ref es por posición: si el educador corrige el programa y la línea
            // cambia (o se corre), esta copia permite avisarlo y mostrar a qué se refería.
            $table->text('line_texto')->nullable()->after('line_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_notes', function (Blueprint $table) {
            $table->dropColumn('line_texto');
        });
    }
};
