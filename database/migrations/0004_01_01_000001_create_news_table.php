<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('contenido');
            $table->foreignId('autor_id')->constrained('users');

            // Campos nuevos
            $table->string('estado')->default('Borrador'); // Publicada, Borrador
            $table->string('categoria')->nullable();       // 'grupo' en tu mock
            $table->unsignedInteger('visitas')->default(0); // contador de vistas

            $table->timestamp('publicado_at')->nullable();
            $table->timestamps();
            $table->string('imagen')->nullable(); // URL o path del archivo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};