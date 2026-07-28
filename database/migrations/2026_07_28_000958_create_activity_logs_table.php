<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion'); // ej: 'noticia_creada', 'curso_creado', 'usuario_creado', 'rol_asignado'
            $table->string('titulo'); // ej: "Subió un programa"
            $table->text('descripcion')->nullable(); // ej: "Campamento de Invierno 2026"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};