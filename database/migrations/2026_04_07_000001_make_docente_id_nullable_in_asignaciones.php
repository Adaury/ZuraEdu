<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->foreignId('docente_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->foreignId('docente_id')->nullable(false)->change();
        });
    }
};
