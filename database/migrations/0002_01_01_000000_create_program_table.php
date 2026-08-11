<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('diagnostico')->nullable();
            $table->text('objetivos')->nullable();
            $table->text('educadores_a_cargo')->nullable(); // ← nueva columna
            $table->string('tipo')->default('cfa');

            // Relaciones
            $table->foreignId('rama_id')->constrained('ramas')->onDelete('cascade');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->onDelete('set null');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // Fechas del programa
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            // Estructuras compuestas en JSONB (PostgreSQL)
            $table->jsonb('cronograma')->nullable();
            $table->jsonb('anexos')->nullable();

            // Estado del ciclo de vida
            $table->enum('estado', ['borrador', 'enviado', 'aprobado', 'rechazado'])->default('borrador');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};