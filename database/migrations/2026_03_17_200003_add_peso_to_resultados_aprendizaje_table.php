<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados_aprendizaje', function (Blueprint $table) {
            // null = usar distribución equitativa automática (100 / numRA)
            $table->decimal('peso', 5, 2)->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('resultados_aprendizaje', function (Blueprint $table) {
            $table->dropColumn('peso');
        });
    }
};
