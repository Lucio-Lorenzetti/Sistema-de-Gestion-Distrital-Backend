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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('diagnostico')->nullable();
            $table->text('objetivos')->nullable();
            
            // Aquí vive la magia del "Drive"
            $table->jsonb('cronograma')->nullable(); // Guardarás el array de horarios
            $table->jsonb('anexos')->nullable();     // Guardarás el array de juegos/detalles
            
            $table->string('estado')->default('borrador');
            $table->foreignId('owner_id')->constrained('users');
            $table->foreignId('rama_id')->constrained('ramas');
            $table->foreignId('grupo_id')->constrained('grupos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
