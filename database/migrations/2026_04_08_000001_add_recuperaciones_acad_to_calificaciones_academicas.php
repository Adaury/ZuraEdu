<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            // Almacena hasta 5 recuperaciones académicas: { "1": 5, "2": 3, ... }
            $table->json('recuperaciones_acad')->nullable()->after('situacion');
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            $table->dropColumn('recuperaciones_acad');
        });
    }
};
