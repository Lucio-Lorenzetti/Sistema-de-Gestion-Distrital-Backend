<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permite "sacarle" la prioridad a una petición (queda sin label), no solo
 * cambiarla — sin doctrine/dbal instalado, se hace con SQL crudo en vez de
 * ->nullable()->change().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE feature_requests ALTER COLUMN prioridad DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE feature_requests SET prioridad = 'media' WHERE prioridad IS NULL");
        DB::statement("ALTER TABLE feature_requests ALTER COLUMN prioridad SET NOT NULL");
    }
};
