<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('link_formulario')->nullable();
            $table->enum('categoria', ['Programa', 'Gestion'])->nullable();
            $table->json('ramas')->nullable(); // solo aplica si categoria = Programa
            $table->date('fecha_cierre')->nullable(); // cierre de inscripción -> pasa a "Cerrado"
            $table->date('fecha_fin')->nullable(); // fecha en que se dicta / finaliza -> pasa a "Finalizado"
            $table->string('lugar')->nullable();
            $table->integer('costo')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('formador')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
