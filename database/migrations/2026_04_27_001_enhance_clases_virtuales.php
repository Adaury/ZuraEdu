<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases_virtuales', function (Blueprint $table) {
            $table->string('codigo_clase', 8)->nullable()->unique()->after('nombre');
            $table->string('portada_imagen')->nullable()->after('portada_color');
            $table->integer('estudiantes_count')->default(0)->after('portada_imagen');
        });
    }

    public function down(): void
    {
        Schema::table('clases_virtuales', function (Blueprint $table) {
            $table->dropColumn(['codigo_clase', 'portada_imagen', 'estudiantes_count']);
        });
    }
};
