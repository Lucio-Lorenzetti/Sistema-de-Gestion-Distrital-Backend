<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfil público del grupo, lo completa el Jefe de Grupo desde su
     * Dashboard. Todo opcional: en /distrito solo se muestra lo cargado.
     */
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->string('foto')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 50)->nullable();
            // Si el teléfono tiene WhatsApp, el "Contactar" del Jefe de Grupo abre un chat.
            $table->boolean('telefono_whatsapp')->default(true);
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->text('descripcion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn(['foto', 'direccion', 'telefono', 'telefono_whatsapp', 'instagram', 'facebook', 'descripcion']);
        });
    }
};
