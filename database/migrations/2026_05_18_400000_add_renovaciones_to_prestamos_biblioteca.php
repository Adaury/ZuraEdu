<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos_biblioteca', function (Blueprint $table) {
            $table->unsignedSmallInteger('renovaciones')->default(0)->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos_biblioteca', function (Blueprint $table) {
            $table->dropColumn('renovaciones');
        });
    }
};
