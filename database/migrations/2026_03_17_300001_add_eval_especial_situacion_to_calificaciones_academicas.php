<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            $table->decimal('eval_cf', 5, 2)->nullable()->after('nota_extraordinaria');
            $table->decimal('eval_ce', 5, 2)->nullable()->after('eval_cf');
            $table->enum('situacion', ['A', 'R'])->nullable()->after('eval_ce');
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            $table->dropColumn(['eval_cf', 'eval_ce', 'situacion']);
        });
    }
};
