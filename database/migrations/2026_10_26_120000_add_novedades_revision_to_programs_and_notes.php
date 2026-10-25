<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Datos para el tablero de novedades de los auxiliares de programa: cuándo
     * entró un programa a revisión, si volvió a entrar después de una vuelta de
     * comentarios, y quién resolvió cada hilo (para no avisarle a uno mismo).
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->timestamp('enviado_at')->nullable()->after('estado');
            $table->boolean('enviado_tras_comentarios')->default(false)->after('enviado_at');
        });

        Schema::table('program_notes', function (Blueprint $table) {
            $table->foreignId('resuelta_por')->nullable()->after('resuelta')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('resuelta_at')->nullable()->after('resuelta_por');
        });

        // Los programas que ya están en revisión toman la última modificación como
        // fecha de envío: es lo más cercano que hay. El resto queda en null.
        DB::table('programs')->where('estado', 'enviado')->update(['enviado_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resuelta_por');
            $table->dropColumn('resuelta_at');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['enviado_at', 'enviado_tras_comentarios']);
        });
    }
};
