<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->json('recuperaciones_ra')->nullable()->after('ra10')
                  ->comment('JSON: {"1":[null,82,null],"2":[75,null,null]} — 3 recuperaciones por RA');
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn('recuperaciones_ra');
        });
    }
};
