<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_matriculas', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->nullable()->after('notas_admin')
                  ->constrained('estudiantes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_matriculas', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Estudiante::class);
        });
    }
};
