<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('contenido');
            // pendiente | proxima_version | descartada | implementada
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};
