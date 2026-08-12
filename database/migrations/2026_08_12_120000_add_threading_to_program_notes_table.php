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
            $table->foreignId('parent_id')->nullable()->after('user_id')
                ->constrained('program_notes')->onDelete('cascade');
            $table->string('line_ref', 190)->nullable()->after('contenido');
            $table->boolean('resuelta')->default(false)->after('line_ref');

            $table->index(['program_id', 'line_ref']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_notes', function (Blueprint $table) {
            $table->dropIndex(['program_id', 'line_ref']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['line_ref', 'resuelta']);
        });
    }
};
